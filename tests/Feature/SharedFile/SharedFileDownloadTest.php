<?php

namespace Tests\Feature\SharedFile;

use App\Models\SharedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SharedFileDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
    }

    private function createFileWithStorage(array $attributes = []): SharedFile
    {
        $file = SharedFile::factory()->create($attributes);
        Storage::put($file->file_path, 'encrypted-file-contents');

        return $file;
    }

    public function test_returns_file_contents(): void
    {
        $file = $this->createFileWithStorage(['token' => 'dl-token']);

        $response = $this->get('/download-file/dl-token');

        $response->assertOk();
        $this->assertEquals('encrypted-file-contents', $response->getContent());
    }

    public function test_returns_correct_content_type(): void
    {
        $this->createFileWithStorage(['token' => 'ct-token']);

        $response = $this->get('/download-file/ct-token');

        $response->assertHeader('Content-Type', 'application/octet-stream');
    }

    public function test_returns_content_disposition_header(): void
    {
        $this->createFileWithStorage([
            'token' => 'cd-token',
            'file_name' => 'mydownload',
        ]);

        $response = $this->get('/download-file/cd-token');

        $response->assertHeader('Content-Disposition', 'attachment; filename="mydownload"');
    }

    public function test_deletes_file_from_storage_after_download(): void
    {
        $file = $this->createFileWithStorage(['token' => 'del-storage-token']);

        $this->get('/download-file/del-storage-token');

        Storage::assertMissing($file->file_path);
    }

    public function test_deletes_database_record_after_download(): void
    {
        $this->createFileWithStorage(['token' => 'del-db-token']);

        $this->get('/download-file/del-db-token');

        $this->assertDatabaseMissing('shared_files', ['token' => 'del-db-token']);
    }

    public function test_returns_404_for_missing_token(): void
    {
        $this->get('/download-file/nonexistent')->assertNotFound();
    }

    public function test_returns_404_for_expired_token(): void
    {
        $file = SharedFile::factory()->expired()->create(['token' => 'expired-dl']);
        Storage::put($file->file_path, 'data');

        $this->get('/download-file/expired-dl')->assertNotFound();
    }

    public function test_returns_404_when_file_missing_from_storage(): void
    {
        // Create DB record but no file on disk
        SharedFile::factory()->create(['token' => 'no-disk-file']);

        $this->get('/download-file/no-disk-file')->assertNotFound();
    }

    public function test_second_download_returns_404(): void
    {
        $this->createFileWithStorage(['token' => 'once-only-token']);

        $this->get('/download-file/once-only-token')->assertOk();
        $this->get('/download-file/once-only-token')->assertNotFound();
    }

    public function test_self_destruct_removes_both_file_and_record(): void
    {
        $file = $this->createFileWithStorage(['token' => 'destruct-token']);

        $this->get('/download-file/destruct-token')->assertOk();

        Storage::assertMissing($file->file_path);
        $this->assertDatabaseMissing('shared_files', ['token' => 'destruct-token']);
    }
}
