<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\PersonnelModule\Actions\SyncEmployeeDataWithCiscoAction;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
    public function handle(CiscoFinesseClient $client, SyncEmployeeDataWithCiscoAction $syncDataAction)
    {
        $loop = $this->option('loop');
        $interval = (int) $this->option('interval');

        $this->info('Iniciando trabajador de sincronización UCCX...');

        // 1. Sincronizar datos básicos (Nombres y Equipos) una vez al inicio
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

            // se ejecutara solo entre las 5 de la mañana y las 7 de la noche
            if ($hour < 5 || $hour > 19) {
                $this->warn('Fuera de horario de trabajo, saltando sincronización...');
                sleep(60);

                continue;
            }
            try {
                $this->syncStates($client);
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

    /**
     * Lógica principal de sincronización.
     */
    protected function syncStates(CiscoFinesseClient $client): void
    {
        // Obtener empleados activos (que podrían ser agentes)
        $employees = Employee::where('is_active', true)
            ->whereNotNull('username')
            ->get();

        $this->info('Procesando '.$employees->count().' posibles agentes...');

        $successCount = 0;
        $errorCount = 0;

        foreach ($employees as $employee) {
            // Determinar el ID de UCCX (priorizar metadata, luego username)
            $uccxId = $employee->metadata['uccx_id'] ?? $employee->username;

            try {
                $data = $client->getAgentInfo($uccxId);

                if (empty($data)) {
                    continue;
                }

                // Si está en TALKING, buscar información de la llamada (Dialogs)
                $dialogInfo = [];
                if (($data['state'] ?? '') === 'TALKING') {
                    try {
                        $dialogs = $client->getAgentDialogs($uccxId);
                        // El XML de Cisco para Dialogs puede ser complejo, intentamos extraer el primer diálogo
                        $dialog = $dialogs['Dialog'] ?? null;

                        // Si hay múltiples diálogos, Cisco a veces devuelve un array de Dialogs
                        if ($dialog && isset($dialog[0])) {
                            $dialog = $dialog[0];
                        }

                        if ($dialog) {
                            $dialogInfo = [
                                'call_type' => $dialog['callType'] ?? 'N/A',
                                'from_address' => $dialog['fromAddress'] ?? 'N/A',
                                'queue_name' => $dialog['mediaProperties']['DNIS'] ?? ($dialog['mediaProperties']['callControlVariables']['Variable'][0]['value'] ?? 'N/A'),
                            ];

                            // Intentar buscar queueName específico si existe en mediaProperties
                            if (isset($dialog['mediaProperties']['queueName'])) {
                                $dialogInfo['queue_name'] = $dialog['mediaProperties']['queueName'];
                            }
                        }
                    } catch (\Exception $e) {
                        // Error silencioso para dialogs, no incrementa errorCount general pero se loguea en debug si es necesario
                        Log::debug("No se pudo obtener Dialogs para agente {$uccxId}: ".$e->getMessage());
                    }
                }

                $this->updateAgentRealtimeState($employee->id, $uccxId, $data, $dialogInfo);
                $successCount++;

            } catch (\Exception $e) {
                // No detenemos el bucle por un fallo de un solo agente
                $errorCount++;
                // Logueamos solo en debug para no saturar el log principal
                // Log::debug("No se pudo obtener estado para agente {$uccxId}: " . $e->getMessage());
            }
        }

        Log::info("Sincronización de Cisco completada: {$successCount} registros correctos, {$errorCount} presentan errores.");
        $this->info("Resumen: {$successCount} éxitos, {$errorCount} fallos.");
    }

    /**
     * Actualiza la tabla agent_realtime_states (UNLOGGED para performance).
     */
    protected function updateAgentRealtimeState(int $employeeId, string $externalId, array $data, array $dialogInfo = []): void
    {
        $state = $data['state'] ?? 'UNKNOWN';
        $reasonCode = $data['reasonCode'] ?? null;
        $stateChangeTime = $data['stateChangeTime'] ?? null;

        // Si el reasonCode es un arreglo (del XML), extraer el nombre o ID
        if (is_array($reasonCode)) {
            $reasonCode = $reasonCode['label'] ?? ($reasonCode['id'] ?? 'N/A');
        }

        $lastChangedAt = now()->utc();

        // 1. Obtener el estado previo del agente
        $existingState = DB::table('agent_realtime_states')->where('employee_id', $employeeId)->first();

        if ($stateChangeTime) {
            try {
                // Forzamos UTC al parsear si no hay offset, ya que Cisco UCCX suele enviar tiempos en UTC
                // pero a veces el XML no incluye el indicador 'Z'.
                $lastChangedAt = Carbon::parse($stateChangeTime, 'UTC')->utc();
            } catch (\Exception $e) {
                Log::error("Error parseando stateChangeTime ({$stateChangeTime}) para agente {$externalId}: ".$e->getMessage());
            }
        } else {
            // Si Cisco no provee stateChangeTime (común en TALKING),
            // conservamos la fecha que ya estaba en la base de datos para no reiniciar el cronómetro a 0.
            if ($existingState && $existingState->current_state === $state) {
                $lastChangedAt = Carbon::parse($existingState->last_changed_at)->utc();
            } elseif ($existingState && $state === 'TALKING' && isset($dialogInfo['start_time'])) {
                // Si tienes la hora de inicio de la llamada desde el dialog, podrías usarla,
                // de lo contrario usamos la hora actual (primer segundo de la llamada).
                $lastChangedAt = now()->utc();
            }
        }

        DB::table('agent_realtime_states')->updateOrInsert(
            ['employee_id' => $employeeId],
            [
                'external_id' => $externalId,
                'current_state' => $state,
                'reason_code' => $reasonCode,
                'last_changed_at' => $lastChangedAt->toIso8601String(),
                'metadata' => json_encode(array_merge($data, [
                    'state_change_time_original' => $stateChangeTime,
                    'last_sync_at' => now()->utc()->toIso8601String(),
                    'parsed_at_utc' => $lastChangedAt->toIso8601String(),
                    'call_info' => $dialogInfo, // Inyectar info de la llamada
                ])),
                'updated_at' => now(),
            ]
        );
    }
}
