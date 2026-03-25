<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TrackableController;
use Illuminate\Support\Facades\Route;


Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/trackables/', [DashboardController::class, 'index'])->name('trackables_index');
    Route::get('/trackables/create', [TrackableController::class, 'createTrackablePage'])->name('trackables.create');
    Route::post('/trackables', [TrackableController::class, 'storeTrackable'])->name('trackables.store');
    Route::get('/trackables/{trackable}/edit', [TrackableController::class, 'editTrackablePage'])
        ->can('own', 'trackable')
        ->name('trackables.edit');
    Route::put('/trackables/{trackable}', [TrackableController::class, 'updateTrackable'])
        ->can('own', 'trackable')
        ->name('trackables.update');
    Route::patch('/trackables/{trackable}/toggle', [TrackableController::class, 'toggleTrackable'])
        ->can('own', 'trackable')
        ->name('trackables.toggle');
    Route::get('/trackables/{trackable}/schema/edit', [TrackableController::class, 'editSchemaPage'])
        ->can('own', 'trackable')
        ->name('trackables.schema.edit');
    Route::post('/trackables/{trackable}/schema', [TrackableController::class, 'storeSchemaFromPage'])
        ->can('own', 'trackable')
        ->name('trackables.schema.store');
    Route::put('/trackables/{trackable}/schema/{schema}', [TrackableController::class, 'updateSchemaFromPage'])
        ->can('own', 'trackable')
        ->name('trackables.schema.update');
    Route::get('/trackables/{trackable}/records/create', [TrackableController::class, 'createRecord'])
        ->can('own', 'trackable')
        ->name('trackables.records.create');
    Route::post('/trackables/{trackable}/records', [TrackableController::class, 'storeRecord'])
        ->can('own', 'trackable')
        ->name('trackables.records.store');
    Route::get('/trackables/{trackable}/records/{record}/edit', [TrackableController::class, 'editRecord'])
        ->can('own', 'trackable')
        ->name('trackables.records.edit');
    Route::put('/trackables/{trackable}/records/{record}', [TrackableController::class, 'updateRecord'])
        ->can('own', 'trackable')
        ->name('trackables.records.update');
    Route::delete('/trackables/{trackable}/records/{record}', [TrackableController::class, 'destroyRecord'])
        ->can('own', 'trackable')
        ->name('trackables.records.destroy');
    Route::get('/trackables/{trackable}/statistics', [TrackableController::class, 'statistics'])
        ->can('own', 'trackable')
        ->name('trackables.statistics');
    Route::post('/trackables/{trackable}/statistics/graphs', [TrackableController::class, 'storeGraph'])
        ->can('own', 'trackable')
        ->name('trackables.statistics.graphs.store');
    Route::get('/trackables/{trackable}/statistics/graphs/{graph}/edit', [TrackableController::class, 'editGraph'])
        ->can('own', 'trackable')
        ->name('trackables.statistics.graphs.edit');
    Route::put('/trackables/{trackable}/statistics/graphs/{graph}', [TrackableController::class, 'updateGraph'])
        ->can('own', 'trackable')
        ->name('trackables.statistics.graphs.update');
    Route::delete('/trackables/{trackable}/statistics/graphs/{graph}', [TrackableController::class, 'destroyGraph'])
        ->can('own', 'trackable')
        ->name('trackables.statistics.graphs.destroy');
    Route::get('/trackables/{trackable}', [TrackableController::class, 'show'])
        ->can('own', 'trackable')
        ->name('trackables.show');
});
