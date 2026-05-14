<?php

declare(strict_types=1);

namespace App\Modules\DocumentationModule\Models;

use App\Modules\CommunicationsModule\Models\Category;
use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Article extends Model
{
    protected $table = 'documentation_articles';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'is_published',
        'author_id',
        'view_count',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'view_count' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });
    }

    /**
     * Relación con el autor (Usuario).
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Relación polimórfica con categorías.
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'categorizables');
    }

    /**
     * Scope para artículos publicados.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope para ordenar.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }
}
