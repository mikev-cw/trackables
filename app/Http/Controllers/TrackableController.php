<?php

namespace App\Http\Controllers;

use App\Models\Trackable;
use App\Models\TrackableData;
use App\Models\TrackableRecord;
use App\Models\TrackableSchema;
use Illuminate\Http\Request;

class TrackableController extends Controller
{
    public function storeRecord(Request $request)
    {

        $schema = $request->trackable->schema->keyBy('uid');

        $validationRules = $schema->mapWithKeys(function ($item, $key) use ($request) {
            // Use validation_rule from schema
            return [$key => $item->validation_rule];
        })->toArray();

        // If no validation rules, handle appropriately
        if (empty($validationRules)) {
            return response()->json([
                'message' => 'No validation rules were applied. Ensure your input matches the schema.',
            ], 400);
        }

        // Proceed with validation
        $validated = $request->validate($validationRules);

//        dd($validated);
//
//        exit;

        // Create the record
        $record = TrackableRecord::create([
            'trackable_uid' => $request->trackable->uid,
            'record_date' => now(),
        ]);

//        dd($record->uid);
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
}
