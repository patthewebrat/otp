<?php

use Illuminate\Support\Facades\Route;

Route::get('/otp', function () {
    return view('otp');
});
