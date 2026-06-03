<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Actions;

use App\Modules\KnowledgeModule\DTOs\ArticleDTO;
use App\Modules\KnowledgeModule\Models\Article;
use App\Modules\KnowledgeModule\Models\ArticleVersion;
use App\Modules\KnowledgeModule\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Acción encapsulada para crear artículos de la base de conocimiento en una transacción segura.
 */
class CreateArticleAction
{
    /**
     * Ejecuta la creación del artículo.
     *
     * @param  ArticleDTO  $dto
     * @param  int  $userId
     * @return Article
     */
    public function execute(ArticleDTO $dto, int $userId): Article
    {
        return DB::transaction(function () use ($dto, $userId) {
            $slug = Str::slug($dto->title) . '-' . uniqid();

            $publishedAt = $dto->published_at;
            if ($dto->status === 'published' && $publishedAt === null) {
                $publishedAt = now();
            }

            $article = Article::create([
                'title' => $dto->title,
                'slug' => $slug,
                'summary' => $dto->summary,
                'content' => $dto->content,
                'category_id' => $dto->category_id,
                'status' => $dto->status,
                'version' => 1,
                'published_at' => $publishedAt,
                'expires_at' => $dto->expires_at,
                'created_by' => $userId,
            ]);

            // Sincronizar colas de atención
            $article->queues()->sync($dto->queues);

            // Sincronizar etiquetas (crear dinámicamente)
            $tagIds = [];
            foreach ($dto->tags as $tagName) {
                $tag = Tag::firstOrCreate(['name' => Str::lower(trim($tagName))]);
                $tagIds[] = $tag->id;
            }
            $article->tags()->sync($tagIds);

            // Registrar versión inicial de auditoría de contenido
            ArticleVersion::create([
                'article_id' => $article->id,
                'version' => 1,
                'content' => $dto->content,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            return $article;
        });
    }
}
/**
 * [RIESGOS]
 * - Duplicación de etiquetas → Se usa Str::lower y trim para normalizar las etiquetas antes de crearlas.
 * - Transacciones PostgreSQL → El uso de DB::transaction asegura atomicidad total en la inserción del artículo, relaciones y versiones históricas.
 */
