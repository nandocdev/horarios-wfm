<?php

declare(strict_types=1);

namespace App\Src\Knowledge\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentTag extends Model
{
    protected $table = 'knowledge_tags';

    protected $fillable = ['name'];
}
