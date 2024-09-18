<?php

use Illuminate\Support\Facades\Artisan;

Schedule::command('otps:delete-expired')->everyMinute();
