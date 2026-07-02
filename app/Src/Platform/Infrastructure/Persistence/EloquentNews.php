<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class EloquentNews extends Model {
    protected $table = 'news';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'author_id',
        'status',
        'approved_by',
        'approved_at',
        'moderation_notes',
        'version_history',
        'is_active',
        'published_at',
        'scheduled_at',
        'archive_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'version_history' => 'array',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'archive_at' => 'datetime',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function author(): BelongsTo {
        return $this->belongsTo('App\Modules\CoreModule\Models\User', 'author_id');
    }

    public function categories(): MorphToMany {
        return $this->morphToMany(EloquentCategory::class, 'categorizable', 'categorizables');
    }

    public function tags(): MorphToMany {
        return $this->morphToMany(EloquentTag::class, 'taggable', 'taggables');
    }

    public function comments(): HasMany {
        return $this->hasMany(EloquentComment::class, 'news_id');
    }
}
