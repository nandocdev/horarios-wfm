<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Observers;

use App\Modules\CommunicationsModule\Models\Category;
use Illuminate\Support\Facades\Cache;

/**
 * Observa el ciclo de vida del modelo Category.
 * Maneja efectos secundarios como limpieza de caché.
 */
class CategoryObserver
{
    /**
     * Maneja el evento de creación.
     */
    public function created(Category $category): void
    {
        Cache::forget('categories_list');
        Cache::forget('categories_tree');
    }

    /**
     * Maneja el evento de actualización.
     */
    public function updated(Category $category): void
    {
        Cache::forget("category:{$category->id}");
        Cache::forget('categories_list');
        Cache::forget('categories_tree');
    }

    /**
     * Maneja el evento de eliminación.
     */
    public function deleted(Category $category): void
    {
        Cache::forget("category:{$category->id}");
        Cache::forget('categories_list');
        Cache::forget('categories_tree');
    }

    /**
     * Maneja el evento de restauración.
     */
    public function restored(Category $category): void
    {
        Cache::forget('categories_list');
        Cache::forget('categories_tree');
    }

    /**
     * Maneja el evento de eliminación permanente.
     */
    public function forceDeleted(Category $category): void
    {
        Cache::forget("category:{$category->id}");
        Cache::forget('categories_list');
        Cache::forget('categories_tree');
    }
}
