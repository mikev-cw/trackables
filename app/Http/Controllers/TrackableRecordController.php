<?php

namespace App\Http\Controllers;

use App\Models\TrackableRecord;
use Illuminate\Http\Request;

class TrackableRecordController extends Controller
{
    public function getRecords(Request $request)
    {
        $t = TrackableRecord::select('uid', 'record_date')
            ->with(['data' => function ($query) {
                $query->select('uid', 'trackable_record_uid', 'trackable_schema_uid', 'value') // Include schema_id for joining
                ->with(['schema' => function ($query) {
                    $query->select('uid', 'name');
                }]);
            }])
            ->where('trackable_uid', $request->trackable->uid)
            ->orderByDesc('created_at')
            ->paginate(10);

        // Hide the foreign key from the 'data' relationship
        $t->getCollection()->transform(function ($record) {
            $record->data->transform(function ($data) {
                $data->name = $data->schema->name ?? null; // Flatten the 'schema' field to just 'name'
                unset($data->schema); // Remove the 'schema' object
                return $data;
            });

            $record->data->makeHidden('trackable_record_uid');

            return $record;
        });

        return $t;
    }
}
