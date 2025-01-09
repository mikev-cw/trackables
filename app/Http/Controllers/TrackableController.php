<?php

namespace App\Http\Controllers;

use App\Http\Resources\TrackableResource;
use App\Models\Trackable;
use App\Models\TrackableData;
use App\Models\TrackableRecord;
use App\Models\TrackableSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackableController extends Controller
{

    public function create(Request $request) {
//        dd($request);
//        dd(Auth::id());
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

    public function storeRecord(Request $request)
    {

        $schema = $request->trackable->schema->keyBy('uid');

        $validationRules = $schema->mapWithKeys(function ($item, $key) use ($request) {
            // Use validation_rule from schema
            return [$key => $item->validation_rule];
        })->toArray();

        if (empty($validationRules)) {
            return response()->json([
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
            'message' => 'Data successfully saved.',
        ]);


    }

    public function storeSingleSchema(Request $request) {
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
}
