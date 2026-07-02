<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentCaseSubtype extends Model
{
    protected $table = 'case_subtypes';

    protected $fillable = [
        'code',
        'queue_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
