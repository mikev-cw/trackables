<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\TrackableController;
use App\Models\Trackable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function (Request $request) {
    echo "PONG!".PHP_EOL;
});

Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {

    // Expose user data
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->name('profile.edit');

    // List Trackables
    Route::get('trackable',function (Request $request) {
        $t = Trackable::with('schema')
            ->where('deleted',0)
            ->where('user_id',$request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(5);
        return response()->json($t);
    });

    // Create Trackable
    Route::post('trackable', function(Request $request) {
        return response()->json(['trackable creation']);
    });

    // Get all records

    // Create record
    Route::post('trackable/{trackable}/record', [TrackableController::class, 'storeRecord'])
        ->can('create', 'trackable')
    ;

});
