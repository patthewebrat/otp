<?php

namespace Tests\Feature\SecurityAudit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_routes_are_rate_limited(): void
    {
        // Make 51 requests rapidly - the 51st should be throttled
        for ($i = 0; $i < 50; $i++) {
            $this->postJson('/api/create', [
                'token' => 'token-' . $i,
                'encryptedPassword' => 'encrypted',
                'iv' => 'iv-value',
                'expiry' => 60,
            ]);
        }

        $response = $this->postJson('/api/create', [
            'token' => 'token-overflow',
            'encryptedPassword' => 'encrypted',
            'iv' => 'iv-value',
            'expiry' => 60,
        ]);

        $response->assertStatus(429);
    }

    public function test_rate_limit_headers_are_present(): void
    {
        $response = $this->postJson('/api/create', [
            'token' => 'test-token',
            'encryptedPassword' => 'encrypted',
            'iv' => 'iv-value',
            'expiry' => 60,
        ]);

        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }
}
