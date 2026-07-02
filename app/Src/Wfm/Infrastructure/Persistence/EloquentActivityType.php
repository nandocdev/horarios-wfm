<?php

declare(strict_types=1);

namespace App\Src\Wfm\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentActivityType extends Model
{
    protected $table = 'activity_types';

    protected $fillable = ['name', 'color', 'is_productive', 'is_paid'];

    protected $casts = [
        'is_productive' => 'boolean',
        'is_paid' => 'boolean',
    ];
}
