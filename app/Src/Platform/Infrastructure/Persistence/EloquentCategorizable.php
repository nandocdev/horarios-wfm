<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentCategorizable extends Model {
    protected $table = 'categorizables';

    protected $fillable = [
        'category_id',
        'categorizable_type',
        'categorizable_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
