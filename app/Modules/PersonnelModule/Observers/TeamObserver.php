<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Observers;

use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Support\Facades\Cache;

/**
 * Observa el ciclo de vida del modelo Team.
 * Solo efectos secundarios: caché, logs, sincronizaciones externas.
 * NO contiene lógica de negocio (esa va en Actions).
 */
class TeamObserver
{
    public function created(Team $team): void
    {
        Cache::forget('teams_list');
    }

    public function updated(Team $team): void
    {
        Cache::forget("team:{$team->id}");
    }

    public function deleted(Team $team): void
    {
        Cache::forget("team:{$team->id}");
        Cache::forget('teams_list');
    }
}
