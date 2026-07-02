<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentCallQueue extends Model
{
    protected $table = 'call_queues';

    protected $fillable = [
        'name',
        'description',
        'extension',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
