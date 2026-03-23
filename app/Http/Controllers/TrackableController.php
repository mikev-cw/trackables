<?php

namespace App\Http\Controllers;

use App\Http\Resources\TrackableResource;
use App\Models\Trackable;
use App\Models\TrackableData;
use App\Models\TrackableRecord;
use App\Models\TrackableSchema;
use Illuminate\Database\Eloquent\Collection;
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
        $trackable = $request->trackable;
        $validated = $this->validateSingleRecord($request, $trackable);
        $record = $this->persistSingleRecord($trackable, $validated);

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

    public function show(Trackable $trackable, Request $request)
    {
        $perPageOptions = [25, 50, 100, 250];
        $sortOptions = ['record_date', 'created_at'];
        $sortBy = $request->query('sort_by', 'record_date');
        $sortDir = $request->query('sort_dir', 'desc');
        $perPage = (int) $request->query('per_page', 25);

        if (!in_array($sortBy, $sortOptions, true)) {
            $sortBy = 'record_date';
        }

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }

        $trackable->load('schema');

        // Estrai e decodifica lo schema se è JSON
        $schema = $trackable->schema;
        if (is_string($schema)) {
            $decoded = json_decode($schema, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $schema = $decoded;
            }
        }

        $schemaCollection = collect($schema);
        $schemaFieldFilters = collect($request->query('schema', []))
            ->filter(function ($value) {
                return !is_null($value) && trim((string) $value) !== '';
            })
            ->map(function ($value) {
                return trim((string) $value);
            })
            ->only($schemaCollection->pluck('uid')->all());

        $recordsQuery = $trackable->records()
            ->with('data')
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('record_date', '>=', $request->query('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('record_date', '<=', $request->query('date_to'));
            })
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));

                $query->whereHas('data', function ($dataQuery) use ($search) {
                    $dataQuery->where('value', 'like', '%'.$search.'%');
                });
            })
            ->when($schemaFieldFilters->isNotEmpty(), function ($query) use ($schemaCollection, $schemaFieldFilters) {
                foreach ($schemaFieldFilters as $schemaUid => $value) {
                    $field = $schemaCollection->firstWhere('uid', $schemaUid);
                    $operator = $this->usesExactSchemaFilter($field?->field_type) ? '=' : 'like';
                    $comparisonValue = $operator === '=' ? $value : '%'.$value.'%';

                    $query->whereHas('data', function ($dataQuery) use ($schemaUid, $operator, $comparisonValue) {
                        $dataQuery
                            ->where('trackable_schema_uid', $schemaUid)
                            ->where('value', $operator, $comparisonValue);
                    });
                }
            })
            ->orderBy($sortBy, $sortDir)
            ->orderBy('uid', 'desc');

        $records = $recordsQuery
            ->paginate($perPage)
            ->withQueryString();

        $schemaByUid = $schemaCollection->keyBy('uid');
        $schemaOrder = $schemaCollection->pluck('uid')->flip();
        $filters = [
            'q' => (string) $request->query('q', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
            'per_page' => $perPage,
            'schema' => $schemaCollection->mapWithKeys(function ($field) use ($schemaFieldFilters) {
                return [$field->uid => (string) $schemaFieldFilters->get($field->uid, '')];
            })->toArray(),
        ];
        $activeFilterCount = count(array_filter([
            $filters['q'],
            $filters['date_from'],
            $filters['date_to'],
        ])) + $schemaFieldFilters->count();

        return view('trackables.show', compact(
            'trackable',
            'schema',
            'schemaByUid',
            'schemaOrder',
            'records',
            'filters',
            'activeFilterCount',
            'sortOptions',
            'perPageOptions'
        ));
    }

    public function createRecord(Trackable $trackable)
    {
        $schema = $trackable->schema()->orderBy('created_at')->get();

        return view('trackables.create-record', compact('trackable', 'schema'));
    }

    public function storeRecord(Request $request, Trackable $trackable)
    {
        $validated = $this->validateSingleRecord($request, $trackable);
        $this->persistSingleRecord($trackable, $validated);

        return redirect()
            ->route('trackables.show', $trackable->uid)
            ->with('status', 'Record added successfully.');
    }

    public function editRecord(Trackable $trackable, TrackableRecord $record)
    {
        $record = $this->getTrackableRecordOrFail($trackable, $record);
        $schema = $trackable->schema()->orderBy('created_at')->get();
        $recordValues = $this->getRecordValues($record);

        return view('trackables.edit-record', compact('trackable', 'record', 'schema', 'recordValues'));
    }

    public function updateRecord(Request $request, Trackable $trackable, TrackableRecord $record)
    {
        $record = $this->getTrackableRecordOrFail($trackable, $record);
        $validated = $this->validateSingleRecord($request, $trackable);
        $this->updateSingleRecord($record, $validated);

        return redirect()
            ->route('trackables.show', $trackable->uid)
            ->with('status', 'Record updated successfully.');
    }

    private function validateSingleRecord(Request $request, Trackable $trackable): array
    {
        $schema = $trackable->schema->keyBy('uid');
        $validationRules = $this->getSingleRecordValidationRules($schema);

        if (empty($validationRules)) {
            abort(400, 'No validation rules were applied. Ensure your input matches the schema.');
        }

        return $request->validate($validationRules);
    }

    private function getSingleRecordValidationRules(Collection $schema): array
    {
        return $schema->mapWithKeys(function ($item, $key) {
            return [$key => $item->validation_rule];
        })->toArray();
    }

    private function usesExactSchemaFilter(?string $fieldType): bool
    {
        return in_array($fieldType, ['int', 'float', 'bool', 'date', 'datetime'], true);
    }

    private function getTrackableRecordOrFail(Trackable $trackable, TrackableRecord $record): TrackableRecord
    {
        abort_unless($record->trackable_uid === $trackable->uid, 404);

        return $record->load('data');
    }

    private function getRecordValues(TrackableRecord $record): array
    {
        return $record->data
            ->mapWithKeys(function ($item) {
                return [$item->trackable_schema_uid => $item->value];
            })
            ->toArray();
    }

    private function persistSingleRecord(Trackable $trackable, array $validated): TrackableRecord
    {
        return DB::transaction(function () use ($trackable, $validated) {
            $record = TrackableRecord::create([
                'trackable_uid' => $trackable->uid,
                'record_date' => now(),
            ]);

            foreach ($validated as $key => $value) {
                TrackableData::create([
                    'trackable_record_uid' => $record->uid,
                    'trackable_schema_uid' => $key,
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                ]);
            }

            return $record;
        });
    }

    private function updateSingleRecord(TrackableRecord $record, array $validated): void
    {
        DB::transaction(function () use ($record, $validated) {
            $record->update([
                'record_date' => now(),
            ]);

            $existingData = $record->data()->get()->keyBy('trackable_schema_uid');

            foreach ($validated as $key => $value) {
                $serializedValue = is_array($value) ? json_encode($value) : (string) $value;
                $existingField = $existingData->get($key);

                if ($existingField) {
                    $existingField->update([
                        'value' => $serializedValue,
                    ]);

                    continue;
                }

                TrackableData::create([
                    'trackable_record_uid' => $record->uid,
                    'trackable_schema_uid' => $key,
                    'value' => $serializedValue,
                ]);
            }
        });
    }
}
