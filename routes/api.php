<?php

use App\Http\Controllers\OTPController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/create', [OTPController::class, 'create']);
Route::get('/{combinedKey}', [OTPController::class, 'show']);
Route::get('/check/{combinedKey}', [OTPController::class, 'check']);
