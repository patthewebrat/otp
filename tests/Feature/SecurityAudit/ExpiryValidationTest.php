<?php

namespace Tests\Feature\SecurityAudit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpiryValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_rejects_expiry_exceeding_30_days(): void
    {
        $response = $this->postJson('/api/create', [
            'token' => 'test-token',
            'encryptedPassword' => 'encrypted-data',
            'iv' => 'iv-value',
            'expiry' => 43201, // 30 days + 1 minute
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('expiry');
    }

    public function test_otp_accepts_max_30_day_expiry(): void
    {
        $response = $this->postJson('/api/create', [
            'token' => 'test-token',
            'encryptedPassword' => 'encrypted-data',
            'iv' => 'iv-value',
            'expiry' => 43200, // Exactly 30 days
        ]);

        $response->assertStatus(200);
    }

    public function test_otp_rejects_absurd_expiry(): void
    {
        $response = $this->postJson('/api/create', [
            'token' => 'test-token',
            'encryptedPassword' => 'encrypted-data',
            'iv' => 'iv-value',
            'expiry' => 999999999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('expiry');
    }

    public function test_file_rejects_expiry_exceeding_30_days(): void
    {
        $response = $this->postJson('/api/file/create', [
            'token' => 'test-token',
            'encryptedFile' => \Illuminate\Http\UploadedFile::fake()->create('test.bin', 100),
            'fileName' => 'SGVsbG8',
            'fileSize' => '100',
            'iv_file' => 'abc123',
            'iv_name' => 'def456',
            'expiry' => 43201,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('expiry');
    }

    public function test_file_accepts_max_30_day_expiry(): void
    {
        $response = $this->postJson('/api/file/create', [
            'token' => 'test-token',
            'encryptedFile' => \Illuminate\Http\UploadedFile::fake()->create('test.bin', 100),
            'fileName' => 'SGVsbG8',
            'fileSize' => '100',
            'iv_file' => 'abc123',
            'iv_name' => 'def456',
            'expiry' => 43200,
        ]);

        $response->assertJsonMissingValidationErrors('expiry');
    }
}
