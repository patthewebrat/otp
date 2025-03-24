<?php

use Illuminate\Support\Facades\Route;

Route::get('/download-file/{token}', [\App\Http\Controllers\SharedFileController::class, 'download']);

// Root path and the main pages
Route::get('/', function () {
    return view('app');
});

Route::get('/f', function () {
    return view('app');
});

Route::get('/v', function () {
    return view('app');
});
