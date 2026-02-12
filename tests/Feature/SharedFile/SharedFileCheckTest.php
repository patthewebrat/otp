<?php

namespace Tests\Feature\SharedFile;

use App\Models\SharedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedFileCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_exists_true_for_valid_file(): void
    {
        SharedFile::factory()->create(['token' => 'check-file-token']);

        $this->getJson('/api/file/check/check-file-token')
            ->assertOk()
            ->assertJson(['exists' => true]);
    }

    public function test_returns_exists_false_for_missing_token(): void
    {
        $this->getJson('/api/file/check/nonexistent')
            ->assertOk()
            ->assertJson(['exists' => false]);
    }

    public function test_returns_exists_false_for_expired_token(): void
    {
        SharedFile::factory()->expired()->create(['token' => 'expired-check']);

        $this->getJson('/api/file/check/expired-check')
            ->assertOk()
            ->assertJson(['exists' => false]);
    }

    public function test_does_not_delete_record(): void
    {
        SharedFile::factory()->create(['token' => 'persist-check']);

        $this->getJson('/api/file/check/persist-check');

        $this->assertDatabaseHas('shared_files', ['token' => 'persist-check']);
    }

    public function test_does_not_expose_metadata(): void
    {
        SharedFile::factory()->create(['token' => 'meta-check']);

        $response = $this->getJson('/api/file/check/meta-check');

        $response->assertOk();
        $this->assertArrayNotHasKey('fileName', $response->json());
        $this->assertArrayNotHasKey('fileSize', $response->json());
        $this->assertArrayNotHasKey('iv', $response->json());
        $this->assertArrayNotHasKey('fileUrl', $response->json());
    }
}
