<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Enums\ContactDisposition;
use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CallRecord;
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
 *   el conjunto de 4 syncs no es transaccional entre sí.
 */
final class SyncCuicDataAction
{
    /** @var array<string, int> */
    private array $queueCache = [];

    public function __construct(
        private readonly CuicReportService $cuic,
        private readonly EmployeeLookupRepositoryInterface $employees,
    ) {}

    /**
     * @return array<string, int> Resumen de registros procesados por tipo
     */
    public function execute(CarbonInterface $start, CarbonInterface $end): array
    {
        $this->primeQueueCache();

        $stats = [
            'transitions' => [],
            'performance' => 0,
            'calls' => 0,
            'chats' => 0,
        ];

        // Ventana operativa: 05:00 a 18:00 para la mayoría de los reportes
        $isWithinWindow = $this->isWithinOperatingWindow($start, $end);

        if ($isWithinWindow) {
            $stats['transitions'] = $this->syncTransitions($start, $end);
            $stats['performance'] = $this->syncPerformance($start, $end);
        }

        // Este reporte se registra TODO (sin restricción de ventana) según solicitud
        $stats['calls'] = $this->syncCalls($start, $end);

        if ($isWithinWindow) {
            $stats['chats'] = $this->syncChats($start, $end);
        }

        Log::info('[CUIC-ETL] Sincronización finalizada', [
            'transitions' => count($stats['transitions']),
            'performance' => $stats['performance'],
            'calls' => $stats['calls'],
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
     * Sincroniza Registros Técnicos de Llamadas (CSQ).
     */
    private function syncCalls(CarbonInterface $start, CarbonInterface $end): int
    {
        $rows = $this->cuic->executeReportWithFilter('agent_csq_detail', $start, $end);
        Log::info('[CUIC-ETL] Datos de llamadas: '.count($rows));

        $upserts = $rows->map(function (array $row): ?array {
            $ciscoCallId = trim((string) ($row['session_id_seq'] ?? ''));
            $sequenceNumber = $row['sequence_num'] ?? null;
            $startTimestamp = (int) ($row['start_time'] ?? 0);
            $endTimestamp = (int) ($row['end_time'] ?? 0);

            if ($ciscoCallId === '' || ! is_numeric($sequenceNumber) || $startTimestamp <= 0) {
                Log::warning('[CUIC-ETL] Segmento de llamada omitido por identidad o fecha incompleta.', [
                    'session_id_seq' => $row['session_id_seq'] ?? null,
                    'sequence_num' => $sequenceNumber,
                    'start_time' => $row['start_time'] ?? null,
                ]);

                return null;
            }

            // En algunos CUIC la columna es 'resource_name', en otros es 'agent_name' o simplemente no existe si no hubo agente
            $agentLoginId = $row['resource_name'] ?? $row['agent_login_id'] ?? null;
            $agentName = $row['agent_name'] ?? $row['resource_name'] ?? null;
            $contactDisposition = (int) ($row['contact_disposition'] ?? 0);
            $rawQueueName = $row['csq_names'] ?? null;
            $rawQueueName = is_array($rawQueueName)
                ? implode(', ', array_map(static fn (mixed $name): string => (string) $name, $rawQueueName))
                : (string) $rawQueueName;

            $employeeId = $this->employees->resolve($agentLoginId, $agentName);

            return [
                'cisco_call_id' => $ciscoCallId,
                'sequence_number' => (int) $sequenceNumber,
                'phone_number' => $row['originator_dn'] ?? 'Unknown',
                'destination_number' => $row['destination_dn'] ?? null,
                'dialed_number' => $row['dialed_number'] ?? $row['called_number'] ?? $row['dialed_dn'] ?? null,
                'application_name' => $row['application_name'] ?? $row['application'] ?? $row['app_name'] ?? null,
                'ivr_started_at' => Carbon::createFromTimestampMs($startTimestamp, 'UTC')->tz(config('app.timezone')),
                'ivr_ended_at' => $endTimestamp > 0
                    ? Carbon::createFromTimestampMs($endTimestamp, 'UTC')->tz(config('app.timezone'))
                    : null,
                'talk_time' => (int) ($row['talk_time'] ?? 0),
                'ring_time' => (int) ($row['ring_time'] ?? 0),
                'queue_time' => (int) ($row['queue_time'] ?? 0),
                'work_time' => (int) ($row['work_time'] ?? 0),
                'contact_disposition' => $contactDisposition,
                'queue_id' => $this->resolveQueueId($rawQueueName),
                'employee_id' => $employeeId,
                'raw_agent_name' => $agentName,
                'status' => ContactDisposition::statusFor($contactDisposition),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->filter()
            ->unique(fn (array $item): string => $item['cisco_call_id'].'-'.$item['sequence_number'])
            ->values()
            ->toArray();
        Log::info('[CUIC-ETL] Intentando upsert de '.count($upserts).' registros en call_records');

        if (empty($upserts)) {
            return 0;
        }

        CallRecord::upsert(
            $upserts,
            ['cisco_call_id', 'sequence_number'],
            [
                'phone_number', 'destination_number', 'dialed_number', 'application_name',
                'ivr_started_at', 'ivr_ended_at', 'talk_time', 'ring_time', 'queue_time',
                'work_time', 'contact_disposition', 'employee_id', 'queue_id', 'raw_agent_name',
                'status', 'updated_at',
            ]
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
     * Precarga el cache de colas desde call_queues.
     * Indexado por nombre normalizado y por finesse_queue_id.
     */
    private function primeQueueCache(): void
    {
        $queues = CallQueue::select(['id', 'name', 'finesse_queue_id'])->get();

        foreach ($queues as $queue) {
            $normalized = $this->normalizeQueueName($queue->name);
            $this->queueCache[$normalized] = $queue->id;

            if ($queue->finesse_queue_id) {
                $this->queueCache["finesse:{$queue->finesse_queue_id}"] = $queue->id;
            }
        }
    }

    /**
     * Normaliza un nombre de cola para comparación: mayúsculas, sin '*' ni espacios extra.
     */
    private function normalizeQueueName(string $name): string
    {
        return strtoupper(trim(str_replace('*', '', $name)));
    }

    /**
     * Resuelve el ID de la cola a partir de su nombre raw de CUIC.
     * Busca primero por nombre normalizado, luego por finesse_queue_id si aplica.
     */
    private function resolveQueueId(?string $rawName): ?int
    {
        if (empty($rawName)) {
            return null;
        }

        $cleanName = $this->normalizeQueueName($rawName);

        return $this->queueCache[$cleanName]
            ?? $this->queueCache["finesse:{$cleanName}"]
            ?? null;
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
