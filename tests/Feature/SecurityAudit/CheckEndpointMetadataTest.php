<?php

namespace Tests\Feature\SecurityAudit;

use App\Models\OTP;
use App\Models\SharedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckEndpointMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_check_only_returns_exists_flag(): void
    {
        OTP::create([
            'token' => 'test-token-abc',
            'password' => 'encrypted-data',
            'iv' => 'secret-iv',
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->getJson('/api/check/test-token-abc');

        $response->assertStatus(200);
        $response->assertJson(['exists' => true]);

        // Should NOT contain sensitive fields
        $response->assertJsonMissing(['password']);
        $response->assertJsonMissing(['iv']);
    }

    public function test_file_check_only_returns_exists_flag(): void
    {
        SharedFile::create([
            'token' => 'file-token-abc',
            'file_path' => 'encrypted-files/test.bin',
            'file_name' => 'encrypted-name',
            'file_size' => '100',
            'iv' => 'secret-iv',
            'iv_file' => 'secret-iv-file',
            'iv_name' => 'secret-iv-name',
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->getJson('/api/file/check/file-token-abc');

        $response->assertStatus(200);
        $response->assertJson(['exists' => true]);

        // Should NOT contain file metadata or IVs
        $data = $response->json();
        $this->assertArrayNotHasKey('fileName', $data);
        $this->assertArrayNotHasKey('fileSize', $data);
        $this->assertArrayNotHasKey('iv', $data);
        $this->assertArrayNotHasKey('ivFile', $data);
        $this->assertArrayNotHasKey('ivName', $data);
    }

    public function test_file_check_nonexistent_returns_exists_false(): void
    {
        $response = $this->getJson('/api/file/check/nonexistent-token');

        $response->assertStatus(200);
        $response->assertJson(['exists' => false]);
    }
}
