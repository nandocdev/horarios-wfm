<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentPasswordReset extends Model
{
    protected $table = 'password_reset_tokens';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'token',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
