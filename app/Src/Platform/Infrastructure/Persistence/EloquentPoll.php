<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class EloquentPoll extends Model {
    protected $table = 'polls';

    protected $fillable = [
        'question',
        'options',
        'status',
        'approved_by',
        'approved_at',
        'moderation_notes',
        'version_history',
        'is_active',
        'expires_at',
        'scheduled_at',
        'archive_at',
        'reminder_sent_at',
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'version_history' => 'array',
        'expires_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'archive_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function responses(): HasMany {
        return $this->hasMany(EloquentPollResponse::class, 'poll_id');
    }

    public function categories(): MorphToMany {
        return $this->morphToMany(EloquentCategory::class, 'categorizable', 'categorizables');
    }

    public function tags(): MorphToMany {
        return $this->morphToMany(EloquentTag::class, 'taggable', 'taggables');
    }
}
