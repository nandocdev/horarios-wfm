<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Services;

use App\Modules\ConnectModule\Enums\ContactDisposition;
use App\Shared\Support\Metrics\ServiceQualityMetrics;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de analíticas para el Dashboard Operativo del Contact Center.
 *
 * Proporciona métricas en tiempo real y del día actual para supervisores de piso.
 * Todas las consultas usan agregación en PostgreSQL para máximo rendimiento.
 */
final class CallCenterAnalyticsService
{
    private int $slaThresholdSeconds;

    private string $abandonedDispositionsSql;

    public function __construct()
    {
        $this->slaThresholdSeconds = (int) config('contact-center.sla_threshold_seconds', 20);
        $this->abandonedDispositionsSql = ContactDisposition::abandonedIdsSql();
    }

    /**
     * Métricas principales del día actual en tiempo real.
     *
     * @param  string|null  $dateFrom  Fecha inicio (Y-m-d). Null = hoy.
     * @param  string|null  $dateTo  Fecha fin exclusive (Y-m-d). Null = mañana.
     * @param  array|null  $employeeIds  IDs de empleados permitidos. Null = sin filtro.
     */
    public function getRealtimeMetrics(?string $dateFrom = null, ?string $dateTo = null, ?array $employeeIds = null): array
    {
        $abandoned = $this->abandonedDispositionsSql;

        $conditions = $this->buildDateCondition('ivr_started_at', $dateFrom, $dateTo);
        $conditions .= $this->buildEmployeeCondition('employee_id', $employeeIds);

        $stats = DB::selectOne("
            SELECT 
                COUNT(*) as total_calls,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as handled,
                SUM(CASE WHEN status = 'abandoned' OR contact_disposition IN ({$abandoned}) THEN 1 ELSE 0 END) as abandoned,
                SUM(CASE WHEN status = 'closed' AND queue_time <= ? THEN 1 ELSE 0 END) as within_sla,
                ROUND(AVG(CASE WHEN status = 'closed' THEN talk_time END), 1) as avg_talk_time,
                ROUND(AVG(CASE WHEN status = 'closed' THEN talk_time + work_time END), 1) as avg_handle_time,
                ROUND(AVG(queue_time), 1) as avg_queue_time
            FROM call_records
            WHERE {$conditions}
        ", $this->buildParams($this->slaThresholdSeconds, $dateFrom, $dateTo, $employeeIds));

        $totalCalls = (int) ($stats->total_calls ?? 0);
        $handled = (int) ($stats->handled ?? 0);
        $abandonedCount = (int) ($stats->abandoned ?? 0);
        $withinSla = (int) ($stats->within_sla ?? 0);

        $agentStates = DB::select('
            SELECT 
                TRIM(current_state) as state,
                COUNT(*) as agents
            FROM agent_realtime_states
            GROUP BY TRIM(current_state)
        ');

        $agents = collect($agentStates)->pluck('agents', 'state')->toArray();

        return [
            'total_calls' => $totalCalls,
            'handled' => $handled,
            'abandoned' => $abandonedCount,
            'abandon_rate' => ServiceQualityMetrics::abandonmentRate($abandonedCount, $totalCalls),
            'service_level' => ServiceQualityMetrics::serviceLevel($withinSla, $totalCalls),
            'avg_talk_time' => (float) ($stats->avg_talk_time ?? 0),
            'avg_handle_time' => (float) ($stats->avg_handle_time ?? 0),
            'avg_queue_time' => (float) ($stats->avg_queue_time ?? 0),
            'agents_ready' => $agents['Ready'] ?? 0,
            'agents_talking' => $agents['Talking'] ?? 0,
            'agents_not_ready' => $agents['Not Ready'] ?? 0,
            'total_agents' => array_sum($agents),
        ];
    }

    /**
     * Estado de las colas: volumen, SL, tiempos, abandono.
     */
    public function getQueueStatus(?string $dateFrom = null, ?string $dateTo = null, ?array $employeeIds = null): array
    {
        $abandoned = $this->abandonedDispositionsSql;

        $conditions = $this->buildDateCondition('cr.ivr_started_at', $dateFrom, $dateTo);
        $conditions .= $this->buildEmployeeCondition('cr.employee_id', $employeeIds);

        $results = DB::select("
            SELECT 
                cq.name as queue_name,
                cq.aht_goal,
                COUNT(*) as total_calls,
                SUM(CASE WHEN cr.status = 'closed' THEN 1 ELSE 0 END) as handled,
                SUM(CASE WHEN cr.status = 'abandoned' OR cr.contact_disposition IN ({$abandoned}) THEN 1 ELSE 0 END) as abandoned,
                ROUND(AVG(cr.queue_time), 1) as avg_queue_time,
                MAX(cr.queue_time) as max_queue_time,
                ROUND(AVG(CASE WHEN cr.status = 'closed' THEN cr.talk_time + cr.work_time END), 1) as avg_handle_time,
                SUM(CASE WHEN cr.status = 'closed' AND cr.queue_time <= ? THEN 1 ELSE 0 END) as within_sla
            FROM call_records cr
            JOIN call_queues cq ON cr.queue_id = cq.id
            WHERE {$conditions}
            GROUP BY cq.name, cq.aht_goal
            ORDER BY total_calls DESC
        ", $this->buildParams($this->slaThresholdSeconds, $dateFrom, $dateTo, $employeeIds));

        return array_map(function ($row) {
            $totalCalls = (int) $row->total_calls;
            $handled = (int) $row->handled;
            $abandoned = (int) $row->abandoned;
            $withinSla = (int) $row->within_sla;

            return [
                'queue_name' => $row->queue_name,
                'aht_goal' => $row->aht_goal,
                'total_calls' => $totalCalls,
                'handled' => $handled,
                'abandoned' => $abandoned,
                'abandon_rate' => ServiceQualityMetrics::abandonmentRate($abandoned, $totalCalls),
                'avg_queue_time' => (float) $row->avg_queue_time,
                'max_queue_time' => (float) $row->max_queue_time,
                'avg_handle_time' => (float) $row->avg_handle_time,
                'sla_pct' => ServiceQualityMetrics::serviceLevel($withinSla, $totalCalls),
            ];
        }, $results);
    }

    /**
     * Distribución horaria de llamadas del día actual.
     */
    public function getHourlyVolumeToday(?string $dateFrom = null, ?string $dateTo = null, ?array $employeeIds = null): array
    {
        $abandoned = $this->abandonedDispositionsSql;

        $conditions = $this->buildDateCondition('ivr_started_at', $dateFrom, $dateTo);
        $conditions .= $this->buildEmployeeCondition('employee_id', $employeeIds);

        $results = DB::select("
            SELECT 
                EXTRACT(HOUR FROM ivr_started_at)::int as hour_of_day,
                COUNT(*) as total_calls,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as handled,
                SUM(CASE WHEN status = 'abandoned' OR contact_disposition IN ({$abandoned}) THEN 1 ELSE 0 END) as abandoned,
                ROUND(AVG(talk_time), 1) as avg_talk_time,
                ROUND(AVG(queue_time), 1) as avg_queue_time
            FROM call_records
            WHERE {$conditions}
            GROUP BY EXTRACT(HOUR FROM ivr_started_at)
            ORDER BY hour_of_day
        ", $this->buildParams(null, $dateFrom, $dateTo, $employeeIds));

        return array_map(fn ($row) => [
            'hour_of_day' => (int) $row->hour_of_day,
            'total_calls' => (int) $row->total_calls,
            'handled' => (int) $row->handled,
            'abandoned' => (int) $row->abandoned,
            'abandon_rate' => ServiceQualityMetrics::abandonmentRate((int) $row->abandoned, (int) $row->total_calls),
            'avg_talk_time' => (float) $row->avg_talk_time,
            'avg_queue_time' => (float) $row->avg_queue_time,
        ], $results);
    }

    /**
     * Top N agentes por volumen de llamadas.
     */
    public function getTopAgentsToday(int $limit = 10, ?string $dateFrom = null, ?string $dateTo = null, ?array $employeeIds = null): array
    {
        $conditions = $this->buildDateCondition('acp.start_time', $dateFrom, $dateTo);
        $conditions .= $this->buildEmployeeCondition('acp.employee_id', $employeeIds);

        return DB::select("
            SELECT 
                acp.employee_id,
                e.first_name || ' ' || e.last_name as agent_name,
                COUNT(*) as total_calls,
                ROUND(AVG(acp.talk_time), 1) as avg_talk_time,
                ROUND(AVG(acp.total_duration), 1) as avg_handle_time
            FROM agent_call_performance acp
            JOIN employees e ON acp.employee_id = e.id
            WHERE {$conditions}
            GROUP BY acp.employee_id, e.first_name, e.last_name
            ORDER BY total_calls DESC
            LIMIT ?
        ", array_merge(
            $this->buildParams(null, $dateFrom, $dateTo, $employeeIds),
            [$limit]
        ));
    }

    /**
     * Resumen macro de métricas para el dashboard general.
     * Combina volume, handled, abandoned, SLA en una sola consulta.
     */
    public function getSummaryMetrics(?string $dateFrom = null, ?string $dateTo = null, ?array $employeeIds = null): array
    {
        $abandoned = $this->abandonedDispositionsSql;

        $conditions = $this->buildDateCondition('ivr_started_at', $dateFrom, $dateTo);
        $conditions .= $this->buildEmployeeCondition('employee_id', $employeeIds);

        $stats = DB::selectOne("
            SELECT 
                COUNT(*) as total_volume,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as total_handled,
                SUM(CASE WHEN status = 'abandoned' OR contact_disposition IN ({$abandoned}) THEN 1 ELSE 0 END) as total_abandoned,
                SUM(CASE WHEN status = 'closed' AND queue_time <= ? THEN 1 ELSE 0 END) as calls_within_sla
            FROM call_records
            WHERE {$conditions}
        ", $this->buildParams($this->slaThresholdSeconds, $dateFrom, $dateTo, $employeeIds));

        $totalVolume = (int) ($stats->total_volume ?? 0);
        $totalHandled = (int) ($stats->total_handled ?? 0);
        $totalAbandoned = (int) ($stats->total_abandoned ?? 0);
        $callsWithinSla = (int) ($stats->calls_within_sla ?? 0);

        return [
            'total_volume' => $totalVolume,
            'total_handled' => $totalHandled,
            'abandon_rate' => ServiceQualityMetrics::abandonmentRate($totalAbandoned, $totalVolume),
            'sla' => ServiceQualityMetrics::serviceLevel($callsWithinSla, $totalVolume),
        ];
    }

    /**
     * Construye condición WHERE para rango de fechas.
     */
    private function buildDateCondition(string $column, ?string $dateFrom, ?string $dateTo): string
    {
        if ($dateFrom && $dateTo) {
            return "{$column} >= ? AND {$column} < ?";
        }

        if ($dateFrom) {
            return "{$column} >= ?";
        }

        return "{$column} >= CURRENT_DATE";
    }

    /**
     * Construye condición WHERE para filtro de empleados.
     */
    private function buildEmployeeCondition(string $column, ?array $employeeIds): string
    {
        if (is_array($employeeIds)) {
            return " AND {$column} IN (".implode(',', array_fill(0, count($employeeIds), '?')).')';
        }

        return '';
    }

    /**
     * Construye array de parámetros bind en orden: sla, dateFrom, dateTo, employeeIds.
     */
    private function buildParams(?int $slaThreshold, ?string $dateFrom, ?string $dateTo, ?array $employeeIds): array
    {
        $params = [];

        if ($slaThreshold !== null) {
            $params[] = $slaThreshold;
        }

        if ($dateFrom) {
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $params[] = $dateTo;
        }

        if (is_array($employeeIds)) {
            array_push($params, ...$employeeIds);
        }

        return $params;
    }
}
