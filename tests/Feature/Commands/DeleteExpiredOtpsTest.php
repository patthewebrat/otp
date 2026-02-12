<?php

namespace Tests\Feature\Commands;

use App\Models\OTP;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteExpiredOtpsTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_expired_otps(): void
    {
        OTP::factory()->expired()->count(3)->create();
        OTP::factory()->create(); // Valid, should remain

        $this->artisan('otps:delete-expired')
            ->assertSuccessful();

        $this->assertDatabaseCount('otps', 1);
    }

    public function test_leaves_valid_otps_intact(): void
    {
        $valid = OTP::factory()->create(['token' => 'valid-otp']);
        OTP::factory()->expired()->create();

        $this->artisan('otps:delete-expired');

        $this->assertDatabaseHas('otps', ['token' => 'valid-otp']);
    }

    public function test_output_includes_count(): void
    {
        OTP::factory()->expired()->count(3)->create();

        $this->artisan('otps:delete-expired')
            ->expectsOutputToContain('3 expired OTP(s) deleted.');
    }

    public function test_returns_success_exit_code(): void
    {
        $this->artisan('otps:delete-expired')
            ->assertExitCode(0);
    }

    public function test_handles_zero_expired_gracefully(): void
    {
        OTP::factory()->create(); // Only valid OTPs

        $this->artisan('otps:delete-expired')
            ->expectsOutputToContain('0 expired OTP(s) deleted.')
            ->assertSuccessful();
    }
}
