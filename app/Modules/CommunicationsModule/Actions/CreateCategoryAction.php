<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\DTOs\CategoryDTO;
use App\Modules\CommunicationsModule\Models\Category;
use Illuminate\Support\Facades\DB;

/**
 * Crea una nueva categoría en el sistema.
 */
class CreateCategoryAction
{
    /**
     * Ejecuta la creación de la categoría.
     */
    public function execute(CategoryDTO $dto): Category
    {
        return DB::transaction(function () use ($dto) {
            return Category::create([
                'name' => $dto->name,
                'slug' => $dto->slug,
                'description' => $dto->description,
                'color' => $dto->color,
                'is_active' => $dto->is_active,
                'sort_order' => $dto->sort_order,
            ]);
        });
    }
}
