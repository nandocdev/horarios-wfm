<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncFinesseAgentStatesAction
{
    public function __construct(
        private CiscoFinesseClient $client,
    ) {}

    public function execute(): array
    {
        $employees = Cache::remember('cisco_active_employees', 3600, function () {
            return Employee::where('is_active', true)
                ->whereNotNull('username')
                ->get(['id', 'username', 'metadata']);
        });

        $successCount = 0;
        $errorCount = 0;

        foreach ($employees as $employee) {
            $uccxId = $employee->metadata['uccx_id'] ?? $employee->username;

            try {
                $data = $this->client->getAgentInfo($uccxId);

                if (empty($data)) {
                    continue;
                }

                $dialogInfo = $this->getDialogInfo($uccxId, $data);
                $this->updateAgentRealtimeState($employee->id, $uccxId, $data, $dialogInfo);
                $successCount++;

            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        return ['success' => $successCount, 'error' => $errorCount];
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
     * Actualiza la tabla agent_realtime_states.
     */
    public function updateAgentRealtimeState(
        int $employeeId,
        string $externalId,
        array $data,
        array $dialogInfo = [],
    ): void {
        $state = strtoupper($data['state'] ?? 'UNKNOWN');
        $reasonCode = $data['reasonCode'] ?? null;
        $stateChangeTime = $data['stateChangeTime'] ?? null;

        if (is_array($reasonCode)) {
            $reasonCode = $reasonCode['label'] ?? ($reasonCode['id'] ?? 'N/A');
        }

        if ($reasonCode !== null) {
            $reasonCode = strtoupper((string) $reasonCode);
        }

        $lastChangedAt = now()->utc();

        $existingState = DB::table('agent_realtime_states')
            ->where('employee_id', $employeeId)
            ->first();

        if ($stateChangeTime) {
            try {
                $lastChangedAt = Carbon::parse($stateChangeTime)->utc();

                if ($lastChangedAt->isFuture()) {
                    $lastChangedAt = now()->utc();
                }
            } catch (\Exception $e) {
                Log::error("Error parseando stateChangeTime ({$stateChangeTime}) para agente {$externalId}: ".$e->getMessage());
            }
        } else {
            if ($existingState
                && strtoupper((string) $existingState->current_state) === $state
                && strtoupper((string) ($existingState->reason_code ?? '')) === ($reasonCode ?? '')
            ) {
                $lastChangedAt = Carbon::parse($existingState->last_changed_at)->utc();
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
