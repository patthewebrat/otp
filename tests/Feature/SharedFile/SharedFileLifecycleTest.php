<?php

namespace Tests\Feature\SharedFile;

use App\Models\SharedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SharedFileLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        config(['app.file_upload_whitelist' => '']);
    }

    public function test_full_lifecycle_create_check_show_download_destroy(): void
    {
        // Create
        $this->postJson('/api/file/create', [
            'token' => 'lifecycle-file',
            'encryptedFile' => UploadedFile::fake()->create('test.bin', 50),
            'fileName' => 'testfile',
            'fileSize' => '51200',
            'iv_file' => 'iv-file-val',
            'iv_name' => 'iv-name-val',
            'expiry' => 60,
        ])->assertOk();

        // Check exists
        $this->getJson('/api/file/check/lifecycle-file')
            ->assertJson(['exists' => true]);

        // Show metadata (non-destructive)
        $this->getJson('/api/file/lifecycle-file')
            ->assertOk()
            ->assertJson([
                'fileName' => 'testfile',
                'fileSize' => '51200',
            ]);

        // Download (destructive)
        $this->get('/download-file/lifecycle-file')->assertOk();

        // Check is now gone
        $this->getJson('/api/file/check/lifecycle-file')
            ->assertJson(['exists' => false]);

        // Show returns 404
        $this->getJson('/api/file/lifecycle-file')->assertNotFound();
    }

    public function test_legacy_iv_lifecycle(): void
    {
        // Create with legacy single IV
        $this->postJson('/api/file/create', [
            'token' => 'legacy-lifecycle',
            'encryptedFile' => UploadedFile::fake()->create('test.bin', 50),
            'fileName' => 'legacyfile',
            'fileSize' => '51200',
            'iv' => 'single-iv-value',
            'expiry' => 60,
        ])->assertOk();

        // Show returns IV fallbacks
        $response = $this->getJson('/api/file/legacy-lifecycle');
        $response->assertOk();
        $this->assertEquals('single-iv-value', $response->json('iv'));
        $this->assertEquals('single-iv-value', $response->json('ivFile'));
        $this->assertEquals('single-iv-value', $response->json('ivName'));

        // Download destroys
        $this->get('/download-file/legacy-lifecycle')->assertOk();
        $this->getJson('/api/file/check/legacy-lifecycle')
            ->assertJson(['exists' => false]);
    }

    public function test_file_inaccessible_after_expiry(): void
    {
        $this->freezeTime();

        $this->postJson('/api/file/create', [
            'token' => 'expiry-file',
            'encryptedFile' => UploadedFile::fake()->create('test.bin', 50),
            'fileName' => 'expirytest',
            'fileSize' => '51200',
            'iv_file' => 'iv1',
            'iv_name' => 'iv2',
            'expiry' => 5,
        ])->assertOk();

        // Accessible now
        $this->getJson('/api/file/check/expiry-file')
            ->assertJson(['exists' => true]);

        // Travel past expiry
        Carbon::setTestNow(now()->addMinutes(6));

        // No longer accessible
        $this->getJson('/api/file/check/expiry-file')
            ->assertJson(['exists' => false]);

        $this->getJson('/api/file/expiry-file')->assertNotFound();
        $this->get('/download-file/expiry-file')->assertNotFound();
    }
}
