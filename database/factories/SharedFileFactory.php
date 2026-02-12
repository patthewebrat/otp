<?php

namespace Database\Factories;

use App\Models\SharedFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SharedFileFactory extends Factory
{
    protected $model = SharedFile::class;

    public function definition(): array
    {
        return [
            'token' => Str::random(32),
            'file_path' => 'encrypted-files/' . Str::random(40),
            'file_name' => Str::random(16),
            'file_size' => '1024',
            'iv' => Str::random(24),
            'iv_file' => Str::random(24),
            'iv_name' => Str::random(24),
            'expires_at' => now()->addHour(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subHour(),
        ]);
    }

    public function legacyIv(): static
    {
        $iv = Str::random(24);

        return $this->state(fn () => [
            'iv' => $iv,
            'iv_file' => null,
            'iv_name' => null,
        ]);
    }

    public function expiresIn(int $minutes): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->addMinutes($minutes),
        ]);
    }
}
