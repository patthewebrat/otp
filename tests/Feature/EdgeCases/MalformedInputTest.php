<?php

namespace Tests\Feature\EdgeCases;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MalformedInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_null_values_for_otp_fields(): void
    {
        $this->postJson('/api/create', [
            'token' => null,
            'encryptedPassword' => null,
            'iv' => null,
            'expiry' => null,
        ])->assertUnprocessable();
    }

    public function test_array_where_string_expected(): void
    {
        $this->postJson('/api/create', [
            'token' => ['not', 'a', 'string'],
            'encryptedPassword' => 'data',
            'iv' => 'iv',
            'expiry' => 60,
        ])->assertUnprocessable();
    }

    public function test_string_for_integer_field(): void
    {
        $this->postJson('/api/create', [
            'token' => 'string-test',
            'encryptedPassword' => 'data',
            'iv' => 'iv',
            'expiry' => 'not-a-number',
        ])->assertUnprocessable();
    }

    public function test_non_file_value_for_file_field(): void
    {
        Storage::fake();
        config(['app.file_upload_whitelist' => '']);

        $this->postJson('/api/file/create', [
            'token' => 'not-file-token',
            'encryptedFile' => 'not-a-file',
            'fileName' => 'testfile',
            'fileSize' => '1024',
            'iv_file' => 'iv1',
            'iv_name' => 'iv2',
            'expiry' => 60,
        ])->assertUnprocessable();
    }

    public function test_path_traversal_in_token_returns_404(): void
    {
        // Route constraint [A-Za-z0-9_-]+ should prevent path traversal
        $this->getJson('/api/../../../etc/passwd')->assertNotFound();
    }

    public function test_missing_all_iv_fields_for_file(): void
    {
        Storage::fake();
        config(['app.file_upload_whitelist' => '']);

        $this->postJson('/api/file/create', [
            'token' => 'no-iv-token',
            'encryptedFile' => UploadedFile::fake()->create('test.bin', 10),
            'fileName' => 'testfile',
            'fileSize' => '10240',
            'expiry' => 60,
        ])->assertUnprocessable();
    }

    public function test_null_values_for_file_fields(): void
    {
        Storage::fake();
        config(['app.file_upload_whitelist' => '']);

        $this->postJson('/api/file/create', [
            'token' => null,
            'encryptedFile' => null,
            'fileName' => null,
            'fileSize' => null,
            'expiry' => null,
        ])->assertUnprocessable();
    }
}
