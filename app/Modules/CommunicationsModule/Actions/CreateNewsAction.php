<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\DTOs\NewsDTO;
use App\Modules\CommunicationsModule\Models\News;
use Illuminate\Support\Facades\DB;

/**
 * Acción para crear una nueva noticia.
 */
class CreateNewsAction
{
    /**
     * Ejecuta la creación de la noticia y procesa los archivos multimedia.
     */
    public function execute(NewsDTO $dto): News
    {
        return DB::transaction(function () use ($dto) {
            $news = News::create([
                'title' => $dto->title,
                'slug' => $dto->slug,
                'excerpt' => $dto->excerpt,
                'content' => $dto->content,
                'published_at' => $dto->published_at,
                'scheduled_at' => $dto->scheduled_at,
                'archive_at' => $dto->archive_at,
                'status' => $dto->workflowAction === 'submit_review' ? 'pending_review' : 'draft',
                'is_active' => $dto->is_active,
                'author_id' => $dto->author_id,
            ]);

            $news->categories()->sync($dto->categoryIds);
            $news->tags()->sync($dto->tagIds);

            // Procesar Imagen Destacada
            if ($dto->featuredImage) {
                $news->addMedia($dto->featuredImage)
                    ->toMediaCollection('featured_image', 'public');
            }

            // Procesar Adjuntos (PDF, Videos, etc)
            foreach ($dto->attachments as $file) {
                $news->addMedia($file)
                    ->toMediaCollection('attachments', 'public');
            }

            return $news;
        });
    }
}
