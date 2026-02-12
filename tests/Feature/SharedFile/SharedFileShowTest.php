<?php

namespace Tests\Feature\SharedFile;

use App\Models\SharedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedFileShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_file_metadata(): void
    {
        SharedFile::factory()->create([
            'token' => 'show-token',
            'file_name' => 'testfile',
            'file_size' => '2048',
            'iv' => 'file-iv',
            'iv_file' => 'iv-for-file',
            'iv_name' => 'iv-for-name',
        ]);

        $response = $this->getJson('/api/file/show-token');

        $response->assertOk()->assertJson([
            'fileName' => 'testfile',
            'fileSize' => '2048',
            'iv' => 'file-iv',
            'ivFile' => 'iv-for-file',
            'ivName' => 'iv-for-name',
        ]);
    }

    public function test_file_url_contains_download_path(): void
    {
        SharedFile::factory()->create(['token' => 'url-token']);

        $response = $this->getJson('/api/file/url-token');

        $response->assertOk();
        $this->assertStringContains('/download-file/url-token', $response->json('fileUrl'));
    }

    public function test_returns_404_for_missing_token(): void
    {
        $this->getJson('/api/file/nonexistent')->assertNotFound();
    }

    public function test_returns_404_for_expired_token(): void
    {
        SharedFile::factory()->expired()->create(['token' => 'expired-file']);

        $this->getJson('/api/file/expired-file')->assertNotFound();
    }

    public function test_does_not_delete_record(): void
    {
        SharedFile::factory()->create(['token' => 'persistent-file']);

        $this->getJson('/api/file/persistent-file');

        $this->assertDatabaseHas('shared_files', ['token' => 'persistent-file']);
    }

    public function test_iv_file_falls_back_to_iv_when_null(): void
    {
        SharedFile::factory()->legacyIv()->create([
            'token' => 'legacy-token',
            'iv' => 'shared-iv',
        ]);

        $response = $this->getJson('/api/file/legacy-token');

        $response->assertOk();
        $this->assertEquals('shared-iv', $response->json('ivFile'));
        $this->assertEquals('shared-iv', $response->json('ivName'));
    }

    public function test_does_not_expose_file_path(): void
    {
        SharedFile::factory()->create(['token' => 'safe-file']);

        $response = $this->getJson('/api/file/safe-file');

        $response->assertOk();
        $this->assertArrayNotHasKey('file_path', $response->json());
        $this->assertArrayNotHasKey('filePath', $response->json());
    }

    public function test_404_response_contains_error_message(): void
    {
        $response = $this->getJson('/api/file/missing');

        $response->assertNotFound()->assertJson([
            'error' => "Sorry, this file doesn't exist. It has either expired or has already been accessed.",
        ]);
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'."
        );
    }
}
