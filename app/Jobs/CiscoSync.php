<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Modules\ConnectModule\Actions\SyncFinesseAgentStatesAction;
use App\Modules\PersonnelModule\Actions\SyncEmployeeDataWithCiscoAction;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CiscoSync implements ShouldQueue
{
    use Queueable;

    /**
     * El número de veces que se puede intentar el job.
     */
    public int $tries = 3;

    /**
     * El número de segundos antes de que el job aborte por timeout.
     */
    public int $timeout = 120;

    /**
     * La conexión de cola que debe manejar este job.
     */
    public string $connection = 'database';

    /**
     * La cola específica donde se despachará el job (para no bloquear correos u otros procesos).
     */
    public string $queue = 'realtime-sync';

    protected bool $syncMasterData;

    /**
     * Create a new job instance.
     *
     * @param  bool  $syncMasterData  Sincroniza nombres de perfiles y equipos (costoso, se recomienda hacer false en los subciclos)
     */
    public function __construct(bool $syncMasterData = false)
    {
        $this->syncMasterData = $syncMasterData;
    }

    /**
     * Execute the job.
     */
    public function handle(
        CiscoFinesseClient $client,
        SyncFinesseAgentStatesAction $syncStatesAction,
        SyncEmployeeDataWithCiscoAction $syncDataAction,
    ): void {
        $hour = now()->hour;

        if ($hour < 5 || $hour > 19) {
            Log::info('CiscoSync: Fuera de horario de trabajo. El Job se detendrá. (Debe ser reactivado por el Scheduler a las 5 AM)');

            return;
        }

        if ($this->syncMasterData) {
            try {
                $dataStats = $syncDataAction->execute();
                Log::info("CiscoSync Master Data: Procesados: {$dataStats['total_cisco_users']}, Actualizados: {$dataStats['updated_employees']}, Desajustes: {$dataStats['team_mismatches']}");
            } catch (\Exception $e) {
                Log::warning('CiscoSync: No se pudieron sincronizar los datos maestros: '.$e->getMessage());
            }
        }

        try {
            $syncStatesAction->execute();
        } catch (\Exception $e) {
            Log::error('CiscoSync Failure: '.$e->getMessage());
        }

        self::dispatch(false)->delay(now()->addSeconds(5));
    }
}
