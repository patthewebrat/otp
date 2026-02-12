<?php

namespace Tests\Feature\SharedFile;

use App\Models\SharedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SharedFileCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        config(['app.file_upload_whitelist' => '']);
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'token' => 'file-token-123',
            'encryptedFile' => UploadedFile::fake()->create('encrypted.bin', 100),
            'fileName' => 'myfile-name_123',
            'fileSize' => '102400',
            'iv_file' => 'iv-for-file',
            'iv_name' => 'iv-for-name',
            'expiry' => 60,
        ], $overrides);
    }

    public function test_creates_shared_file_with_valid_data(): void
    {
        $response = $this->postJson('/api/file/create', $this->validData());

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('shared_files', [
            'token' => 'file-token-123',
            'file_name' => 'myfile-name_123',
        ]);
    }

    public function test_stores_file_on_disk(): void
    {
        $this->postJson('/api/file/create', $this->validData());

        $file = SharedFile::where('token', 'file-token-123')->first();
        Storage::assertExists($file->file_path);
    }

    public function test_requires_token(): void
    {
        $data = $this->validData();
        unset($data['token']);

        $this->postJson('/api/file/create', $data)->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_requires_encrypted_file(): void
    {
        $data = $this->validData();
        unset($data['encryptedFile']);

        $this->postJson('/api/file/create', $data)->assertUnprocessable()
            ->assertJsonValidationErrors('encryptedFile');
    }

    public function test_requires_file_name(): void
    {
        $data = $this->validData();
        unset($data['fileName']);

        $this->postJson('/api/file/create', $data)->assertUnprocessable()
            ->assertJsonValidationErrors('fileName');
    }

    public function test_requires_file_size(): void
    {
        $data = $this->validData();
        unset($data['fileSize']);

        $this->postJson('/api/file/create', $data)->assertUnprocessable()
            ->assertJsonValidationErrors('fileSize');
    }

    public function test_requires_expiry(): void
    {
        $data = $this->validData();
        unset($data['expiry']);

        $this->postJson('/api/file/create', $data)->assertUnprocessable()
            ->assertJsonValidationErrors('expiry');
    }

    public function test_accepts_separate_iv_fields(): void
    {
        $this->postJson('/api/file/create', $this->validData([
            'iv_file' => 'file-iv',
            'iv_name' => 'name-iv',
        ]))->assertOk();

        $file = SharedFile::first();
        $this->assertEquals('file-iv', $file->iv_file);
        $this->assertEquals('name-iv', $file->iv_name);
    }

    public function test_accepts_legacy_single_iv(): void
    {
        $data = $this->validData();
        unset($data['iv_file'], $data['iv_name']);
        $data['iv'] = 'legacy-iv';

        $this->postJson('/api/file/create', $data)->assertOk();

        $file = SharedFile::first();
        $this->assertEquals('legacy-iv', $file->iv);
    }

    public function test_rejects_missing_all_iv_fields(): void
    {
        $data = $this->validData();
        unset($data['iv_file'], $data['iv_name']);

        $this->postJson('/api/file/create', $data)->assertUnprocessable();
    }

    public function test_file_name_rejects_dots(): void
    {
        $this->postJson('/api/file/create', $this->validData([
            'fileName' => 'file.txt',
        ]))->assertUnprocessable()->assertJsonValidationErrors('fileName');
    }

    public function test_file_name_rejects_spaces(): void
    {
        $this->postJson('/api/file/create', $this->validData([
            'fileName' => 'my file',
        ]))->assertUnprocessable()->assertJsonValidationErrors('fileName');
    }

    public function test_file_name_rejects_special_chars(): void
    {
        $this->postJson('/api/file/create', $this->validData([
            'fileName' => 'file@#$%',
        ]))->assertUnprocessable()->assertJsonValidationErrors('fileName');
    }

    public function test_file_name_accepts_base64url_chars(): void
    {
        $this->postJson('/api/file/create', $this->validData([
            'fileName' => 'ABCDEFxyz0123456789_-',
        ]))->assertOk();
    }

    public function test_expiry_boundary_values(): void
    {
        $this->postJson('/api/file/create', $this->validData(['expiry' => 1]))->assertOk();
    }

    public function test_expiry_above_max_rejected(): void
    {
        $this->postJson('/api/file/create', $this->validData(['expiry' => 43201]))
            ->assertUnprocessable();
    }

    public function test_file_size_over_100mb_rejected(): void
    {
        $this->postJson('/api/file/create', $this->validData([
            'encryptedFile' => UploadedFile::fake()->create('big.bin', 102401),
        ]))->assertUnprocessable()->assertJsonValidationErrors('encryptedFile');
    }
}
