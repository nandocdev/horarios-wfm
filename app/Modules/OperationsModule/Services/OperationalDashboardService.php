<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Services;

use App\Modules\ConnectModule\Enums\ContactDisposition;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de analíticas para el Dashboard Táctico de Operaciones.
 *
 * Proporciona métricas históricas, tendencias y comparativos para supervisores y jefaturas.
 * Todas las consultas usan agregación en PostgreSQL para máximo rendimiento.
 */
final class OperationalDashboardService
{
    private int $slaThresholdSeconds;

    private string $abandonedDispositionsSql;

    public function __construct()
    {
        $this->slaThresholdSeconds = (int) config('contact-center.sla_threshold_seconds', 20);
        $this->abandonedDispositionsSql = ContactDisposition::abandonedIdsSql();
    }

    /**
     * Resumen diario de la operación (últimos N días).
     *
     * Retorna array de días con:
     * - call_date, total_calls, handled, abandoned
     * - abandon_rate, service_level, avg_talk_time, avg_handle_time
     * - avg_queue_time, unique_agents
     */
    public function getDailySummary(int $days = 7): array
    {
        $abandoned = $this->abandonedDispositionsSql;

        $results = DB::select("
            WITH daily_stats AS (
                SELECT 
                    DATE(ivr_started_at) as call_date,
                    COUNT(*) as total_calls,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as handled,
                    SUM(CASE WHEN status = 'abandoned' OR contact_disposition IN ({$abandoned}) THEN 1 ELSE 0 END) as abandoned,
                    SUM(CASE WHEN status = 'closed' AND queue_time <= ? THEN 1 ELSE 0 END) as within_sla,
                    ROUND(AVG(CASE WHEN status = 'closed' THEN talk_time END), 1) as avg_talk_time,
                    ROUND(AVG(CASE WHEN status = 'closed' THEN talk_time + work_time END), 1) as avg_handle_time,
                    ROUND(AVG(queue_time), 1) as avg_queue_time,
                    COUNT(DISTINCT employee_id) as unique_agents
                FROM call_records
                WHERE ivr_started_at >= CURRENT_DATE - (? || ' days')::INTERVAL
                GROUP BY DATE(ivr_started_at)
            )
            SELECT 
                call_date,
                total_calls,
                handled,
                abandoned,
                ROUND(abandoned::numeric / NULLIF(total_calls, 0) * 100, 1) as abandon_rate,
                ROUND(within_sla::numeric / NULLIF(handled, 0) * 100, 1) as service_level,
                avg_talk_time,
                avg_handle_time,
                avg_queue_time,
                unique_agents
            FROM daily_stats
            ORDER BY call_date DESC
        ", [$this->slaThresholdSeconds, $days]);

        return array_map(fn ($row) => [
            'call_date' => $row->call_date,
            'total_calls' => (int) $row->total_calls,
            'handled' => (int) $row->handled,
            'abandoned' => (int) $row->abandoned,
            'abandon_rate' => (float) ($row->abandon_rate ?? 0),
            'service_level' => (float) ($row->service_level ?? 0),
            'avg_talk_time' => (float) ($row->avg_talk_time ?? 0),
            'avg_handle_time' => (float) ($row->avg_handle_time ?? 0),
            'avg_queue_time' => (float) ($row->avg_queue_time ?? 0),
            'unique_agents' => (int) $row->unique_agents,
        ], $results);
    }

    /**
     * Rendimiento por agente (últimos N días).
     *
     * Retorna array de agentes con:
     * - employee_id, agent_name, team_name
     * - total_calls, avg_talk_time, avg_work_time, avg_handle_time
     * - talk_minutes, work_minutes, ready_minutes, not_ready_minutes
     * - occupancy_pct, productivity_pct
     */
    public function getAgentPerformance(int $days = 7): array
    {
        $results = DB::select("
            WITH agent_performance AS (
                SELECT 
                    acp.employee_id,
                    e.first_name || ' ' || e.last_name as agent_name,
                    t.name as team_name,
                    COUNT(*) as total_calls,
                    ROUND(AVG(acp.talk_time), 1) as avg_talk_time,
                    ROUND(AVG(acp.work_time), 1) as avg_work_time,
                    ROUND(AVG(acp.total_duration), 1) as avg_handle_time
                FROM agent_call_performance acp
                JOIN employees e ON acp.employee_id = e.id
                LEFT JOIN teams t ON e.team_id = t.id
                WHERE acp.start_time >= CURRENT_DATE - (? || ' days')::INTERVAL
                GROUP BY acp.employee_id, e.first_name, e.last_name, t.name
            ),
            agent_states AS (
                SELECT 
                    employee_id,
                    ROUND(SUM(CASE WHEN TRIM(agent_state) = 'Talking' THEN duration ELSE 0 END) / 60.0, 1) as talk_minutes,
                    ROUND(SUM(CASE WHEN TRIM(agent_state) = 'Work' THEN duration ELSE 0 END) / 60.0, 1) as work_minutes,
                    ROUND(SUM(CASE WHEN TRIM(agent_state) = 'Ready' THEN duration ELSE 0 END) / 60.0, 1) as ready_minutes,
                    ROUND(SUM(CASE WHEN TRIM(agent_state) = 'Not Ready' THEN duration ELSE 0 END) / 60.0, 1) as not_ready_minutes,
                    ROUND(SUM(CASE WHEN TRIM(agent_state) = 'Logged-in' THEN duration ELSE 0 END) / 60.0, 1) as login_minutes
                FROM agent_state_transitions
                WHERE transition_time >= CURRENT_DATE - (? || ' days')::INTERVAL
                GROUP BY employee_id
            )
            SELECT 
                ap.*,
                ast.talk_minutes,
                ast.work_minutes,
                ast.ready_minutes,
                ast.not_ready_minutes,
                ROUND(
                    (ast.talk_minutes + ast.work_minutes) / 
                    NULLIF(ast.talk_minutes + ast.work_minutes + ast.ready_minutes + ast.not_ready_minutes + ast.login_minutes, 0) 
                    * 100, 1
                ) as occupancy_pct,
                ROUND(
                    ast.talk_minutes / 
                    NULLIF(ast.talk_minutes + ast.ready_minutes + ast.not_ready_minutes, 0) 
                    * 100, 1
                ) as productivity_pct
            FROM agent_performance ap
            LEFT JOIN agent_states ast ON ap.employee_id = ast.employee_id
            ORDER BY ap.total_calls DESC
        ", [$days, $days]);

        return array_map(fn ($row) => [
            'employee_id' => (int) $row->employee_id,
            'agent_name' => $row->agent_name,
            'team_name' => $row->team_name,
            'total_calls' => (int) $row->total_calls,
            'avg_talk_time' => (float) $row->avg_talk_time,
            'avg_work_time' => (float) $row->avg_work_time,
            'avg_handle_time' => (float) $row->avg_handle_time,
            'talk_minutes' => (float) ($row->talk_minutes ?? 0),
            'work_minutes' => (float) ($row->work_minutes ?? 0),
            'ready_minutes' => (float) ($row->ready_minutes ?? 0),
            'not_ready_minutes' => (float) ($row->not_ready_minutes ?? 0),
            'occupancy_pct' => (float) ($row->occupancy_pct ?? 0),
            'productivity_pct' => (float) ($row->productivity_pct ?? 0),
        ], $results);
    }

    /**
     * Distribución de estados de agentes (últimos N días).
     *
     * Retorna array con:
     * - agent_state, total_transitions, total_minutes
     * - avg_duration_seconds, pct_of_total
     */
    public function getStateDistribution(int $days = 7): array
    {
        $results = DB::select("
            SELECT 
                TRIM(agent_state) as agent_state,
                COUNT(*) as total_transitions,
                ROUND(SUM(duration) / 60.0, 1) as total_minutes,
                ROUND(AVG(duration), 1) as avg_duration_seconds,
                ROUND(
                    SUM(duration)::numeric / 
                    NULLIF((SELECT SUM(duration) FROM agent_state_transitions WHERE transition_time >= CURRENT_DATE - (? || ' days')::INTERVAL), 0) 
                    * 100, 1
                ) as pct_of_total
            FROM agent_state_transitions
            WHERE transition_time >= CURRENT_DATE - (? || ' days')::INTERVAL
            GROUP BY TRIM(agent_state)
            ORDER BY total_minutes DESC
        ", [$days, $days]);

        return array_map(fn ($row) => [
            'agent_state' => $row->agent_state,
            'total_transitions' => (int) $row->total_transitions,
            'total_minutes' => (float) $row->total_minutes,
            'avg_duration_seconds' => (float) $row->avg_duration_seconds,
            'pct_of_total' => (float) ($row->pct_of_total ?? 0),
        ], $results);
    }

    /**
     * Adherencia a horario: agentes vs programación (día específico).
     *
     * Retorna array de agentes con:
     * - employee_id, agent_name
     * - scheduled_start, scheduled_end
     * - first_login, last_logout
     * - late_minutes, early_exit_minutes
     * - arrival_status: 'Ausente', 'Tardanza', 'Puntual'
     */
    public function getScheduleAdherence(?string $date = null): array
    {
        $targetDate = $date ?? Carbon::today()->toDateString();
        $dayOfWeek = Carbon::parse($targetDate)->dayOfWeekIso;

        $results = DB::select("
            WITH scheduled_agents AS (
                SELECT 
                    wsa.employee_id,
                    e.first_name || ' ' || e.last_name as agent_name,
                    wsa.start_time as scheduled_start,
                    wsa.end_time as scheduled_end,
                    wsa.lunch_start_time,
                    wsa.lunch_end_time
                FROM weekly_schedule_assignments wsa
                JOIN employees e ON wsa.employee_id = e.id
                JOIN weekly_schedules ws ON wsa.weekly_schedule_id = ws.id
                WHERE ws.week_start_date <= ?
                  AND ws.week_end_date >= ?
                  AND wsa.day_of_week = ?
                  AND wsa.is_replaced = false
            ),
            agent_login AS (
                SELECT 
                    employee_id,
                    MIN(transition_time) as first_login,
                    MAX(CASE WHEN TRIM(agent_state) = 'Logout' THEN transition_time END) as last_logout
                FROM agent_state_transitions
                WHERE DATE(transition_time) = ?
                GROUP BY employee_id
            )
            SELECT 
                sa.employee_id,
                sa.agent_name,
                sa.scheduled_start,
                sa.scheduled_end,
                al.first_login,
                al.last_logout,
                ROUND(EXTRACT(EPOCH FROM (al.first_login::timestamp - sa.scheduled_start::timestamp)) / 60, 1) as late_minutes,
                ROUND(EXTRACT(EPOCH FROM (sa.scheduled_end::timestamp - al.last_logout::timestamp)) / 60, 1) as early_exit_minutes,
                CASE 
                    WHEN al.first_login IS NULL THEN 'Ausente'
                    WHEN EXTRACT(EPOCH FROM (al.first_login::timestamp - sa.scheduled_start::timestamp)) / 60 > 5 THEN 'Tardanza'
                    ELSE 'Puntual'
                END as arrival_status
            FROM scheduled_agents sa
            LEFT JOIN agent_login al ON sa.employee_id = al.employee_id
            ORDER BY late_minutes DESC NULLS LAST
        ", [$targetDate, $targetDate, $dayOfWeek, $targetDate]);

        return array_map(fn ($row) => [
            'employee_id' => (int) $row->employee_id,
            'agent_name' => $row->agent_name,
            'scheduled_start' => $row->scheduled_start,
            'scheduled_end' => $row->scheduled_end,
            'first_login' => $row->first_login,
            'last_logout' => $row->last_logout,
            'late_minutes' => (float) ($row->late_minutes ?? 0),
            'early_exit_minutes' => (float) ($row->early_exit_minutes ?? 0),
            'arrival_status' => $row->arrival_status,
        ], $results);
    }

    /**
     * Rankings de agentes: top performers y bottom performers (últimos N días).
     *
     * Retorna array con:
     * - employee_id, agent_name, total_calls, avg_talk_time, avg_handle_time
     * - performance_score (0-100, compuesto: 40% volumen + 30% TMO + 30% AHT)
     */
    public function getAgentRankings(int $days = 7): array
    {
        $results = DB::select("
            WITH agent_metrics AS (
                SELECT 
                    acp.employee_id,
                    e.first_name || ' ' || e.last_name as agent_name,
                    COUNT(*) as total_calls,
                    ROUND(AVG(acp.talk_time), 1) as avg_talk_time,
                    ROUND(AVG(acp.total_duration), 1) as avg_handle_time
                FROM agent_call_performance acp
                JOIN employees e ON acp.employee_id = e.id
                WHERE acp.start_time >= CURRENT_DATE - (? || ' days')::INTERVAL
                GROUP BY acp.employee_id, e.first_name, e.last_name
                HAVING COUNT(*) >= 10
            ),
            bounds AS (
                SELECT 
                    MAX(total_calls) as max_calls,
                    MIN(total_calls) as min_calls,
                    MAX(avg_talk_time) as max_talk,
                    MIN(avg_talk_time) as min_talk,
                    MAX(avg_handle_time) as max_handle,
                    MIN(avg_handle_time) as min_handle
                FROM agent_metrics
            )
            SELECT 
                am.*,
                ROUND(
                    ((am.total_calls - b.min_calls) / NULLIF(b.max_calls - b.min_calls, 0)) * 40 +
                    ((b.max_talk - am.avg_talk_time) / NULLIF(b.max_talk - b.min_talk, 0)) * 30 +
                    ((b.max_handle - am.avg_handle_time) / NULLIF(b.max_handle - b.min_handle, 0)) * 30
                , 1) as performance_score
            FROM agent_metrics am
            CROSS JOIN bounds b
            ORDER BY performance_score DESC
        ", [$days]);

        return array_map(fn ($row) => [
            'employee_id' => (int) $row->employee_id,
            'agent_name' => $row->agent_name,
            'total_calls' => (int) $row->total_calls,
            'avg_talk_time' => (float) $row->avg_talk_time,
            'avg_handle_time' => (float) $row->avg_handle_time,
            'performance_score' => (float) ($row->performance_score ?? 0),
        ], $results);
    }

    /**
     * Rendimiento por cola (últimos N días).
     *
     * Retorna array de colas con:
     * - queue_name, aht_goal, total_calls, handled, abandoned
     * - abandon_rate, avg_talk_time, avg_queue_time, avg_handle_time, sla_pct
     */
    public function getQueuePerformance(int $days = 7): array
    {
        $abandoned = $this->abandonedDispositionsSql;

        $results = DB::select("
            SELECT 
                cq.name as queue_name,
                cq.aht_goal,
                COUNT(*) as total_calls,
                SUM(CASE WHEN cr.status = 'closed' THEN 1 ELSE 0 END) as handled,
                SUM(CASE WHEN cr.status = 'abandoned' OR cr.contact_disposition IN ({$abandoned}) THEN 1 ELSE 0 END) as abandoned,
                ROUND(
                    SUM(CASE WHEN cr.status = 'abandoned' OR cr.contact_disposition IN ({$abandoned}) THEN 1 ELSE 0 END)::numeric 
                    / NULLIF(COUNT(*), 0) * 100, 1
                ) as abandon_rate,
                ROUND(AVG(CASE WHEN cr.status = 'closed' THEN cr.talk_time END), 1) as avg_talk_time,
                ROUND(AVG(cr.queue_time), 1) as avg_queue_time,
                ROUND(AVG(CASE WHEN cr.status = 'closed' THEN cr.talk_time + cr.work_time END), 1) as avg_handle_time,
                ROUND(
                    SUM(CASE WHEN cr.status = 'closed' AND cr.queue_time <= ? THEN 1 ELSE 0 END)::numeric 
                    / NULLIF(SUM(CASE WHEN cr.status = 'closed' THEN 1 ELSE 0 END), 0) * 100, 1
                ) as sla_pct
            FROM call_records cr
            JOIN call_queues cq ON cr.queue_id = cq.id
            WHERE cr.ivr_started_at >= CURRENT_DATE - (? || ' days')::INTERVAL
            GROUP BY cq.name, cq.aht_goal
            ORDER BY total_calls DESC
        ", [$this->slaThresholdSeconds, $days]);

        return array_map(fn ($row) => [
            'queue_name' => $row->queue_name,
            'aht_goal' => $row->aht_goal ? (int) $row->aht_goal : null,
            'total_calls' => (int) $row->total_calls,
            'handled' => (int) $row->handled,
            'abandoned' => (int) $row->abandoned,
            'abandon_rate' => (float) ($row->abandon_rate ?? 0),
            'avg_talk_time' => (float) ($row->avg_talk_time ?? 0),
            'avg_queue_time' => (float) ($row->avg_queue_time ?? 0),
            'avg_handle_time' => (float) ($row->avg_handle_time ?? 0),
            'sla_pct' => (float) ($row->sla_pct ?? 0),
        ], $results);
    }
}
