<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\ConnectModule\Services\CiscoFinesseService;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncTeamsWithCiscoAction
{
    public function __construct(
        protected CiscoFinesseService $finesseService
    ) {}

    /**
     * Ejecuta la sincronización de equipos.
     *
     * @return int Número de equipos sincronizados.
     *
     * @throws \Exception
     */
    public function execute(): int
    {
        $ciscoTeams = $this->finesseService->getTeams();
        $syncedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($ciscoTeams as $ciscoData) {
                // Sincronizar por cisco_team_id
                Team::updateOrCreate(
                    ['cisco_team_id' => $ciscoData['id']],
                    [
                        'name' => $ciscoData['name'],
                        'is_active' => true,
                        'cisco_team_id' => $ciscoData['id'],
                    ]
                );
                $syncedCount++;
            }

            DB::commit();

            return $syncedCount;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error sincronizando equipos con Cisco: '.$e->getMessage());
            throw $e;
        }
    }
}
