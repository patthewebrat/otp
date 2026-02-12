<?php

namespace Database\Factories;

use App\Models\OTP;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OTPFactory extends Factory
{
    protected $model = OTP::class;

    public function definition(): array
    {
        return [
            'token' => Str::random(32),
            'password' => Str::random(64),
            'iv' => Str::random(24),
            'expires_at' => now()->addHour(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subHour(),
        ]);
    }

    public function expiresIn(int $minutes): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->addMinutes($minutes),
        ]);
    }
}
