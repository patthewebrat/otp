<?php

namespace Tests\Feature\OTP;

use App\Models\OTP;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OTPCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_exists_true_for_valid_otp(): void
    {
        OTP::factory()->create(['token' => 'check-token']);

        $this->getJson('/api/check/check-token')
            ->assertOk()
            ->assertJson(['exists' => true]);
    }

    public function test_returns_exists_false_for_missing_token(): void
    {
        $this->getJson('/api/check/nonexistent')
            ->assertOk()
            ->assertJson(['exists' => false]);
    }

    public function test_returns_exists_false_for_expired_token(): void
    {
        OTP::factory()->expired()->create(['token' => 'expired-check']);

        $this->getJson('/api/check/expired-check')
            ->assertOk()
            ->assertJson(['exists' => false]);
    }

    public function test_does_not_delete_otp(): void
    {
        OTP::factory()->create(['token' => 'persistent-token']);

        $this->getJson('/api/check/persistent-token');

        $this->assertDatabaseHas('otps', ['token' => 'persistent-token']);
    }

    public function test_does_not_expose_password_or_iv(): void
    {
        OTP::factory()->create(['token' => 'safe-token']);

        $response = $this->getJson('/api/check/safe-token');

        $response->assertJsonMissing(['password' => true])
            ->assertJsonMissing(['iv' => true])
            ->assertJsonMissing(['encryptedPassword' => true]);
    }

    public function test_multiple_checks_do_not_affect_otp(): void
    {
        OTP::factory()->create(['token' => 'multi-check']);

        $this->getJson('/api/check/multi-check')->assertJson(['exists' => true]);
        $this->getJson('/api/check/multi-check')->assertJson(['exists' => true]);
        $this->getJson('/api/check/multi-check')->assertJson(['exists' => true]);

        $this->assertDatabaseHas('otps', ['token' => 'multi-check']);
    }
}
