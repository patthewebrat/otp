<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OTP extends Model
{
    use HasFactory;
    protected $fillable = [
        'token',
        'password',
        'iv',
        'expires_at',
    ];

    protected $hidden = [
        'password',
        'iv',
    ];

    protected $table = 'otps';

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
