<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Observers;

use App\Modules\OrganizationModule\Models\Position;
use Illuminate\Support\Facades\Cache;

/**
 * Observa el ciclo de vida del modelo Position.
 * Solo efectos secundarios: caché, logs, sincronizaciones externas.
 * NO contiene lógica de negocio (esa va en Actions).
 */
class PositionObserver
{
    public function created(Position $position): void
    {
        Cache::forget('positions_list');
    }

    public function updated(Position $position): void
    {
        Cache::forget("position:{$position->id}");
    }

    public function deleted(Position $position): void
    {
        Cache::forget("position:{$position->id}");
        Cache::forget('positions_list');
    }
}
