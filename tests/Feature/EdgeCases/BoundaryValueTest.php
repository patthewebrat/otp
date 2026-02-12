<?php

namespace Tests\Feature\EdgeCases;

use App\Models\OTP;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class BoundaryValueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.file_upload_whitelist' => '']);
    }

    public function test_empty_string_token_rejected_for_otp(): void
    {
        $this->postJson('/api/create', [
            'token' => '',
            'encryptedPassword' => 'data',
            'iv' => 'iv',
            'expiry' => 60,
        ])->assertUnprocessable();
    }

    public function test_very_long_token_accepted(): void
    {
        $longToken = Str::random(255);

        $this->postJson('/api/create', [
            'token' => $longToken,
            'encryptedPassword' => 'data',
            'iv' => 'iv',
            'expiry' => 60,
        ])->assertOk();

        $this->assertDatabaseHas('otps', ['token' => $longToken]);
    }

    public function test_very_long_encrypted_password_accepted(): void
    {
        $this->postJson('/api/create', [
            'token' => 'long-pass-token',
            'encryptedPassword' => Str::random(10000),
            'iv' => 'iv',
            'expiry' => 60,
        ])->assertOk();
    }

    public function test_expiry_at_exact_minimum(): void
    {
        $this->postJson('/api/create', [
            'token' => 'min-expiry',
            'encryptedPassword' => 'data',
            'iv' => 'iv',
            'expiry' => 1,
        ])->assertOk();
    }

    public function test_expiry_at_exact_maximum(): void
    {
        $this->postJson('/api/create', [
            'token' => 'max-expiry',
            'encryptedPassword' => 'data',
            'iv' => 'iv',
            'expiry' => 43200,
        ])->assertOk();
    }

    public function test_file_at_exact_100mb_limit(): void
    {
        Storage::fake();

        $this->postJson('/api/file/create', [
            'token' => 'limit-file',
            'encryptedFile' => UploadedFile::fake()->create('exact.bin', 102400),
            'fileName' => 'exactlimit',
            'fileSize' => '104857600',
            'iv_file' => 'iv1',
            'iv_name' => 'iv2',
            'expiry' => 60,
        ])->assertOk();
    }

    public function test_file_name_with_all_valid_base64url_characters(): void
    {
        Storage::fake();

        $this->postJson('/api/file/create', [
            'token' => 'charset-test',
            'encryptedFile' => UploadedFile::fake()->create('test.bin', 10),
            'fileName' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-',
            'fileSize' => '10240',
            'iv_file' => 'iv1',
            'iv_name' => 'iv2',
            'expiry' => 60,
        ])->assertOk();
    }

    public function test_zero_expiry_rejected(): void
    {
        $this->postJson('/api/create', [
            'token' => 'zero-expiry',
            'encryptedPassword' => 'data',
            'iv' => 'iv',
            'expiry' => 0,
        ])->assertUnprocessable();
    }
}
