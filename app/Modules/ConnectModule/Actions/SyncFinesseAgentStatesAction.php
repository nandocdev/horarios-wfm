<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Enums\AgentState;
use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use App\Shared\Support\Cache\CachePolicyService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncFinesseAgentStatesAction
{
    public function __construct(
        private CiscoFinesseClient $client,
        private readonly CachePolicyService $cachePolicy,
    ) {}

    public function execute(): array
    {
        $ciscoUsers = $this->getCiscoUserIds();

        if (empty($ciscoUsers)) {
            Log::debug('Lista de usuarios Finesse vacía, saltando sincronización de estados.');

            return ['success' => 0, 'error' => 0, 'skipped' => 0];
        }

        $employeeCacheKey = 'cisco_active_employees:'.sha1(implode('|', $ciscoUsers));

        $employees = $this->cachePolicy->remember('connect', 'employees', 'active_by_users:'.substr($employeeCacheKey, 25, 8), function () use ($ciscoUsers) {
            return Employee::where('is_active', true)
                ->where(function ($q) use ($ciscoUsers) {
                    $q->whereIn('username', $ciscoUsers)
                        ->orWhereIn('cisco_username', $ciscoUsers);
                })
                ->get(['id', 'username', 'cisco_username', 'metadata'])
                ->toArray();
        });

        if (! is_array($employees)) {
            Cache::forget('cisco_active_employees');

            return ['success' => 0, 'error' => 0, 'skipped' => 0];
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($employees as $employee) {
            $uccxId = null;
            if (! empty($employee['cisco_username']) && in_array($employee['cisco_username'], $ciscoUsers, true)) {
                $uccxId = (string) $employee['cisco_username'];
            } elseif (! empty($employee['username']) && in_array($employee['username'], $ciscoUsers, true)) {
                $uccxId = (string) $employee['username'];
            } elseif (! empty($employee['metadata']['uccx_id']) && in_array($employee['metadata']['uccx_id'], $ciscoUsers, true)) {
                $uccxId = (string) $employee['metadata']['uccx_id'];
            } else {
                $uccxId = (string) ($employee['cisco_username'] ?? ($employee['username'] ?? ($employee['metadata']['uccx_id'] ?? '')));
            }

            if (empty($uccxId)) {
                continue;
            }

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
        return $this->cachePolicy->remember('connect', 'config', 'finesse_user_ids', function () {
            try {
                $data = $this->client->getAllUsers();
                $users = $data['User'] ?? [];

                if (isset($users['loginId'])) {
                    $users = [$users];
                }

                $userIds = collect($users)->pluck('loginId')->filter()->values()->toArray();

                return $userIds;
            } catch (\Throwable $e) {
                Log::warning('No se pudo obtener lista de usuarios Finesse: '.$e->getMessage());

                return [];
            }
        });
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
    ): void {
        $rawState = $data['state'] ?? 'UNKNOWN';
        $state = strtoupper((string) $rawState);
        $reasonCode = $data['reasonCode'] ?? null;
        $stateChangeTime = $data['stateChangeTime'] ?? null;

        if (is_array($reasonCode)) {
            $reasonCode = $reasonCode['label'] ?? ($reasonCode['id'] ?? 'N/A');
        }

        if ($reasonCode !== null) {
            $reasonCode = strtoupper((string) $reasonCode);
        }

        $existingState = AgentRealtimeState::where('employee_id', $employeeId)->first();
        $fromState = strtoupper((string) ($existingState?->current_state ?? 'OFFLINE'));

        // Validar transición usando la enum AgentState
        $isValid = AgentState::isValidTransition($fromState, $state);

        $reloginRequiredValues = array_map(fn ($case) => $case->value, AgentState::RELOGIN_REQUIRED);
        $isReloginRequired = in_array($state, $reloginRequiredValues, true);

        // Si la transición es inválida y no requiere re-login, registramos warning
        if (! $isValid && ! $isReloginRequired && $fromState !== $state) {
            Log::warning("Transición de estado inusual para agente {$externalId}: {$fromState} -> {$state}");
        }

        // Ignorar fallos de teléfono transitorios para no romper trazabilidad de WFM
        $isPhoneFailure = $reasonCode !== null && (
            (str_contains($reasonCode, 'PHONE') && str_contains($reasonCode, 'FAIL')) ||
            str_contains($reasonCode, 'PHONEFAIULER')
        );

        if ($isPhoneFailure && $existingState) {
            // Actualizamos solo metadatos, manteniendo el estado y tiempo anterior
            $this->logStateTransition($employeeId, $externalId, $fromState, $state, $reasonCode, now()->utc());

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
            } catch (\Throwable $e) {
                Log::error("Error parseando stateChangeTime ({$stateChangeTime}) para agente {$externalId}: ".$e->getMessage());
                $lastChangedAt = now()->utc();
            }
        } elseif ($existingState && $existingState->last_changed_at && $existingState->current_state === $state) {
            $lastChangedAt = Carbon::parse($existingState->last_changed_at)->utc();
        }

        // Siempre registrar la transición en AgentStateTransition para auditoría inmutable
        $this->logStateTransition($employeeId, $externalId, $fromState, $state, $reasonCode, $lastChangedAt);

        // Actualizar o crear el estado realtime
        $stateChanged = ! $existingState
            || strtoupper((string) $existingState->current_state) !== $state
            || ($reasonCode ?? '') !== ($existingState->reason_code ?? '');

        $payload = [
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
        ];

        if ($stateChanged || ! $existingState) {
            DB::table('agent_realtime_states')->updateOrInsert(
                ['employee_id' => $employeeId],
                $payload
            );
        } else {
            // Mantener updated_at fresco para el indicador de sincronización en tiempo real
            DB::table('agent_realtime_states')
                ->where('employee_id', $employeeId)
                ->update([
                    'metadata' => $payload['metadata'],
                    'updated_at' => now(),
                ]);
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
        string $externalId,
        string $fromState,
        string $toState,
        ?string $reasonCode,
        CarbonInterface $transitionTime,
    ): void {
        AgentStateTransition::firstOrCreate(
            [
                'agent_login_id' => $externalId,
                'transition_time' => $transitionTime,
                'agent_state' => $toState,
            ],
            [
                'employee_id' => $employeeId,
                'reason_code' => $reasonCode,
                'duration' => 0,
            ]
        );
    }

    /**
     * Obtiene los loginId de todos los usuarios en Finesse, cacheados 5 min.
     */
    private function getCiscoUserIdsCached(): array
    {
        return $this->getCiscoUserIds();
    }
}
