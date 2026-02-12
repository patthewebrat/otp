<?php

namespace Tests\Feature\OTP;

use App\Models\OTP;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OTPCreateTest extends TestCase
{
    use RefreshDatabase;

    private array $validData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validData = [
            'token' => 'test-token-abc123',
            'encryptedPassword' => 'encrypted-data-here',
            'iv' => 'initialization-vector',
            'expiry' => 60,
        ];
    }

    public function test_creates_otp_with_valid_data(): void
    {
        $response = $this->postJson('/api/create', $this->validData);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('otps', [
            'token' => 'test-token-abc123',
            'password' => 'encrypted-data-here',
            'iv' => 'initialization-vector',
        ]);
    }

    public function test_creates_otp_with_correct_expiry_time(): void
    {
        $this->freezeTime();

        $this->postJson('/api/create', $this->validData);

        $otp = OTP::where('token', 'test-token-abc123')->first();
        $this->assertEquals(now()->addMinutes(60)->timestamp, $otp->expires_at->timestamp);
    }

    public function test_requires_token(): void
    {
        $data = $this->validData;
        unset($data['token']);

        $this->postJson('/api/create', $data)->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_requires_encrypted_password(): void
    {
        $data = $this->validData;
        unset($data['encryptedPassword']);

        $this->postJson('/api/create', $data)->assertUnprocessable()
            ->assertJsonValidationErrors('encryptedPassword');
    }

    public function test_requires_iv(): void
    {
        $data = $this->validData;
        unset($data['iv']);

        $this->postJson('/api/create', $data)->assertUnprocessable()
            ->assertJsonValidationErrors('iv');
    }

    public function test_requires_expiry(): void
    {
        $data = $this->validData;
        unset($data['expiry']);

        $this->postJson('/api/create', $data)->assertUnprocessable()
            ->assertJsonValidationErrors('expiry');
    }

    public function test_expiry_minimum_is_1(): void
    {
        $data = array_merge($this->validData, ['expiry' => 1]);
        $this->postJson('/api/create', $data)->assertOk();
    }

    public function test_expiry_maximum_is_43200(): void
    {
        $data = array_merge($this->validData, ['expiry' => 43200]);
        $this->postJson('/api/create', $data)->assertOk();
    }

    public function test_expiry_zero_rejected(): void
    {
        $data = array_merge($this->validData, ['expiry' => 0]);
        $this->postJson('/api/create', $data)->assertUnprocessable();
    }

    public function test_expiry_negative_rejected(): void
    {
        $data = array_merge($this->validData, ['expiry' => -1]);
        $this->postJson('/api/create', $data)->assertUnprocessable();
    }

    public function test_expiry_above_max_rejected(): void
    {
        $data = array_merge($this->validData, ['expiry' => 43201]);
        $this->postJson('/api/create', $data)->assertUnprocessable();
    }

    public function test_expiry_float_rejected(): void
    {
        $data = array_merge($this->validData, ['expiry' => 1.5]);
        $this->postJson('/api/create', $data)->assertUnprocessable();
    }

    public function test_expiry_string_rejected(): void
    {
        $data = array_merge($this->validData, ['expiry' => 'one-hour']);
        $this->postJson('/api/create', $data)->assertUnprocessable();
    }
}
