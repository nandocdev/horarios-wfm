<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent;

use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\Traits\Auditable;
use App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent\Policies\NewsPolicy;
use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class NewsModel extends Model implements HasMedia
{
    use Auditable, InteractsWithMedia;

    protected $table = 'news';

    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'author_id',
        'is_active', 'published_at', 'scheduled_at', 'archive_at',
        'status', 'approved_by', 'approved_at', 'moderation_notes',
        'version_history',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'archive_at' => 'datetime',
        'approved_at' => 'datetime',
        'version_history' => 'array',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(
            CategoryModel::class,
            'categorizable',
            'categorizables',
        );
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(
            TagModel::class,
            'taggable',
            'taggables',
        );
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommentModel::class, 'news_id');
    }

    public function mentions(): MorphMany
    {
        return $this->morphMany(MentionModel::class, 'mentionable');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeDraft(Builder $query): void
    {
        $query->where('status', 'draft');
    }

    public function scopePendingReview(Builder $query): void
    {
        $query->where('status', 'pending_review');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published');
    }

    public function scopeArchived(Builder $query): void
    {
        $query->where('status', 'archived');
    }

    public function scopeScheduledToPublish(Builder $query): void
    {
        $query->where('status', 'published')
            ->where('scheduled_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '>', now());
            });
    }

    public function scopeScheduledToArchive(Builder $query): void
    {
        $query->where('status', '!=', 'archived')
            ->where('archive_at', '<=', now());
    }
}
