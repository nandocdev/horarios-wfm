<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Observers;

use App\Modules\OrganizationModule\Models\Directorate;
use Illuminate\Support\Facades\Cache;

/**
 * Observa el ciclo de vida del modelo Directorate.
 * Solo efectos secundarios: caché, logs, sincronizaciones externas.
 * NO contiene lógica de negocio (esa va en Actions).
 */
class DirectorateObserver
{
    public function created(Directorate $directorate): void
    {
        Cache::forget('directorates_list');
    }

    public function updated(Directorate $directorate): void
    {
        Cache::forget("directorate:{$directorate->id}");
    }

    public function deleted(Directorate $directorate): void
    {
        Cache::forget("directorate:{$directorate->id}");
        Cache::forget('directorates_list');
    }
}
