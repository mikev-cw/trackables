<?php

namespace App\Http\Controllers;

use App\Http\Resources\TrackableResource;
use App\Models\Trackable;
use App\Models\TrackableData;
use App\Models\TrackableRecord;
use App\Models\TrackableSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TrackableController extends Controller
{

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
        ]);

        $t = Trackable::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
        ]);

        return $t;
    }

    public function list(Request $request)
    {
        return TrackableResource::collection(Trackable::with('schema')
            ->where('deleted',0)
            ->where('user_id',$request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(10));
    }

    public function storeSingleRecord(Request $request)
    {

        $schema = $request->trackable->schema->keyBy('uid');

        $validationRules = $schema->mapWithKeys(function ($item, $key) use ($request) {
            // Use validation_rule from schema
            return [$key => $item->validation_rule];
        })->toArray();

        if (empty($validationRules)) {
            return response()->json([
                'success' => false,
                'message' => 'No validation rules were applied. Ensure your input matches the schema.',
            ], 400);
        }

        $validated = $request->validate($validationRules);

        $record = TrackableRecord::create([
            'trackable_uid' => $request->trackable->uid,
            'record_date' => now(),
        ]);

        foreach ($validated as $key => $value) {
            TrackableData::create([
                'trackable_record_uid' => $record->uid,
                'trackable_schema_uid' => $key, // The key (schema UID)
                'value' => $value,
            ]);
        }

        // TODO Return a success response
        return response()->json([
            'record_uid' => $record->uid,
            'success' => true,
            'message' => 'Data successfully saved.',
        ]);
    }

    public function storeBulkRecords(Request $request)
    {
        $schema = $request->trackable->schema->keyBy('uid');

        // Generate validation rules dynamically from the schema
        $validationRules = $schema->mapWithKeys(function ($item, $key) {
            return ["records.*.$key" => $item->validation_rule];
        })->toArray();

        if (empty($validationRules)) {
            return response()->json([
                'success' => false,
                'message' => 'No validation rules were applied. Ensure your input matches the schema.',
            ], 400);
        }

        // Validate all records
        $validated = $request->validate([
                'records' => 'required|array|min:1',
            ] + $validationRules);

        $items = $validated['records'];

        $recordsCreated =[];

        DB::transaction(function () use (&$request, &$items, &$recordsCreated) {
            foreach ($items as $recordData) {
                // Create a record
                $record = TrackableRecord::create([
                    'trackable_uid' => $request->trackable->uid,
                    'record_date' => now(),
                ]);

                $recordsCreated[] = $record->uid;

                // Prepare TrackableData instances
                $trackableDataInstances = [];
                foreach ($recordData as $key => $value) {
                    $trackableDataInstances[] = new TrackableData([
                        'trackable_record_uid' => $record->uid,
                        'trackable_schema_uid' => $key,
                        'value' => $value,
                    ]);
                }

                // Bulk save using saveMany() to trigger Eloquent events
                if (!empty($trackableDataInstances)) {
                    $record->data()->saveMany($trackableDataInstances);
                }
            }
        });

        return response()->json([
            'record_uids' => $recordsCreated,
            'success' => true,
            'message' => count($recordsCreated).' records created successfully',
        ], 201);
    }

    public function storeSingleSchema(Request $request)
    {
        // store a single schema for a trackable

        $validated = $request->validate([
            'name' => 'required|max:80',
            'field_type' => 'required',
            'enum_uid' => 'nullable',
            'calc_formula' => 'nullable',
            'validation_rule' => 'required',
        ]);

        return TrackableSchema::create([
            'trackable_uid' => $request->trackable->uid,
            'name' => $validated['name'],
            'field_type' => $validated['field_type'],
            'enum_uid' => $validated['enum_uid'] ?? null,
            'calc_formula' => $validated['calc_formula'] ?? null,
            'validation_rule' => $validated['validation_rule'],
        ]);

    }

    public function editSchema(Request $request)
    {
        // Find the model by ID
        $model = TrackableSchema::findOrFail($request->schema);

        // Define validation rules
        // https://laravel.com/docs/11.x/validation#available-validation-rules
        $rules = [
            'name' => 'sometimes|string|max:80',
            'field_type' => 'sometimes|string',
            'enum_uid' => 'sometimes|string|max:24',
            'calc_formula' => 'sometimes|',
            'validation_rule' => 'sometimes|',
        ];

        // Validate the incoming request
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update only the provided fields
        $model->update($request->only(array_keys($rules)));

        return response()->json(['message' => 'Model updated successfully', 'data' => $model], 200);
    }

    public function show($id, Request $request)
    {
        $perPage = (int) $request->query('per_page', 25);

        $trackable = Trackable::with(['schema', 'records' => function ($q) {
            $q->orderBy('created_at', 'desc');
        }])->findOrFail($id);

        // Estrai e decodifica lo schema se è JSON
        $schema = $trackable->schema;
        if (is_string($schema)) {
            $decoded = json_decode($schema, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $schema = $decoded;
            }
        }

        // Recupera i record collegati, ordinati per più recenti, paginati
        $records = $trackable->records()
            ->with('data')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('trackables.show', compact('trackable', 'schema', 'records'));
    }
}
