<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Actions;

use App\Modules\KnowledgeModule\DTOs\ArticleDTO;
use App\Modules\KnowledgeModule\Models\ArticleVersion;
use App\Modules\KnowledgeModule\Models\KnowledgeArticle;
use App\Modules\KnowledgeModule\Models\Tag;
use App\Shared\Support\HtmlSanitizer;
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
        $content = HtmlSanitizer::sanitize($dto->content);

        return DB::transaction(function () use ($article, $dto, $userId, $content) {
            // Bloqueo pesimista para serializar ediciones concurrentes y evitar
            // versiones duplicadas cuando dos editores guardan a la vez.
            $article = KnowledgeArticle::whereKey($article->id)->lockForUpdate()->firstOrFail();

            $contentChanged = ($article->content !== $content);
            $newVersion = $article->version;

            if ($contentChanged) {
                $newVersion += 1;
            }

            $publishedAt = $dto->published_at ?? $article->published_at;
            if ($dto->status === 'published' && $publishedAt === null) {
                $publishedAt = now();
            }

            // El slug es inmutable: no se regenera al cambiar el título para
            // no romper enlaces guardados, bookmarks ni el SEO interno.
            $article->update([
                'title' => $dto->title,
                'summary' => $dto->summary,
                'content' => $content,
                'category_id' => $dto->category_id,
                'directory_unit_id' => $dto->directory_unit_id,
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
                    'content' => $content,
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
