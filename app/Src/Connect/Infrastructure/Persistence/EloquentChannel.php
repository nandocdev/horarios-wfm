<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentChannel extends Model
{
    protected $table = 'channels';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
