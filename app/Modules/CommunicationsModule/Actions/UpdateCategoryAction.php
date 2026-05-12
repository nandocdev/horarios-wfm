<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\DTOs\CategoryDTO;
use App\Modules\CommunicationsModule\Models\Category;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza una categoría existente.
 */
class UpdateCategoryAction
{
    /**
     * Ejecuta la actualización de la categoría.
     */
    public function execute(Category $category, CategoryDTO $dto): Category
    {
        return DB::transaction(function () use ($category, $dto) {
            $category->update([
                'name' => $dto->name,
                'slug' => $dto->slug,
                'description' => $dto->description,
                'color' => $dto->color,
                'is_active' => $dto->is_active,
                'sort_order' => $dto->sort_order,
            ]);

            return $category->fresh();
        });
    }
}
