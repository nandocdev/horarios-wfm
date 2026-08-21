<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Enums\AgentState;
use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncFinesseAgentStatesAction
{
    public function __construct(
        private CiscoFinesseClient $client,
    ) {}

    public function execute(): array
    {
        $ciscoUsers = $this->getCiscoUserIds();

        if (empty($ciscoUsers)) {
            Log::debug('Lista de usuarios Finesse vacía, saltando sincronización de estados.');

            return ['success' => 0, 'error' => 0, 'skipped' => 0];
        }

        $employeeCacheKey = 'cisco_active_employees:'.sha1(implode('|', $ciscoUsers));

        $employees = Cache::remember($employeeCacheKey, 3600, function () use ($ciscoUsers) {
            return Employee::where('is_active', true)
                ->whereNotNull('username')
                ->whereIn('username', $ciscoUsers)
                ->get(['id', 'username', 'metadata'])
                ->toArray();
        });

        if (! is_array($employees)) {
            Cache::forget('cisco_active_employees');

            return ['success' => 0, 'error' => 0, 'skipped' => 0];
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($employees as $employee) {
            $uccxId = $employee['metadata']['uccx_id'] ?? $employee['username'];

            try {
                $data = $this->client->getAgentInfo($uccxId);

                if (empty($data)) {
                    continue;
                }

                $dialogInfo = $this->getDialogInfo($uccxId, $data);
                $this->updateAgentRealtimeState($employee['id'], $uccxId, $data, $dialogInfo);
                $successCount++;

            } catch (\Throwable $e) {
                $errorCount++;
                Log::debug("Error sincronizando estado del agente {$uccxId}: ".$e->getMessage());
            }
        }

        return ['success' => $successCount, 'error' => $errorCount];
    }

    /**
     * Obtiene los loginId de todos los usuarios en Finesse, cacheados 5 min.
     */
    private function getCiscoUserIds(): array
    {
        $cachedUsers = Cache::get('cisco_finesse_user_ids');
        if (is_array($cachedUsers) && $cachedUsers !== []) {
            return $cachedUsers;
        }

        try {
            $data = $this->client->getAllUsers();
            $users = $data['User'] ?? [];

            if (isset($users['loginId'])) {
                $users = [$users];
            }

            $userIds = collect($users)->pluck('loginId')->filter()->values()->toArray();

            if ($userIds !== []) {
                Cache::put('cisco_finesse_user_ids', $userIds, 300);
            }

            return $userIds;
        } catch (\Throwable $e) {
            Log::warning('No se pudo obtener lista de usuarios Finesse: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Obtiene información de la llamada activa si el agente está en TALKING.
     */
    private function getDialogInfo(string $uccxId, array $data): array
    {
        if (($data['state'] ?? '') !== 'TALKING') {
            return [];
        }

        try {
            $dialogs = $this->client->getAgentDialogs($uccxId);
            $dialog = $dialogs['Dialog'] ?? null;

            if ($dialog && isset($dialog[0])) {
                $dialog = $dialog[0];
            }

            if (! $dialog) {
                return [];
            }

            $dialogInfo = [
                'call_type' => $dialog['callType'] ?? 'N/A',
                'from_address' => $dialog['fromAddress'] ?? 'N/A',
                'queue_name' => $dialog['mediaProperties']['DNIS']
                    ?? ($dialog['mediaProperties']['callControlVariables']['Variable'][0]['value'] ?? 'N/A'),
            ];

            if (isset($dialog['mediaProperties']['queueName'])) {
                $dialogInfo['queue_name'] = $dialog['mediaProperties']['queueName'];
            }

            return $dialogInfo;

        } catch (\Exception $e) {
            Log::debug("No se pudo obtener Dialogs para agente {$uccxId}: ".$e->getMessage());

            return [];
        }
    }

    /**
     * Actualiza el estado realtime del agente con validación de máquina de estados y auditoría.
     *
     * - Valida transición usando la AgentState enum (solo transiciones permitidas)
     * - Siempre escribe AgentStateTransition para trazabilidad auditativa
     * - Evita actualizaciones redundantes cuando el estado no cambia
     * - Manejo de phone failures para no romper trazabilidad WFM
     */
    public function updateAgentRealtimeState(
        int $employeeId,
        string $externalId,
        array $data,
        array $dialogInfo = [],
    ): void
    {
        $rawState = $data['state'] ?? 'UNKNOWN';
        $state = strtoupper($rawState);
        $reasonCode = $data['reasonCode'] ?? null;
        $stateChangeTime = $data['stateChangeTime'] ?? null;

        if (is_array($reasonCode)) {
            $reasonCode = $reasonCode['label'] ?? ($reasonCode['id'] ?? 'N/A');
        }

        if ($reasonCode !== null) {
            $reasonCode = strtoupper((string) $reasonCode);
        }

        // Validar transición usando la enum AgentState
        $isValid = AgentState::isValidTransition(
            strtoupper($existingState?->current_state ?? 'OFFLINE'),
            $state
        ) ?? false;

        // Si la transición es inválida y no es una transición que requiera re-login, la registramos como UNKNOWN
        if (! $isValid && ! AgentState::RELOGIN_REQUIRED->contains($state)) {
            Log::warning("Transición de estado inválida para agente {$externalId}: " . strtoupper($existingState?->current_state ?? 'OFFLINE') . " -> {$state}");
            // Continuamos pero marcamos como UNKNOWN para no perder la trazabilidad
        }

        $existingState = AgentRealtimeState::where('employee_id', $employeeId)->first();

        // Ignorar fallos de teléfono transitorios para no romper trazabilidad de WFM
        $isPhoneFailure = $reasonCode !== null && (
            (str_contains($reasonCode, 'PHONE') && str_contains($reasonCode, 'FAIL')) ||
            str_contains($reasonCode, 'PHONEFAIULER')
        );

        if ($isPhoneFailure && $existingState) {
            // Actualizamos solo metadatos, manteniendo el estado y tiempo anterior
            // Aún así, registramos la transición para auditoría
            $this->logStateTransition($employeeId, $existingState->current_state, $state, $reasonCode, now()->utc());

            DB::table('agent_realtime_states')
                ->where('employee_id', $employeeId)
                ->update([
                    'external_id' => $externalId,
                    'metadata' => json_encode(array_merge($data, [
                        'ignored_transient_state' => $state,
                        'ignored_transient_reason' => $reasonCode,
                        'last_sync_at' => now()->utc()->toIso8601String(),
                        'call_info' => $dialogInfo,
                    ])),
                    'updated_at' => now(),
                ]);

            return;
        }

        $lastChangedAt = now()->utc();

        if ($stateChangeTime) {
            try {
                $lastChangedAt = Carbon::parse($stateChangeTime)->utc();

                if ($lastChangedAt->isFuture()) {
                    $lastChangedAt = now()->utc();
                }
            } catch (\Exception $e) {
                Log::error("Error parseando stateChangeTime ({$stateChangeTime}) para agente {$externalId}: ".$e->getMessage());
                $lastChangedAt = now()->utc();
            }
        } else {
            if ($existingState
                && AgentState::isValidTransition(
                    strtoupper((string) $existingState->current_state),
                    $state
                )
            ) {
                // Solo mantenemos el timestamp si la transición es válida y el estado no cambió
                $lastChangedAt = Carbon::parse($existingState->last_changed_at)->utc();
            }
        }

        // Siempre registrar la transición en AgentStateTransition para auditoría inmutable
        $this->logStateTransition($employeeId, $existingState?->current_state ?? 'OFFLINE', $state, $reasonCode, $lastChangedAt);

        // Actualizar o crear el estado realtime (solo si el estado cambió o pasó suficiente tiempo)
        $stateChanged = ! $existingState
            || strtoupper((string) $existingState->current_state) !== $state
            || ($reasonCode ?? '') !== ($existingState->reason_code ?? '');

        if ($stateChanged) {
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

    /**
     * Registra la transición de estado en AgentStateTransition para auditoría inmutable.
     *
     * Este registro nunca se modifica y provee la trazabilidad requerida por el Código de Trabajo
     * para defensa ante reclamos laborales sobre horarios y ausencias.
     */
    private function logStateTransition(
        int $employeeId,
        string $fromState,
        string $toState,
        ?string $reasonCode,
        CarbonInterface $transitionTime,
    ): void
    {
        AgentStateTransition::create([
            'agent_login_id' => null, // Se llenará si hay información de login
            'employee_id' => $employeeId,
            'transition_time' => $transitionTime,
            'agent_state' => $toState,
            'reason_code' => $reasonCode,
            'duration' => 0, // Se calculará posteriormente si es necesario
        ]);
    }

    /**
     * Obtiene los loginId de todos los usuarios en Finesse, cacheados 5 min.
     */
    private function getCiscoUserIdsCached(): array
    {
        return $this->getCiscoUserIds();
    }
}