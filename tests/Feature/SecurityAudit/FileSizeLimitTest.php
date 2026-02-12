<?php

namespace Tests\Feature\SecurityAudit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileSizeLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_upload_rejects_oversized_files(): void
    {
        // Create a file larger than 100MB (102400 KB)
        $file = UploadedFile::fake()->create('large.bin', 102401);

        $response = $this->postJson('/api/file/create', [
            'token' => 'test-token',
            'encryptedFile' => $file,
            'fileName' => 'SGVsbG8',
            'fileSize' => '102401000',
            'iv_file' => 'abc123',
            'iv_name' => 'def456',
            'expiry' => 60,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('encryptedFile');
    }

    public function test_file_upload_accepts_files_within_limit(): void
    {
        $file = UploadedFile::fake()->create('small.bin', 100);

        $response = $this->postJson('/api/file/create', [
            'token' => 'test-token',
            'encryptedFile' => $file,
            'fileName' => 'SGVsbG8',
            'fileSize' => '100000',
            'iv_file' => 'abc123',
            'iv_name' => 'def456',
            'expiry' => 60,
        ]);

        $response->assertJsonMissingValidationErrors('encryptedFile');
    }
}
