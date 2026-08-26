<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Observers;

use App\Modules\CommunicationsModule\Models\Category;
use App\Shared\Support\Cache\CachePolicyService;
use Illuminate\Support\Facades\Cache;

/**
 * Observa el ciclo de vida del modelo Category.
 * Maneja efectos secundarios como limpieza de caché.
 */
class CategoryObserver
{
    public function __construct(
        private readonly CachePolicyService $cachePolicy,
    ) {}

    /**
     * Maneja el evento de creación.
     */
    public function created(Category $category): void
    {
        $this->cachePolicy->flushByPattern('communications', 'config');
    }

    /**
     * Maneja el evento de actualización.
     */
    public function updated(Category $category): void
    {
        Cache::forget("category:{$category->id}");
        $this->cachePolicy->flushByPattern('communications', 'config');
    }

    /**
     * Maneja el evento de eliminación.
     */
    public function deleted(Category $category): void
    {
        Cache::forget("category:{$category->id}");
        $this->cachePolicy->flushByPattern('communications', 'config');
    }

    /**
     * Maneja el evento de restauración.
     */
    public function restored(Category $category): void
    {
        $this->cachePolicy->flushByPattern('communications', 'config');
    }

    /**
     * Maneja el evento de eliminación permanente.
     */
    public function forceDeleted(Category $category): void
    {
        Cache::forget("category:{$category->id}");
        $this->cachePolicy->flushByPattern('communications', 'config');
    }
}
