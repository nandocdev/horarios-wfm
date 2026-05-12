<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\DTOs\TagDTO;
use App\Modules\CommunicationsModule\Models\Tag;
use Illuminate\Support\Facades\DB;

/**
 * Crea un nuevo tag en el sistema.
 */
class CreateTagAction
{
    /**
     * Ejecuta la creación del tag.
     */
    public function execute(TagDTO $dto): Tag
    {
        return DB::transaction(function () use ($dto) {
            return Tag::create([
                'name' => $dto->name,
                'slug' => $dto->slug,
                'color' => $dto->color,
                'is_active' => $dto->is_active,
            ]);
        });
    }
}
