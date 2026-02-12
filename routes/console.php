<?php

Schedule::command('otps:delete-expired')->everyMinute();
Schedule::command('files:delete-expired')->everyMinute();
Schedule::command('cloudflare:refresh-ips')->daily();
