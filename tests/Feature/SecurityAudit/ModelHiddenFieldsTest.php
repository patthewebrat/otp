<?php

namespace Tests\Feature\SecurityAudit;

use App\Models\OTP;
use App\Models\SharedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelHiddenFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_model_hides_sensitive_fields_in_json(): void
    {
        $otp = OTP::create([
            'token' => 'test-token',
            'password' => 'encrypted-data',
            'iv' => 'iv-value',
            'expires_at' => now()->addHour(),
        ]);

        $json = $otp->toArray();

        $this->assertArrayNotHasKey('password', $json, 'password should be hidden in JSON');
        $this->assertArrayNotHasKey('iv', $json, 'iv should be hidden in JSON');
        $this->assertArrayHasKey('token', $json, 'token should still be visible');
    }

    public function test_shared_file_model_hides_sensitive_fields_in_json(): void
    {
        $file = SharedFile::create([
            'token' => 'test-token',
            'file_path' => 'encrypted-files/test.bin',
            'file_name' => 'encrypted-name',
            'file_size' => '100',
            'iv' => 'iv-value',
            'iv_file' => 'iv-file-value',
            'iv_name' => 'iv-name-value',
            'expires_at' => now()->addHour(),
        ]);

        $json = $file->toArray();

        $this->assertArrayNotHasKey('file_path', $json, 'file_path should be hidden in JSON');
        $this->assertArrayNotHasKey('iv', $json, 'iv should be hidden in JSON');
        $this->assertArrayNotHasKey('iv_file', $json, 'iv_file should be hidden in JSON');
        $this->assertArrayNotHasKey('iv_name', $json, 'iv_name should be hidden in JSON');
        $this->assertArrayHasKey('token', $json, 'token should still be visible');
    }
}
