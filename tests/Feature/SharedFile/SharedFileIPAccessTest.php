<?php

namespace Tests\Feature\SharedFile;

use Tests\TestCase;

class SharedFileIPAccessTest extends TestCase
{
    public function test_returns_allowed_true_when_no_whitelist(): void
    {
        config(['app.file_upload_whitelist' => '']);

        $this->getJson('/api/file/ip-access')
            ->assertOk()
            ->assertJson(['allowed' => true]);
    }

    public function test_returns_allowed_true_when_no_whitelist_null(): void
    {
        config(['app.file_upload_whitelist' => null]);

        $this->getJson('/api/file/ip-access')
            ->assertOk()
            ->assertJson(['allowed' => true]);
    }

    public function test_returns_allowed_true_when_ip_in_whitelist(): void
    {
        config(['app.file_upload_whitelist' => '127.0.0.1,10.0.0.1']);

        $response = $this->getJson('/api/file/ip-access');

        $response->assertOk()->assertJson(['allowed' => true]);
    }

    public function test_returns_allowed_false_when_ip_not_in_whitelist(): void
    {
        config(['app.file_upload_whitelist' => '10.0.0.50,192.168.1.100']);

        $response = $this->getJson('/api/file/ip-access');

        $response->assertOk()->assertJson(['allowed' => false]);
    }

    public function test_response_includes_ip_when_whitelist_configured(): void
    {
        config(['app.file_upload_whitelist' => '127.0.0.1']);

        $response = $this->getJson('/api/file/ip-access');

        $response->assertOk();
        $this->assertArrayHasKey('ip', $response->json());
    }
}
