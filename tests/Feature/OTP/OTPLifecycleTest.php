<?php

namespace Tests\Feature\OTP;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OTPLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_lifecycle_create_check_show_destroy(): void
    {
        // Create
        $this->postJson('/api/create', [
            'token' => 'lifecycle-token',
            'encryptedPassword' => 'my-secret',
            'iv' => 'my-iv',
            'expiry' => 60,
        ])->assertOk();

        // Check exists
        $this->getJson('/api/check/lifecycle-token')
            ->assertJson(['exists' => true]);

        // Show (retrieves and destroys)
        $this->getJson('/api/lifecycle-token')
            ->assertOk()
            ->assertJson([
                'encryptedPassword' => 'my-secret',
                'iv' => 'my-iv',
            ]);

        // Check is now gone
        $this->getJson('/api/check/lifecycle-token')
            ->assertJson(['exists' => false]);

        // Show returns 404
        $this->getJson('/api/lifecycle-token')
            ->assertNotFound();
    }

    public function test_created_otp_data_matches_on_retrieval(): void
    {
        $this->postJson('/api/create', [
            'token' => 'data-match-token',
            'encryptedPassword' => 'encrypted-payload-xyz',
            'iv' => 'iv-value-123',
            'expiry' => 30,
        ])->assertOk();

        $response = $this->getJson('/api/data-match-token');

        $response->assertOk()->assertJson([
            'encryptedPassword' => 'encrypted-payload-xyz',
            'iv' => 'iv-value-123',
        ]);
    }

    public function test_otp_inaccessible_after_expiry(): void
    {
        $this->freezeTime();

        $this->postJson('/api/create', [
            'token' => 'expiry-test',
            'encryptedPassword' => 'secret',
            'iv' => 'iv',
            'expiry' => 5,
        ])->assertOk();

        // Accessible now
        $this->getJson('/api/check/expiry-test')
            ->assertJson(['exists' => true]);

        // Travel past expiry
        Carbon::setTestNow(now()->addMinutes(6));

        // No longer accessible
        $this->getJson('/api/check/expiry-test')
            ->assertJson(['exists' => false]);

        $this->getJson('/api/expiry-test')
            ->assertNotFound();
    }
}
