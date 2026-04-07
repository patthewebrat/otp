<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SharedFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'file_path',
        'file_name',
        'file_size',
        'iv',
        'iv_file',
        'iv_name',
        'expires_at',
        'downloaded_at',
        'key_hash',
    ];

    protected $hidden = [
        'file_path',
        'iv',
        'iv_file',
        'iv_name',
        'key_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'downloaded_at' => 'datetime',
        ];
    }
}
