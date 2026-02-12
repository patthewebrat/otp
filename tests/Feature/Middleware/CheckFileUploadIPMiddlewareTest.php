<?php

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckFileUploadIPMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_all_requests_when_no_whitelist(): void
    {
        config(['app.file_upload_whitelist' => '']);

        $this->getJson('/api/file/max-size')->assertOk();
    }

    public function test_blocks_request_when_ip_not_in_whitelist(): void
    {
        config(['app.file_upload_whitelist' => '10.0.0.50,192.168.1.100']);

        $response = $this->getJson('/api/file/max-size');

        $response->assertForbidden()->assertJson([
            'error' => 'File upload is not available from your location.',
        ]);
    }

    public function test_allows_request_when_ip_in_whitelist(): void
    {
        config(['app.file_upload_whitelist' => '127.0.0.1']);

        $this->getJson('/api/file/max-size')->assertOk();
    }

    public function test_blocks_file_create_when_ip_not_in_whitelist(): void
    {
        Storage::fake();
        config(['app.file_upload_whitelist' => '10.0.0.50']);

        $this->postJson('/api/file/create', [
            'token' => 'blocked-token',
            'encryptedFile' => UploadedFile::fake()->create('test.bin', 50),
            'fileName' => 'testfile',
            'fileSize' => '51200',
            'iv_file' => 'iv1',
            'iv_name' => 'iv2',
            'expiry' => 60,
        ])->assertForbidden();
    }

    public function test_file_check_not_blocked_by_middleware(): void
    {
        config(['app.file_upload_whitelist' => '10.0.0.50']);

        // file/check is NOT behind the middleware - should not be blocked
        $this->getJson('/api/file/check/some-token')->assertOk();
    }

    public function test_file_show_not_blocked_by_middleware(): void
    {
        config(['app.file_upload_whitelist' => '10.0.0.50']);

        // file/{token} is NOT behind the middleware - should return 404 (not 403)
        $this->getJson('/api/file/some-token')->assertNotFound();
    }

    public function test_ip_access_endpoint_not_blocked_by_middleware(): void
    {
        config(['app.file_upload_whitelist' => '10.0.0.50']);

        // file/ip-access is NOT behind the middleware
        $this->getJson('/api/file/ip-access')->assertOk();
    }

    public function test_whitelist_with_spaces_still_works(): void
    {
        config(['app.file_upload_whitelist' => ' 127.0.0.1 , 10.0.0.1 ']);

        $this->getJson('/api/file/max-size')->assertOk();
    }
}
