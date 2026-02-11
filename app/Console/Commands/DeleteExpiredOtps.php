<?php

namespace App\Console\Commands;

use App\Models\OTP;
use Illuminate\Console\Command;

class DeleteExpiredOtps extends Command
{
    protected $signature = 'otps:delete-expired';

    protected $description = 'Delete expired OTPs';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $now = now();
        $deletedRows = Otp::where('expires_at', '<', $now)->delete();

        $this->info($deletedRows . ' expired OTP(s) deleted.');

        return Command::SUCCESS;
    }
}
