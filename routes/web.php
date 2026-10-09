<?php

use App\Http\Controllers\Application\BatchController;
use App\Http\Controllers\Application\CompleteController;
use App\Http\Controllers\Application\ShowController;
use App\Http\Controllers\Application\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('', function () {
    return view('imports.upload');
});

Route::group([], function () {
    Route::post('/imports', StoreController::class);
    Route::get('/imports/{id}', ShowController::class);
    Route::post('/imports/{id}/batches', BatchController::class);
    Route::post('/imports/{id}/complete', CompleteController::class);
});
