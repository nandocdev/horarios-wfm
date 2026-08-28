<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Observers;

use App\Modules\PersonnelModule\Models\Team;
use App\Shared\Support\Cache\CachePolicyService;
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
        $this->flushTeamCache();
    }

    public function updated(Team $team): void
    {
        $this->flushTeamCache();
        Cache::forget("team:{$team->id}");
    }

    public function deleted(Team $team): void
    {
        $this->flushTeamCache();
        Cache::forget("team:{$team->id}");
    }

    /**
     * Flush all team-related cache.
     */
    private function flushTeamCache(): void
    {
        Cache::forget('teams_list');
        // Also use CachePolicyService for consistency
        app(CachePolicyService::class)->flushByPattern('personnel', 'teams');
    }
}
