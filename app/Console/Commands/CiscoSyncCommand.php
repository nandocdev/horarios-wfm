<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\ConnectModule\Actions\SyncFinesseAgentStatesAction;
use App\Modules\PersonnelModule\Actions\SyncEmployeeDataWithCiscoAction;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Log;

class CiscoSyncCommand extends Command implements Isolatable
{
    /**
     * El nombre y la firma del comando.
     *
     * @var string
     */
    protected $signature = 'cisco:sync {--loop : Ejecutar en bucle infinito} {--interval=5 : Intervalo en segundos entre cada sincronización}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Sincroniza los estados de los agentes desde Cisco UCCX Finesse API';

    /**
     * Ejecuta el comando.
     */
    public function handle(
        CiscoFinesseClient $client,
        SyncFinesseAgentStatesAction $syncStatesAction,
        SyncEmployeeDataWithCiscoAction $syncDataAction,
    ) {
        $loop = $this->option('loop');
        $interval = (int) $this->option('interval');

        $this->info('Iniciando trabajador de sincronización UCCX...');

        try {
            $this->info('Sincronizando perfiles de agentes y equipos desde Cisco...');
            $dataStats = $syncDataAction->execute();
            $this->info("Perfiles procesados: {$dataStats['total_cisco_users']}, Actualizados: {$dataStats['updated_employees']}, Desajustes de equipo: {$dataStats['team_mismatches']}");
        } catch (\Exception $e) {
            $this->warn('No se pudieron sincronizar los datos maestros: '.$e->getMessage());
        }

        do {
            $startTime = microtime(true);
            $hour = now()->hour;

            if ($hour < 5 || $hour > 19) {
                $this->warn('Fuera de horario de trabajo, saltando sincronización...');
                sleep(60);

                continue;
            }

            try {
                $result = $syncStatesAction->execute();
                $this->info("Resumen: {$result['success']} éxitos, {$result['error']} fallos.");
            } catch (\Exception $e) {
                $this->error('Error en el ciclo de sincronización: '.$e->getMessage());
                Log::error('CiscoSyncCommand Failure: '.$e->getMessage());
            }

            if ($loop) {
                $executionTime = microtime(true) - $startTime;
                $sleepTime = max(0, $interval - (int) $executionTime);

                if ($sleepTime > 0) {
                    $this->line("Esperando {$sleepTime} segundos para el siguiente ciclo...");
                    sleep($sleepTime);
                }
            }

        } while ($loop);

        $this->info('Sincronización finalizada.');
    }
}
