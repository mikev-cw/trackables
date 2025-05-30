<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\TrackableController;
use App\Http\Controllers\TrackableRecordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function (Request $request) {
    return response()->json(['response' => 'pong']);
});

Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    // Expose user data
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->name('profile.edit');

    // List Trackables
    Route::get('trackable', [TrackableController::class, 'list']);

    // Create Trackable
    Route::post('trackable', [TrackableController::class, 'create']);

    // Get all records
    Route::get('trackable/{trackable}/record', [TrackableRecordController::class, 'getRecords'])
        ->can('own', 'trackable');

    // Create record(s)
    Route::post('trackable/{trackable}/record', [TrackableController::class, 'storeBulkRecords'])
        ->can('own', 'trackable');

    // Create bulk records
    Route::post('trackable/{trackable}/record/bulk', [TrackableController::class, 'storeBulkRecords'])
        ->can('own', 'trackable');

    // Create Schema
    Route::post('trackable/{trackable}/schema', [TrackableController::class, 'storeSingleSchema'])
        ->can('own', 'trackable');

    // Edit Schema
    Route::patch('trackable/{trackable}/schema/{schema}', [TrackableController::class, 'editSchema'])
        ->can('own', 'trackable');

});
