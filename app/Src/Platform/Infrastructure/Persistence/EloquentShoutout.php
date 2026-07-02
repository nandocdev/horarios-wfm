<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class EloquentShoutout extends Model {
    protected $table = 'shoutouts';

    protected $fillable = [
        'employee_id',
        'message',
        'status',
        'approved_by',
        'approved_at',
        'moderation_notes',
        'version_history',
        'is_active',
        'scheduled_at',
        'archive_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'version_history' => 'array',
        'scheduled_at' => 'datetime',
        'archive_at' => 'datetime',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function categories(): MorphToMany {
        return $this->morphToMany(EloquentCategory::class, 'categorizable', 'categorizables');
    }

    public function tags(): MorphToMany {
        return $this->morphToMany(EloquentTag::class, 'taggable', 'taggables');
    }

    public function reactions(): HasMany {
        return $this->hasMany(EloquentReaction::class, 'shoutout_id');
    }
}
