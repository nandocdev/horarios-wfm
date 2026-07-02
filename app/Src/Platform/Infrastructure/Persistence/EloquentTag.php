<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class EloquentTag extends Model {
    protected $table = 'tags';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function news(): MorphToMany {
        return $this->morphedByMany(EloquentNews::class, 'taggable', 'taggables');
    }

    public function polls(): MorphToMany {
        return $this->morphedByMany(EloquentPoll::class, 'taggable', 'taggables');
    }

    public function shoutouts(): MorphToMany {
        return $this->morphedByMany(EloquentShoutout::class, 'taggable', 'taggables');
    }
}
