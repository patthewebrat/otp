<?php

use App\Http\Controllers\OTPController;
use App\Http\Controllers\SharedFileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Password OTP routes
Route::post('/create', [OTPController::class, 'create']);
Route::get('/{combinedKey}', [OTPController::class, 'show']);
Route::get('/check/{combinedKey}', [OTPController::class, 'check']);

// Shared file routes
Route::post('/file/create', [SharedFileController::class, 'create']);
Route::get('/file/max-size', [SharedFileController::class, 'getMaxFileSize']);
Route::get('/file/check/{token}', [SharedFileController::class, 'check']);
Route::get('/file/{token}', [SharedFileController::class, 'show']);
