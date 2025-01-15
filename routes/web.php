<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('otp');
});

Route::get('/v', function () {
    return view('otp');
});
