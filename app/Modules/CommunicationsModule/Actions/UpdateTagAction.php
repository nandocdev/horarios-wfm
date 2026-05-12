<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\DTOs\TagDTO;
use App\Modules\CommunicationsModule\Models\Tag;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza un tag existente.
 */
class UpdateTagAction
{
    /**
     * Ejecuta la actualización del tag.
     */
    public function execute(Tag $tag, TagDTO $dto): Tag
    {
        return DB::transaction(function () use ($tag, $dto) {
            $tag->update([
                'name' => $dto->name,
                'slug' => $dto->slug,
                'color' => $dto->color,
                'is_active' => $dto->is_active,
            ]);

            return $tag->fresh();
        });
    }
}
