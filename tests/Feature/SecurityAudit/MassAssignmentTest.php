<?php

namespace Tests\Feature\SecurityAudit;

use App\Models\OTP;
use App\Models\SharedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_model_has_fillable_not_guarded(): void
    {
        $otp = new OTP;

        $this->assertNotEmpty($otp->getFillable(), 'OTP model should have explicit $fillable');
        $this->assertNotEmpty($otp->getGuarded(), 'OTP model should not have empty $guarded');
    }

    public function test_otp_model_prevents_id_mass_assignment(): void
    {
        $otp = OTP::create([
            'id' => 999,
            'token' => 'test-token',
            'password' => 'encrypted-data',
            'iv' => 'iv-value',
            'expires_at' => now()->addHour(),
        ]);

        $this->assertNotEquals(999, $otp->id, 'OTP should not allow mass-assigning id');
    }

    public function test_shared_file_model_has_fillable_not_guarded(): void
    {
        $file = new SharedFile;

        $this->assertNotEmpty($file->getFillable(), 'SharedFile model should have explicit $fillable');
        $this->assertNotEmpty($file->getGuarded(), 'SharedFile model should not have empty $guarded');
    }

    public function test_shared_file_model_prevents_id_mass_assignment(): void
    {
        $file = SharedFile::create([
            'id' => 999,
            'token' => 'test-token',
            'file_path' => 'encrypted-files/test.bin',
            'file_name' => 'encrypted-name',
            'file_size' => '100',
            'iv' => 'iv-value',
            'iv_file' => 'iv-file-value',
            'iv_name' => 'iv-name-value',
            'expires_at' => now()->addHour(),
        ]);

        $this->assertNotEquals(999, $file->id, 'SharedFile should not allow mass-assigning id');
    }
}
