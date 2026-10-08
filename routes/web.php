<?php

use App\Http\Controllers\Application\Import;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;


Route::prefix('/applications')->group(function () {
    Route::get('', function () {
        return view('welcome');
    });

    Route::post('store', Import::class)->name('applications.store')
//        TODO: delete after dev
        ->withoutMiddleware([PreventRequestForgery::class]);
});
