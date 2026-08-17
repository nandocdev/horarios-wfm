<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Models;

use App\Modules\CoreModule\Models\User;
use App\Shared\Support\HasContentPublishing;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Modelo de Artículo de la Base de Conocimiento.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property string $content
 * @property int|null $category_id
 * @property string $status
 * @property int $version
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property int $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class KnowledgeArticle extends Model
{
    use HasContentPublishing;

    protected $table = 'knowledge_articles';

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'category_id',
        'status',
        'version',
        'published_at',
        'expires_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title).'-'.uniqid();
            }
        });
    }

    /**
     * Relación con la categoría.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'category_id');
    }

    /**
     * Relación con las colas asignadas.
     */
    public function queues(): BelongsToMany
    {
        return $this->belongsToMany(
            Queue::class,
            'knowledge_article_queue',
            'article_id',
            'queue_id'
        );
    }

    /**
     * Relación con las etiquetas.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            'knowledge_article_tag',
            'article_id',
            'tag_id'
        );
    }

    /**
     * Relación con el historial de versiones del contenido.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ArticleVersion::class, 'article_id');
    }

    /**
     * Relación con el autor (creador).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con el último editor (actualizador).
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Prioridad máxima de las colas asociadas.
     *
     * Usa el agregado cargado por withMax() para evitar consultas N+1;
     * cae a la colección ya cargada cuando no se agregó el agregado.
     */
    public function getPriorityAttribute(): int
    {
        if (array_key_exists('queues_max_priority', $this->attributes)) {
            return (int) $this->attributes['queues_max_priority'];
        }

        return (int) $this->queues->max('priority');
    }

    /**
     * Scope para artículos publicados y vigentes.
     */
    public function scopePublished($query)
    {
        $now = now();

        return $query->where('status', 'published')
            ->where('published_at', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $now);
            });
    }

    /**
     * Scope para ordenar por prioridad máxima de las colas asociadas utilizando una subconsulta.
     */
    public function scopeOrderByQueuePriority($query, string $direction = 'desc')
    {
        return $query->orderBy(
            Queue::selectRaw('COALESCE(MAX(priority), 0)')
                ->join('knowledge_article_queue', 'knowledge_queues.id', '=', 'knowledge_article_queue.queue_id')
                ->whereColumn('knowledge_article_queue.article_id', 'knowledge_articles.id'),
            $direction
        );
    }
}
