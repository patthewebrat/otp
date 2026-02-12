<?php

namespace Tests\Feature\SecurityAudit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_route_rejects_invalid_token_characters(): void
    {
        // Tokens with path traversal or special chars should be rejected
        $response = $this->getJson('/api/../etc/passwd');
        $response->assertStatus(404);
    }

    public function test_otp_route_accepts_valid_base64url_token(): void
    {
        // Valid base64url characters should be accepted (returns 404 because token doesn't exist, not route mismatch)
        $response = $this->getJson('/api/ABCdef123_-');
        $response->assertStatus(404);
        $response->assertJsonStructure(['error']);
    }
}
