<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Otp;
use Carbon\Carbon;

class DeleteExpiredOtps extends Command
{
    protected $signature = 'otps:delete-expired';
    protected $description = 'Delete expired OTPs';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $now = Carbon::now();
        $deletedRows = Otp::where('expires_at', '<', $now)->delete();

        $this->info($deletedRows . ' expired OTP(s) deleted.');
    }
}
