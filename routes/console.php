<?php

use Illuminate\Support\Facades\Artisan;

Schedule::command('otps:delete-expired')->everyMinute();
Schedule::command('files:delete-expired')->everyMinute();