<?php

namespace Tests\Feature\Commands;

use App\Models\SharedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteExpiredFilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
    }

    public function test_deletes_expired_files_from_storage_and_database(): void
    {
        $expired = SharedFile::factory()->expired()->create();
        Storage::put($expired->file_path, 'data');

        $this->artisan('files:delete-expired')->assertSuccessful();

        Storage::assertMissing($expired->file_path);
        $this->assertDatabaseMissing('shared_files', ['id' => $expired->id]);
    }

    public function test_leaves_non_expired_files_intact(): void
    {
        $valid = SharedFile::factory()->create(['token' => 'valid-file']);
        Storage::put($valid->file_path, 'data');

        $expired = SharedFile::factory()->expired()->create();
        Storage::put($expired->file_path, 'data');

        $this->artisan('files:delete-expired');

        $this->assertDatabaseHas('shared_files', ['token' => 'valid-file']);
        Storage::assertExists($valid->file_path);
    }

    public function test_handles_missing_file_on_disk_gracefully(): void
    {
        $expired = SharedFile::factory()->expired()->create();
        // Don't put file on disk — it's missing

        $this->artisan('files:delete-expired')->assertSuccessful();

        $this->assertDatabaseMissing('shared_files', ['id' => $expired->id]);
    }

    public function test_output_includes_count(): void
    {
        SharedFile::factory()->expired()->count(2)->create();

        $this->artisan('files:delete-expired')
            ->expectsOutputToContain('Deleted 2 expired shared files.');
    }

    public function test_returns_success_exit_code(): void
    {
        $this->artisan('files:delete-expired')
            ->assertExitCode(0);
    }

    public function test_handles_zero_expired_gracefully(): void
    {
        SharedFile::factory()->create(); // Only valid files

        $this->artisan('files:delete-expired')
            ->expectsOutputToContain('Deleted 0 expired shared files.')
            ->assertSuccessful();
    }
}
