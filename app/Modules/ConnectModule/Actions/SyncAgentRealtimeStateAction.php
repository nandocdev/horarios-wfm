<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Services\CuicReportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncAgentRealtimeStateAction
{
    public function __construct(
        private CuicReportService $cuicService
    ) {}

    /**
     * Sincroniza el estado en tiempo real de un agente específico desde CUIC.
     *
     * @param  string  $agentLoginId  El username de Cisco (ej. 'jballis')
     * @param  int  $employeeId  ID local del empleado
     */
    public function execute(string $agentLoginId, int $employeeId): ?array
    {
        try {
            $rows = $this->cuicService->executeAgentRealtimeSnapshot('agent_realtime_detail', [$agentLoginId]);

            if ($rows->isEmpty()) {
                Log::warning("[CUIC] No se encontró información en tiempo real para el agente: {$agentLoginId}");

                return null;
            }

            $data = $rows->first();

            // Mapeo de campos (ajustar según el JSON real del reporte de CUIC)
            // Normalmente: [ "Agent Name", "Login ID", "State", "State Duration", "Reason Code", ... ]
            // Pero el JSON de initialData suele venir con nombres de columnas amigables o índices.

            $state = $data['State'] ?? $data['agent_state'] ?? 'UNKNOWN';
            $duration = (int) ($data['State Duration'] ?? $data['duration'] ?? 0);
            $reasonCode = $data['Reason Code'] ?? $data['reason_code'] ?? null;

            $updateData = [
                'employee_id' => $employeeId,
                'current_state' => strtoupper($state),
                'state_duration' => $duration,
                'reason_code' => $reasonCode,
                'last_changed_at' => now()->subSeconds($duration),
                'updated_at' => now(),
            ];

            DB::table('agent_realtime_states')->updateOrInsert(
                ['employee_id' => $employeeId],
                $updateData
            );

            return $updateData;

        } catch (\Exception $e) {
            Log::error("[CUIC] Error sincronizando estado de agente {$agentLoginId}: ".$e->getMessage());

            return null;
        }
    }
}
