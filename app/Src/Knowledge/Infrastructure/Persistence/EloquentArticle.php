<?php

declare(strict_types=1);

namespace App\Src\Knowledge\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EloquentArticle extends Model
{
    protected $table = 'knowledge_articles';

    protected $fillable = [
        'title', 'slug', 'summary', 'content', 'category_id',
        'status', 'version', 'published_at', 'expires_at',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            EloquentTag::class,
            'knowledge_article_tag',
            'article_id',
            'tag_id',
        );
    }

    public function queues(): BelongsToMany
    {
        return $this->belongsToMany(
            EloquentQueue::class,
            'knowledge_article_queue',
            'article_id',
            'queue_id',
        );
    }
}
