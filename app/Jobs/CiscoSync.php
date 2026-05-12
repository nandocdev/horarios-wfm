<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Actions\SyncEmployeeDataWithCiscoAction;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
     * @param bool $syncMasterData Sincroniza nombres de perfiles y equipos (costoso, se recomienda hacer false en los subciclos)
     */
    public function __construct(bool $syncMasterData = false)
    {
        $this->syncMasterData = $syncMasterData;
    }

    /**
     * Execute the job.
     */
    public function handle(CiscoFinesseClient $client, SyncEmployeeDataWithCiscoAction $syncDataAction): void
    {
        $hour = now()->hour;

        // Se ejecutará solo entre las 5 de la mañana y las 7 de la noche (19 hrs)
        if ($hour < 5 || $hour > 19) {
            Log::info('CiscoSync: Fuera de horario de trabajo. El Job se detendrá. (Debe ser reactivado por el Scheduler a las 5 AM)');
            return; 
            // Salimos sin volver a despachar el Job.
        }

        // 1. Sincronizar perfiles y equipos (Solo si se envía el flag como true)
        if ($this->syncMasterData) {
            try {
                $dataStats = $syncDataAction->execute();
                Log::info("CiscoSync Master Data: Procesados: {$dataStats['total_cisco_users']}, Actualizados: {$dataStats['updated_employees']}, Desajustes: {$dataStats['team_mismatches']}");
            } catch (\Exception $e) {
                Log::warning('CiscoSync: No se pudieron sincronizar los datos maestros: ' . $e->getMessage());
            }
        }

        // 2. Sincronización de estados en Tiempo Real
        try {
            $this->syncStates($client);
        } catch (\Exception $e) {
            Log::error('CiscoSync Failure: ' . $e->getMessage());
        }

        // 3. Self-Dispatch (Ciclo de vida asíncrono en reemplazo del while-loop)
        // Volvemos a colocar este Job en la cola con 5 segundos de retraso. 
        // Pasamos 'false' para no sobrecargar el API sincronizando los datos maestros cada 5 segundos.
        self::dispatch(false)->delay(now()->addSeconds(5));
    }

    /**
     * Lógica principal de sincronización de estados UCCX.
     */
    protected function syncStates(CiscoFinesseClient $client): void
    {
        $employees = Employee::where('is_active', true)
            ->whereNotNull('username')
            ->get();

        $successCount = 0;
        $errorCount = 0;

        foreach ($employees as $employee) {
            $uccxId = $employee->metadata['uccx_id'] ?? $employee->username;

            try {
                $data = $client->getAgentInfo($uccxId);

                if (empty($data)) {
                    continue;
                }

                $dialogInfo = [];
                if (($data['state'] ?? '') === 'TALKING') {
                    try {
                        $dialogs = $client->getAgentDialogs($uccxId);
                        $dialog = $dialogs['Dialog'] ?? null;

                        if ($dialog && isset($dialog[0])) {
                            $dialog = $dialog[0];
                        }

                        if ($dialog) {
                            $dialogInfo = [
                                'call_type' => $dialog['callType'] ?? 'N/A',
                                'from_address' => $dialog['fromAddress'] ?? 'N/A',
                                'queue_name' => $dialog['mediaProperties']['DNIS'] ?? ($dialog['mediaProperties']['callControlVariables']['Variable'][0]['value'] ?? 'N/A'),
                            ];

                            if (isset($dialog['mediaProperties']['queueName'])) {
                                $dialogInfo['queue_name'] = $dialog['mediaProperties']['queueName'];
                            }
                        }
                    } catch (\Exception $e) {
                        Log::debug("No se pudo obtener Dialogs para agente {$uccxId}: " . $e->getMessage());
                    }
                }

                $this->updateAgentRealtimeState($employee->id, $uccxId, $data, $dialogInfo);
                $successCount++;

            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        // Opcional: Descomentar si requieres ver el conteo cada 5 segundos en el log.
        // if ($errorCount > 0) {
        //     Log::info("Sincronización de Cisco completada: {$successCount} registros correctos, {$errorCount} errores.");
        // }
    }

    /**
     * Actualiza la tabla agent_realtime_states (UNLOGGED para performance).
     */
    protected function updateAgentRealtimeState(int $employeeId, string $externalId, array $data, array $dialogInfo = []): void
    {
        $state = $data['state'] ?? 'UNKNOWN';
        $reasonCode = $data['reasonCode'] ?? null;
        $stateChangeTime = $data['stateChangeTime'] ?? null;

        if (is_array($reasonCode)) {
            $reasonCode = $reasonCode['label'] ?? ($reasonCode['id'] ?? 'N/A');
        }

        $lastChangedAt = now()->utc();
        if ($stateChangeTime) {
            try {
                $lastChangedAt = Carbon::parse($stateChangeTime)->utc();
            } catch (\Exception $e) {
                Log::error("Error parseando stateChangeTime ({$stateChangeTime}) para agente {$externalId}: " . $e->getMessage());
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
                    'call_info' => $dialogInfo,
                ])),
                'updated_at' => now(),
            ]
        );
    }
}
