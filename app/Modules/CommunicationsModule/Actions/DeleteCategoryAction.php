<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\Models\Category;
use Illuminate\Support\Facades\DB;

/**
 * Elimina una categoría del sistema.
 */
class DeleteCategoryAction
{
    /**
     * Ejecuta la eliminación de la categoría.
     */
    public function execute(Category $category): bool
    {
        return DB::transaction(function () use ($category) {
            // Las relaciones polimórficas se eliminan automáticamente por cascade
            return $category->delete();
        });
    }
}
