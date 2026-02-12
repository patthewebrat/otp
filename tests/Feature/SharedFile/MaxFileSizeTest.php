<?php

namespace Tests\Feature\SharedFile;

use Tests\TestCase;

class MaxFileSizeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.file_upload_whitelist' => '']);
    }

    public function test_returns_max_size_and_formatted_size(): void
    {
        $response = $this->getJson('/api/file/max-size');

        $response->assertOk();
        $this->assertArrayHasKey('max_size', $response->json());
        $this->assertArrayHasKey('formatted_size', $response->json());
        $this->assertIsInt($response->json('max_size'));
        $this->assertIsString($response->json('formatted_size'));
    }

    public function test_max_size_is_positive(): void
    {
        $response = $this->getJson('/api/file/max-size');

        $response->assertOk();
        $this->assertGreaterThan(0, $response->json('max_size'));
    }
}
