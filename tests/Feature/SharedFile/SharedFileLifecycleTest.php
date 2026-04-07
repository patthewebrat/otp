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
            'token' => 'lifecycle-file-token',
            'encryptedFile' => UploadedFile::fake()->create('test.bin', 50),
            'fileName' => 'testfile',
            'fileSize' => '51200',
            'iv_file' => 'iv-file-val',
            'iv_name' => 'iv-name-val',
            'expiry' => 60,
            'key_hash' => hash('sha256', 'test-key'),
        ])->assertOk();

        // Check exists
        $this->getJson('/api/file/check/lifecycle-file-token')
            ->assertJson(['exists' => true]);

        // Download via legacy proxy (destructive — also consumes via download())
        $this->get('/download-file/lifecycle-file-token')->assertOk();

        // Check is now gone
        $this->getJson('/api/file/check/lifecycle-file-token')
            ->assertJson(['exists' => false]);

        // Show returns 404
        $this->getJson('/api/file/lifecycle-file-token')->assertNotFound();
    }

    public function test_show_marks_file_as_consumed(): void
    {
        SharedFile::factory()->create(['token' => 'consumed-test-token']);

        // First call succeeds
        $this->getJson('/api/file/consumed-test-token')->assertOk();

        // Second call returns 404 — file is consumed
        $this->getJson('/api/file/consumed-test-token')->assertNotFound();

        // Check also returns false
        $this->getJson('/api/file/check/consumed-test-token')
            ->assertJson(['exists' => false]);
    }

    public function test_destroy_requires_valid_key_hash(): void
    {
        $keyHash = hash('sha256', 'test-key');

        SharedFile::factory()->create([
            'token' => 'destroy-auth-token',
            'key_hash' => $keyHash,
        ]);

        // Wrong key_hash is rejected
        $this->deleteJson('/api/file/destroy-auth-token', [
            'key_hash' => hash('sha256', 'wrong-key'),
        ])->assertForbidden();

        // Correct key_hash succeeds
        $this->deleteJson('/api/file/destroy-auth-token', [
            'key_hash' => $keyHash,
        ])->assertOk();

        $this->assertDatabaseMissing('shared_files', ['token' => 'destroy-auth-token']);
    }

    public function test_legacy_iv_lifecycle(): void
    {
        // Create with legacy single IV
        $this->postJson('/api/file/create', [
            'token' => 'legacy-lifecycle-tok',
            'encryptedFile' => UploadedFile::fake()->create('test.bin', 50),
            'fileName' => 'legacyfile',
            'fileSize' => '51200',
            'iv' => 'single-iv-value',
            'expiry' => 60,
        ])->assertOk();

        // Show returns IV fallbacks (and marks as consumed)
        $response = $this->getJson('/api/file/legacy-lifecycle-tok');
        $response->assertOk();
        $this->assertEquals('single-iv-value', $response->json('iv'));
        $this->assertEquals('single-iv-value', $response->json('ivFile'));
        $this->assertEquals('single-iv-value', $response->json('ivName'));

        // Second show returns 404 (consumed)
        $this->getJson('/api/file/legacy-lifecycle-tok')->assertNotFound();
    }

    public function test_file_inaccessible_after_expiry(): void
    {
        $this->freezeTime();

        $this->postJson('/api/file/create', [
            'token' => 'expiry-file-tokennn',
            'encryptedFile' => UploadedFile::fake()->create('test.bin', 50),
            'fileName' => 'expirytest',
            'fileSize' => '51200',
            'iv_file' => 'iv1',
            'iv_name' => 'iv2',
            'expiry' => 5,
        ])->assertOk();

        // Accessible now
        $this->getJson('/api/file/check/expiry-file-tokennn')
            ->assertJson(['exists' => true]);

        // Travel past expiry
        Carbon::setTestNow(now()->addMinutes(6));

        // No longer accessible
        $this->getJson('/api/file/check/expiry-file-tokennn')
            ->assertJson(['exists' => false]);

        $this->getJson('/api/file/expiry-file-tokennn')->assertNotFound();
        $this->get('/download-file/expiry-file-tokennn')->assertNotFound();
    }
}
