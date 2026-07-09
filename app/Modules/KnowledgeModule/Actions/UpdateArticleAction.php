<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Actions;

use App\Modules\KnowledgeModule\DTOs\ArticleDTO;
use App\Modules\KnowledgeModule\Models\ArticleVersion;
use App\Modules\KnowledgeModule\Models\KnowledgeArticle;
use App\Modules\KnowledgeModule\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Acción encapsulada para actualizar artículos e incrementar versiones si el contenido cambia.
 */
class UpdateArticleAction
{
    /**
     * Ejecuta la actualización del artículo.
     */
    public function execute(KnowledgeArticle $article, ArticleDTO $dto, int $userId): KnowledgeArticle
    {
        return DB::transaction(function () use ($article, $dto, $userId) {
            $contentChanged = ($article->content !== $dto->content);
            $newVersion = $article->version;

            if ($contentChanged) {
                $newVersion += 1;
            }

            $publishedAt = $dto->published_at ?? $article->published_at;
            if ($dto->status === 'published' && $publishedAt === null) {
                $publishedAt = now();
            }

            // Mantener slug estable basado en el ID original
            $slug = Str::slug($dto->title).'-'.$article->id;

            $article->update([
                'title' => $dto->title,
                'slug' => $slug,
                'summary' => $dto->summary,
                'content' => $dto->content,
                'category_id' => $dto->category_id,
                'status' => $dto->status,
                'version' => $newVersion,
                'published_at' => $publishedAt,
                'expires_at' => $dto->expires_at,
                'updated_by' => $userId,
            ]);

            // Sincronizar colas
            $article->queues()->sync($dto->queues);

            // Sincronizar etiquetas
            $tagIds = [];
            foreach ($dto->tags as $tagName) {
                $tag = Tag::firstOrCreate(['name' => Str::lower(trim($tagName))]);
                $tagIds[] = $tag->id;
            }
            $article->tags()->sync($tagIds);

            // Si el contenido cambió, registrar nueva versión en historial
            if ($contentChanged) {
                ArticleVersion::create([
                    'article_id' => $article->id,
                    'version' => $newVersion,
                    'content' => $dto->content,
                    'created_by' => $userId,
                    'created_at' => now(),
                ]);
            }

            return $article;
        });
    }
}
/**
 * [RIESGOS]
 * - Control de Concurrencia → El uso de transacciones con bloqueo optimista/pesimista puede ser considerado si hay múltiples autores editando el mismo registro. En este caso, el versionamiento secuencial mitiga parcialmente sobrescrituras accidentales.
 */
