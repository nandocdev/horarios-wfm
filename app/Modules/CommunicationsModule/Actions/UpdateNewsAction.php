<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\DTOs\NewsDTO;
use App\Modules\CommunicationsModule\Models\News;
use Illuminate\Support\Facades\DB;

/**
 * Acción para actualizar una noticia existente.
 */
class UpdateNewsAction
{
    /**
     * Actualiza metadatos y gestiona archivos multimedia.
     */
    public function execute(News $news, NewsDTO $dto): News
    {
        return DB::transaction(function () use ($news, $dto) {
            $news->update([
                'title' => $dto->title,
                'slug' => $dto->slug,
                'excerpt' => $dto->excerpt,
                'content' => $dto->content,
                'published_at' => $dto->published_at,
                'scheduled_at' => $dto->scheduled_at,
                'archive_at' => $dto->archive_at,
                'is_active' => $dto->is_active,
            ]);

            $news->categories()->sync($dto->categoryIds);
            $news->tags()->sync($dto->tagIds);

            // Actualizar Imagen Destacada (Si hay una nueva)
            if ($dto->featuredImage !== null) {
                $news->clearMediaCollection('featured_image');
                $news->addMedia($dto->featuredImage)->toMediaCollection('featured_image', 'public');
            }

            // Agregar nuevos adjuntos (Sin borrar anteriores)
            foreach ($dto->attachments as $file) {
                $news->addMedia($file)->toMediaCollection('attachments', 'public');
            }

            return $news->fresh();
        });
    }
}
