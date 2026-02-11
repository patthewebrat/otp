<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\SharedFileController;
use Illuminate\Support\Facades\Route;

Route::get('/download-file/{token}', [SharedFileController::class, 'download']);

// Root path and the main pages
Route::get('/', [PageController::class, 'app']);
Route::get('/f', [PageController::class, 'app']);
Route::get('/v', [PageController::class, 'app']);
