<?php

namespace App\Http\Controllers;

use App\Http\Resources\TrackableRecordResource;
use App\Models\TrackableRecord;
use Illuminate\Http\Request;

class TrackableRecordController extends Controller
{
    public function getRecords(Request $request)
    {
        return TrackableRecordResource::collection(TrackableRecord::with('data')
            ->where('trackable_uid', $request->trackable->uid)
            ->orderByDesc('created_at')
            ->paginate(10));
    }
}
