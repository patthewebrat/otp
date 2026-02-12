<?php

namespace Tests\Feature\OTP;

use App\Models\OTP;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OTPShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_encrypted_password_and_iv(): void
    {
        $otp = OTP::factory()->create([
            'token' => 'valid-token',
            'password' => 'secret-encrypted',
            'iv' => 'test-iv-value',
        ]);

        $response = $this->getJson('/api/valid-token');

        $response->assertOk()->assertJson([
            'encryptedPassword' => 'secret-encrypted',
            'iv' => 'test-iv-value',
        ]);
    }

    public function test_deletes_otp_after_retrieval(): void
    {
        OTP::factory()->create(['token' => 'one-time-token']);

        $this->getJson('/api/one-time-token')->assertOk();

        $this->assertDatabaseMissing('otps', ['token' => 'one-time-token']);
    }

    public function test_returns_404_for_nonexistent_token(): void
    {
        $this->getJson('/api/nonexistent-token')->assertNotFound();
    }

    public function test_returns_404_for_expired_token(): void
    {
        OTP::factory()->expired()->create(['token' => 'expired-token']);

        $this->getJson('/api/expired-token')->assertNotFound();
    }

    public function test_returns_404_on_second_access(): void
    {
        OTP::factory()->create(['token' => 'single-use-token']);

        $this->getJson('/api/single-use-token')->assertOk();
        $this->getJson('/api/single-use-token')->assertNotFound();
    }

    public function test_404_response_contains_error_message(): void
    {
        $response = $this->getJson('/api/missing-token');

        $response->assertNotFound()->assertJson([
            'error' => "Sorry, this password doesn't exist. It has either expired or has already been accessed.",
        ]);
    }

    public function test_does_not_return_expired_otp_even_if_exists_in_db(): void
    {
        OTP::factory()->create([
            'token' => 'just-expired',
            'expires_at' => now()->subSecond(),
        ]);

        $this->getJson('/api/just-expired')->assertNotFound();
    }
}
