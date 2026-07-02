<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class EloquentCategory extends Model {
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function news(): MorphToMany {
        return $this->morphedByMany(EloquentNews::class, 'categorizable', 'categorizables');
    }

    public function polls(): MorphToMany {
        return $this->morphedByMany(EloquentPoll::class, 'categorizable', 'categorizables');
    }

    public function shoutouts(): MorphToMany {
        return $this->morphedByMany(EloquentShoutout::class, 'categorizable', 'categorizables');
    }
}
