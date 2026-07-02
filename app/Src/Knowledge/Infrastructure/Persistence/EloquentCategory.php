<?php

declare(strict_types=1);

namespace App\Src\Knowledge\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentCategory extends Model
{
    protected $table = 'knowledge_categories';

    protected $fillable = ['name', 'description'];
}
