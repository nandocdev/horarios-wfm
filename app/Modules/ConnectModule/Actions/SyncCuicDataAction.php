<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\ConnectModule\Models\ChatRecord;
use App\Modules\ConnectModule\Services\CuicReportService;
use App\Shared\Contracts\Employees\EmployeeLookupRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

/**
 * Orquestador ETL para sincronización de datos desde Cisco CUIC.
 *
 * Inyecta EmployeeLookupRepositoryInterface para resolver employee_id
 * sin acoplar directamente al modelo Eloquent de EmployeesModule.
 *
 * [RIESGOS]
 * - El warmup carga TODOS los empleados activos en memoria → ~200-500 registros típico.
 *   Aceptable para CLI; no usar en requests HTTP de larga duración sin supervisión.
 * - Colisión de nombre completo (nameCache) si dos empleados tienen el mismo nombre.
 *   La única mitigación real es asegurar que cisco_username esté siempre configurado.
 * - upsert() sin transacción explícita: cada sync-type es atómica en sí misma pero
 *   el conjunto de 3 syncs no es transaccional entre sí.
 */
final class SyncCuicDataAction
{
    public function __construct(
        private readonly CuicReportService $cuic,
        private readonly EmployeeLookupRepositoryInterface $employees,
    ) {}

    /**
     * @return array<string, int> Resumen de registros procesados por tipo
     */
    public function execute(CarbonInterface $start, CarbonInterface $end): array
    {
        $stats = [
            'transitions' => [],
            'performance' => 0,
            'chats' => 0,
        ];

        // Ventana operativa: 05:00 a 18:00 para la mayoría de los reportes
        $isWithinWindow = $this->isWithinOperatingWindow($start, $end);

        if ($isWithinWindow) {
            $stats['transitions'] = $this->syncTransitions($start, $end);
            $stats['performance'] = $this->syncPerformance($start, $end);
        }

        if ($isWithinWindow) {
            $stats['chats'] = $this->syncChats($start, $end);
        }

        Log::info('[CUIC-ETL] Sincronización finalizada', [
            'transitions' => count($stats['transitions']),
            'performance' => $stats['performance'],
            'chats' => $stats['chats'],
        ]);

        return $stats;
    }

    /**
     * Sincroniza Estados y Transiciones (Voz).
     *
     * @return array<int> IDs de empleados actualizados
     */
    private function syncTransitions(CarbonInterface $start, CarbonInterface $end): array
    {
        $rows = $this->cuic->executeReportWithFilter('agent_detail', $start, $end);

        $upserts = $rows->map(fn (array $row) => [
            'agent_login_id' => $row['agent_login_id'],
            'employee_id' => $this->employees->resolve($row['agent_login_id'], $row['agent_name'] ?? null),
            'transition_time' => Carbon::createFromTimestampMs((int) $row['transition_time'], 'UTC')->tz(config('app.timezone')),
            'agent_state' => $row['agent_state'],
            'reason_code' => $row['reason_code'] ?? null,
            'duration' => (int) ($row['duration'] ?? 0),
            'created_at' => now(),
            'updated_at' => now(),
        ])->unique(fn ($item) => $item['agent_login_id'].$item['transition_time']->toDateTimeString().$item['agent_state'])
            ->values()
            ->toArray();

        if (empty($upserts)) {
            return [];
        }

        AgentStateTransition::upsert(
            $upserts,
            ['agent_login_id', 'transition_time', 'agent_state'],
            ['reason_code', 'duration', 'employee_id', 'updated_at']
        );

        return collect($upserts)->pluck('employee_id')->filter()->unique()->values()->toArray();
    }

    /**
     * Sincroniza Desempeño y AHT.
     */
    private function syncPerformance(CarbonInterface $start, CarbonInterface $end): int
    {
        $rows = $this->cuic->executeReportWithFilter('agent_performance_detail', $start, $end);

        $upserts = $rows->map(fn (array $row) => [
            'agent_login_id' => $row['agent_login_id'],
            'employee_id' => $this->employees->resolve($row['agent_login_id'], $row['agent_name'] ?? null),
            'agent_ext' => $row['agent_extension'] ?? null,
            'start_time' => Carbon::createFromTimestampMs((int) $row['call_start_time'], 'UTC')->tz(config('app.timezone')),
            'end_time' => Carbon::createFromTimestampMs((int) $row['call_end_time'], 'UTC')->tz(config('app.timezone')),
            'total_duration' => (int) ($row['call_duration'] ?? 0),
            'talk_time' => (int) ($row['talk_time'] ?? 0),
            'hold_time' => (int) ($row['hold_time'] ?? 0),
            'work_time' => (int) ($row['wrapup_time'] ?? 0),
            'phone_number' => $row['called_number'] ?? null,
            'ani' => $row['call_ani'] ?? null,
            'csq_name' => $row['call_routed_csq'] ?? null,
            'call_skill' => $row['call_skills'] ?? null,
            'call_type' => $row['type_call'] ?? null,
            'raw_agent_name' => $row['agent_name'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ])->unique(fn ($item) => $item['agent_login_id'].$item['start_time']->toDateTimeString())
            ->values()
            ->toArray();

        if (empty($upserts)) {
            return 0;
        }

        AgentCallPerformance::upsert(
            $upserts,
            ['agent_login_id', 'start_time'],
            ['end_time', 'total_duration', 'talk_time', 'hold_time', 'work_time', 'employee_id', 'updated_at']
        );

        return count($upserts);
    }

    /**
     * Sincroniza Registros de Chat e Interacciones.
     */
    private function syncChats(CarbonInterface $start, CarbonInterface $end): int
    {
        $rows = $this->cuic->executeReportWithFilter('agent_chat_detail', $start, $end);

        $upserts = $rows->map(fn (array $row) => [
            'conversation_id' => $row['chat_originator'],
            'agent_login_id' => $row['agent_login_id'],
            'employee_id' => $this->employees->resolve($row['agent_login_id'], $row['agent_name'] ?? null),
            'start_time' => Carbon::createFromTimestampMs((int) $row['chat_start_time'], 'UTC')->tz(config('app.timezone')),
            'end_time' => Carbon::createFromTimestampMs((int) $row['chat_end_time'], 'UTC')->tz(config('app.timezone')),
            'accepted_at' => Carbon::createFromTimestampMs((int) $row['chat_start_time'], 'UTC')->tz(config('app.timezone'))->addSeconds((int) $row['accept_time']),
            'total_duration' => (int) ($row['chat_duration'] ?? 0),
            'talk_time' => (int) ($row['talk_time'] ?? 0),
            'author_identifier' => $row['chat_originator'] ?? null,
            'destination_identifier' => $row['chat_destination'] ?? null,
            'chat_type' => $row['chat_type'] ?? null,
            'chat_source' => $row['chat_source'] ?? null,
            'chat_rating' => $row['chat_rating'] ?? null,
            'raw_agent_name' => $row['agent_name'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ])->unique('conversation_id')
            ->values()
            ->toArray();

        if (empty($upserts)) {
            return 0;
        }

        ChatRecord::upsert(
            $upserts,
            ['conversation_id'],
            ['end_time', 'total_duration', 'talk_time', 'employee_id', 'updated_at']
        );

        return count($upserts);
    }

    /**
     * Determina si el intervalo de tiempo tiene solapamiento con la ventana operativa (05:00 - 18:00).
     */
    private function isWithinOperatingWindow(CarbonInterface $start, CarbonInterface $end): bool
    {
        $hourStart = (int) $start->format('H');
        $hourEnd = (int) $end->format('H');

        // Si el intervalo termina antes de las 05:00 o empieza a las 18:00 o después, está fuera.
        if ($hourEnd < 5 || $hourStart >= 18) {
            return false;
        }

        return true;
    }
}
