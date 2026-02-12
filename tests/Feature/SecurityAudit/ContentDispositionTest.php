<?php

namespace Tests\Feature\SecurityAudit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentDispositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_filename_with_special_characters_is_rejected(): void
    {
        $response = $this->postJson('/api/file/create', [
            'token' => 'test-token-123',
            'encryptedFile' => \Illuminate\Http\UploadedFile::fake()->create('test.bin', 100),
            'fileName' => 'malicious"file\r\nInjected-Header: value',
            'fileSize' => '100',
            'iv_file' => 'abc123',
            'iv_name' => 'def456',
            'expiry' => 60,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('fileName');
    }

    public function test_valid_base64url_filename_is_accepted(): void
    {
        $response = $this->postJson('/api/file/create', [
            'token' => 'test-token-456',
            'encryptedFile' => \Illuminate\Http\UploadedFile::fake()->create('test.bin', 100),
            'fileName' => 'SGVsbG8gV29ybGQ',
            'fileSize' => '100',
            'iv_file' => 'abc123',
            'iv_name' => 'def456',
            'expiry' => 60,
        ]);

        // Should not fail on fileName validation (may fail on storage but not validation)
        $response->assertJsonMissingValidationErrors('fileName');
    }

    public function test_filename_with_dots_in_base64url_is_rejected(): void
    {
        $response = $this->postJson('/api/file/create', [
            'token' => 'test-token-789',
            'encryptedFile' => \Illuminate\Http\UploadedFile::fake()->create('test.bin', 100),
            'fileName' => 'file.name.with.dots',
            'fileSize' => '100',
            'iv_file' => 'abc123',
            'iv_name' => 'def456',
            'expiry' => 60,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('fileName');
    }
}
