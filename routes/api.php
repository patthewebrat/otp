<?php

use App\Http\Controllers\OTPController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/otp/create', [OTPController::class, 'create']);
Route::get('/otp/{combinedKey}', [OTPController::class, 'show']);
Route::get('/otp/check/{combinedKey}', [OTPController::class, 'check']);
