<?php

declare(strict_types=1);

namespace App\Src\Knowledge\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentQueue extends Model
{
    protected $table = 'knowledge_queues';

    protected $fillable = ['name', 'priority', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'priority' => 'integer'];
}
