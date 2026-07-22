<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\DTOs\ReportPreviewResult;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;

final class FetchReportDataAction
{
    public function __construct(
        private readonly EloquentReportDataRepository $repository,
    ) {}

    public function execute(string $category, string $subReport, ReportFilterDTO $filters): ReportPreviewResult
    {
        return match ("{$category}.{$subReport}") {
            'attendance.absenteeism' => $this->absenteeism($filters),
            'attendance.tardiness' => $this->tardiness($filters),
            'attendance.leaves' => $this->leaves($filters),
            'attendance.vacations' => $this->vacations($filters),
            'attendance.summary' => $this->attendanceSummary($filters),
            'activities.intraday' => $this->intradayActivities($filters),
            'activities.period' => $this->periodActivities($filters),
            'volume.queue' => $this->volumeDetail($filters),
            'volume.interval' => $this->volumeInterval($filters),
            'volume.summary' => $this->volumeSummary($filters),
            'performance.agent' => $this->agentPerformance($filters),
            'performance.team' => $this->teamPerformance($filters),
            'performance.ranking' => $this->ranking($filters),
            default => throw new \InvalidArgumentException("Reporte no válido: {$category}.{$subReport}"),
        };
    }

    private function absenteeism(ReportFilterDTO $filters): ReportPreviewResult
    {
        $rows = $this->repository->getRawAbsenteeismData($filters);

        $totalMinutes = $rows->sum(fn ($r) => $r->minutesAbsent ?? 0);
        $uniqueEmployees = $rows->pluck('employeeId')->unique()->count();
        $justified = $rows->where('isJustified', true)->count();

        return new ReportPreviewResult(
            title: 'Ausentismo',
            description: 'Detalle de ausencias no justificadas por agente, fecha y causa.',
            rows: $rows,
            columns: [
                ['label' => 'Empleado', 'key' => 'employeeName'],
                ['label' => 'Número', 'key' => 'employeeNumber'],
                ['label' => 'Equipo', 'key' => 'teamName'],
                ['label' => 'Fecha', 'key' => 'date'],
                ['label' => 'Causa', 'key' => 'causeName'],
                ['label' => 'Justificado', 'key' => 'isJustified'],
                ['label' => 'Minutos', 'key' => 'minutesAbsent'],
            ],
            summary: [
                ['label' => 'Total Registros', 'value' => (string) $rows->count(), 'icon' => 'clipboard-document-list'],
                ['label' => 'Empleados Afectados', 'value' => (string) $uniqueEmployees, 'icon' => 'users'],
                ['label' => 'Minutos Perdidos', 'value' => $this->formatDuration($totalMinutes), 'icon' => 'clock'],
                ['label' => 'Justificados', 'value' => $rows->count() > 0 ? round($justified / $rows->count() * 100).'%' : '0%', 'icon' => 'check-badge'],
            ],
        );
    }

    private function tardiness(ReportFilterDTO $filters): ReportPreviewResult
    {
        $rows = $this->repository->getTardinessData($filters);

        $avgMinutes = $rows->avg(fn ($r) => $r->minutesLate ?? 0);
        $uniqueEmployees = $rows->pluck('employeeId')->unique()->count();

        return new ReportPreviewResult(
            title: 'Tardanzas',
            description: 'Registro de tardanzas detectadas, con hora programada vs real y minutos de retraso.',
            rows: $rows,
            columns: [
                ['label' => 'Empleado', 'key' => 'employeeName'],
                ['label' => 'Número', 'key' => 'employeeNumber'],
                ['label' => 'Equipo', 'key' => 'teamName'],
                ['label' => 'Fecha', 'key' => 'date'],
                ['label' => 'Minutos', 'key' => 'minutesLate'],
                ['label' => 'Justificación', 'key' => 'justification'],
            ],
            summary: [
                ['label' => 'Total Tardanzas', 'value' => (string) $rows->count(), 'icon' => 'exclamation-triangle'],
                ['label' => 'Empleados', 'value' => (string) $uniqueEmployees, 'icon' => 'users'],
                ['label' => 'Promedio Retraso', 'value' => round($avgMinutes ?? 0).' min', 'icon' => 'clock'],
            ],
        );
    }

    private function leaves(ReportFilterDTO $filters): ReportPreviewResult
    {
        $rows = $this->repository->getLeavesData($filters);

        $totalMinutes = $rows->sum(fn ($r) => $r->minutes ?? 0);
        $uniqueEmployees = $rows->pluck('employeeId')->unique()->count();
        $excused = $rows->where('isExcused', true)->count();

        return new ReportPreviewResult(
            title: 'Permisos',
            description: 'Permisos registrados (trimestral, compensatorio, licencias, duelos, etc.).',
            rows: $rows,
            columns: [
                ['label' => 'Empleado', 'key' => 'employeeName'],
                ['label' => 'Número', 'key' => 'employeeNumber'],
                ['label' => 'Equipo', 'key' => 'teamName'],
                ['label' => 'Fecha', 'key' => 'date'],
                ['label' => 'Tipo', 'key' => 'leaveType'],
                ['label' => 'Justificado', 'key' => 'isExcused'],
                ['label' => 'Estado', 'key' => 'status'],
            ],
            summary: [
                ['label' => 'Total Permisos', 'value' => (string) $rows->count(), 'icon' => 'document-text'],
                ['label' => 'Empleados', 'value' => (string) $uniqueEmployees, 'icon' => 'users'],
                ['label' => 'Justificados', 'value' => $rows->count() > 0 ? round($excused / $rows->count() * 100).'%' : '0%', 'icon' => 'check-badge'],
            ],
        );
    }

    private function vacations(ReportFilterDTO $filters): ReportPreviewResult
    {
        $rows = $this->repository->getVacationsData($filters);

        $totalDays = $rows->sum(fn ($r) => $r->daysTaken ?? 0);
        $uniqueEmployees = $rows->pluck('employeeId')->unique()->count();

        return new ReportPreviewResult(
            title: 'Vacaciones',
            description: 'Períodos de vacaciones registrados como excepciones de horario.',
            rows: $rows,
            columns: [
                ['label' => 'Empleado', 'key' => 'employeeName'],
                ['label' => 'Número', 'key' => 'employeeNumber'],
                ['label' => 'Equipo', 'key' => 'teamName'],
                ['label' => 'Inicio', 'key' => 'startDate'],
                ['label' => 'Fin', 'key' => 'endDate'],
                ['label' => 'Días', 'key' => 'daysTaken'],
            ],
            summary: [
                ['label' => 'Registros', 'value' => (string) $rows->count(), 'icon' => 'calendar-days'],
                ['label' => 'Empleados', 'value' => (string) $uniqueEmployees, 'icon' => 'users'],
                ['label' => 'Total Días', 'value' => (string) $totalDays, 'icon' => 'sun'],
            ],
        );
    }

    private function attendanceSummary(ReportFilterDTO $filters): ReportPreviewResult
    {
        $rows = $this->repository->getAttendanceSummaryData($filters);

        $avgAttendance = $rows->avg(fn ($r) => $r->attendanceRate ?? 0);
        $avgTardiness = $rows->avg(fn ($r) => $r->tardinessRate ?? 0);

        return new ReportPreviewResult(
            title: 'Resumen Global de Asistencia',
            description: 'Métrica consolidada de asistencia: ausencias, tardanzas, permisos y vacaciones por agente.',
            rows: $rows,
            columns: [
                ['label' => 'Entidad', 'key' => 'entityName'],
                ['label' => 'Tipo', 'key' => 'entityType'],
                ['label' => 'Días Programados', 'key' => 'totalScheduledDays'],
                ['label' => 'Ausencias', 'key' => 'totalAbsences'],
                ['label' => 'Tardanzas', 'key' => 'totalTardiness'],
                ['label' => 'Permisos', 'key' => 'totalLeaves'],
                ['label' => 'Vacaciones', 'key' => 'totalVacationDays'],
                ['label' => '% Asistencia', 'key' => 'attendanceRate'],
            ],
            summary: [
                ['label' => '% Asistencia Promedio', 'value' => round($avgAttendance ?? 0, 1).'%', 'icon' => 'check-circle'],
                ['label' => '% Tardanza Promedio', 'value' => round($avgTardiness ?? 0, 1).'%', 'icon' => 'exclamation-triangle'],
                ['label' => 'Total Registros', 'value' => (string) $rows->count(), 'icon' => 'clipboard-document-list'],
            ],
        );
    }

    private function intradayActivities(ReportFilterDTO $filters): ReportPreviewResult
    {
        $rows = $this->repository->getIntradayActivitiesData($filters);

        $productiveMinutes = $rows->where('isProductive', true)->sum(fn ($r) => $r->totalMinutes ?? 0);
        $nonProductiveMinutes = $rows->where('isProductive', false)->sum(fn ($r) => $r->totalMinutes ?? 0);
        $uniqueEmployees = $rows->pluck('employeeId')->unique()->count();

        return new ReportPreviewResult(
            title: 'Actividades Intradía',
            description: 'Actividades no telefónicas ejecutadas por el agente durante la jornada.',
            rows: $rows,
            columns: [
                ['label' => 'Empleado', 'key' => 'employeeName'],
                ['label' => 'Fecha', 'key' => 'date'],
                ['label' => 'Actividad', 'key' => 'activityName'],
                ['label' => 'Inicio', 'key' => 'startTime'],
                ['label' => 'Fin', 'key' => 'endTime'],
                ['label' => 'Productivo', 'key' => 'isProductive'],
            ],
            summary: [
                ['label' => 'Registros', 'value' => (string) $rows->count(), 'icon' => 'clipboard-document-list'],
                ['label' => 'Empleados', 'value' => (string) $uniqueEmployees, 'icon' => 'users'],
                ['label' => 'T. Productivo', 'value' => $this->formatDuration($productiveMinutes), 'icon' => 'check-circle'],
                ['label' => 'T. No Productivo', 'value' => $this->formatDuration($nonProductiveMinutes), 'icon' => 'x-circle'],
            ],
        );
    }

    private function periodActivities(ReportFilterDTO $filters): ReportPreviewResult
    {
        $rows = $this->repository->getPeriodActivitiesData($filters);

        $productiveMinutes = $rows->where('isProductive', true)->sum(fn ($r) => $r->totalMinutes ?? 0);
        $nonProductiveMinutes = $rows->where('isProductive', false)->sum(fn ($r) => $r->totalMinutes ?? 0);
        $avgCompliance = $rows->avg(fn ($r) => $r->compliancePct ?? 0);

        return new ReportPreviewResult(
            title: 'Actividades por Período',
            description: 'Horas acumuladas por tipo de actividad en el rango de fechas seleccionado.',
            rows: $rows,
            columns: [
                ['label' => 'Entidad', 'key' => 'entityName'],
                ['label' => 'Tipo', 'key' => 'entityType'],
                ['label' => 'Actividad', 'key' => 'activityName'],
                ['label' => 'Total Minutos', 'key' => 'totalMinutes'],
                ['label' => 'Productivo', 'key' => 'isProductive'],
                ['label' => '% Cumplimiento', 'key' => 'compliancePct'],
            ],
            summary: [
                ['label' => 'Registros', 'value' => (string) $rows->count(), 'icon' => 'clipboard-document-list'],
                ['label' => 'T. Productivo', 'value' => $this->formatDuration($productiveMinutes), 'icon' => 'check-circle'],
                ['label' => 'T. No Productivo', 'value' => $this->formatDuration($nonProductiveMinutes), 'icon' => 'x-circle'],
                ['label' => '% Cumplimiento Prom.', 'value' => round($avgCompliance ?? 0, 1).'%', 'icon' => 'arrow-trending-up'],
            ],
        );
    }

    private function volumeDetail(ReportFilterDTO $filters): ReportPreviewResult
    {
        $rows = $this->repository->getVolumeDetailData($filters);

        $totalReceived = $rows->sum(fn ($r) => $r->received ?? 0);
        $totalHandled = $rows->sum(fn ($r) => $r->handled ?? 0);
        $totalAbandoned = $rows->sum(fn ($r) => $r->abandoned ?? 0);
        $avgSla = $rows->avg(fn ($r) => $r->slaPercentage ?? 0);

        return new ReportPreviewResult(
            title: 'Volumen por Cola',
            description: 'Métricas de llamadas por cola: ofrecidas, atendidas, abandonadas, AHT, ASA y SLA.',
            rows: $rows,
            columns: [
                ['label' => 'Cola', 'key' => 'queueName'],
                ['label' => 'Ofrecidas', 'key' => 'received'],
                ['label' => 'Atendidas', 'key' => 'handled'],
                ['label' => 'Abandonadas', 'key' => 'abandoned'],
                ['label' => '% Abandono', 'key' => 'abandonmentRate'],
                ['label' => 'AHT', 'key' => 'aht'],
                ['label' => 'ASA', 'key' => 'asa'],
                ['label' => 'SLA', 'key' => 'slaPercentage'],
            ],
            summary: [
                ['label' => 'Total Ofrecidas', 'value' => (string) $totalReceived, 'icon' => 'phone'],
                ['label' => 'Atendidas', 'value' => (string) $totalHandled, 'icon' => 'phone-arrow-up-right'],
                ['label' => 'Abandonadas', 'value' => (string) $totalAbandoned, 'icon' => 'phone-x-mark'],
                ['label' => 'SLA Promedio', 'value' => round($avgSla ?? 0, 1).'%', 'icon' => 'chart-bar'],
            ],
        );
    }

    private function volumeInterval(ReportFilterDTO $filters): ReportPreviewResult
    {
        $rows = $this->repository->getVolumeByIntervalData($filters);

        $totalOffered = $rows->sum(fn ($r) => $r->offered ?? 0);
        $totalHandled = $rows->sum(fn ($r) => $r->handled ?? 0);
        $totalAbandoned = $rows->sum(fn ($r) => $r->abandoned ?? 0);
        $peakInterval = $rows->sortByDesc(fn ($r) => $r->offered ?? 0)->first();

        $chartData = [
            'categories' => $rows->pluck('interval')->values()->toArray(),
            'series' => [
                ['name' => 'Ofrecidas', 'data' => $rows->pluck('offered')->values()->toArray()],
                ['name' => 'Atendidas', 'data' => $rows->pluck('handled')->values()->toArray()],
                ['name' => 'Abandonadas', 'data' => $rows->pluck('abandoned')->values()->toArray()],
            ],
        ];

        return new ReportPreviewResult(
            title: 'Volumen por Intervalo',
            description: 'Volumen de llamadas segmentado en intervalos de 30 minutos.',
            rows: $rows,
            columns: [
                ['label' => 'Intervalo', 'key' => 'interval'],
                ['label' => 'Ofrecidas', 'key' => 'offered'],
                ['label' => 'Atendidas', 'key' => 'handled'],
                ['label' => 'Abandonadas', 'key' => 'abandoned'],
                ['label' => '% Abandono', 'key' => 'abandonmentRate'],
                ['label' => 'AHT', 'key' => 'aht'],
                ['label' => 'ASA', 'key' => 'asa'],
            ],
            summary: [
                ['label' => 'Total Ofrecidas', 'value' => (string) $totalOffered, 'icon' => 'phone'],
                ['label' => 'Atendidas', 'value' => (string) $totalHandled, 'icon' => 'phone-arrow-up-right'],
                ['label' => 'Abandonadas', 'value' => (string) $totalAbandoned, 'icon' => 'phone-x-mark'],
                ['label' => 'Pico', 'value' => $peakInterval ? ($peakInterval->interval ?? '—') : '—', 'icon' => 'arrow-trending-up'],
            ],
            chartConfig: [
                'type' => 'bar',
                'data' => $chartData,
            ],
        );
    }

    private function volumeSummary(ReportFilterDTO $filters): ReportPreviewResult
    {
        $rows = $this->repository->getVolumeSummaryData($filters);

        $totalReceived = $rows->sum(fn ($r) => $r->received ?? 0);
        $totalHandled = $rows->sum(fn ($r) => $r->handled ?? 0);
        $totalAbandoned = $rows->sum(fn ($r) => $r->abandoned ?? 0);
        $avgSla = $rows->avg(fn ($r) => $r->slaPercentage ?? 0);

        return new ReportPreviewResult(
            title: 'Volumen Consolidado',
            description: 'Resumen de tráfico telefónico agregado por cola en el período.',
            rows: $rows,
            columns: [
                ['label' => 'Cola', 'key' => 'queueName'],
                ['label' => 'Fecha', 'key' => 'date'],
                ['label' => 'Ofrecidas', 'key' => 'received'],
                ['label' => 'Atendidas', 'key' => 'handled'],
                ['label' => 'Abandonadas', 'key' => 'abandoned'],
                ['label' => '% Abandono', 'key' => 'abandonmentRate'],
                ['label' => 'SLA', 'key' => 'slaPercentage'],
            ],
            summary: [
                ['label' => 'Total Ofrecidas', 'value' => (string) $totalReceived, 'icon' => 'phone'],
                ['label' => 'Atendidas', 'value' => (string) $totalHandled, 'icon' => 'phone-arrow-up-right'],
                ['label' => 'Abandonadas', 'value' => (string) $totalAbandoned, 'icon' => 'phone-x-mark'],
                ['label' => 'SLA Promedio', 'value' => round($avgSla ?? 0, 1).'%', 'icon' => 'chart-bar'],
            ],
        );
    }

    private function agentPerformance(ReportFilterDTO $filters): ReportPreviewResult
    {
        $rows = $this->repository->getAgentPerformanceData($filters);

        $avgCalls = $rows->avg(fn ($r) => $r->callsHandled ?? 0);
        $avgAht = $rows->avg(fn ($r) => $r->aht ?? 0);
        $avgOccupancy = $rows->avg(fn ($r) => $r->occupancy ?? 0);

        return new ReportPreviewResult(
            title: 'Desempeño por Agente',
            description: 'KPIs individuales: llamadas atendidas, AHT, ocupación, talk time, disponible y ACW.',
            rows: $rows,
            columns: [
                ['label' => 'Empleado', 'key' => 'employeeName'],
                ['label' => 'Número', 'key' => 'employeeNumber'],
                ['label' => 'Equipo', 'key' => 'teamName'],
                ['label' => 'Llamadas', 'key' => 'callsHandled'],
                ['label' => 'AHT', 'key' => 'aht'],
                ['label' => 'Ocupación', 'key' => 'occupancy'],
                ['label' => 'T. Hablado', 'key' => 'talkTime'],
                ['label' => 'Disponible', 'key' => 'readyTime'],
                ['label' => 'ACW', 'key' => 'acwTime'],
            ],
            summary: [
                ['label' => 'Agentes', 'value' => (string) $rows->count(), 'icon' => 'users'],
                ['label' => 'Prom. Llamadas', 'value' => round($avgCalls ?? 0, 1).'', 'icon' => 'phone'],
                ['label' => 'AHT Promedio', 'value' => round($avgAht ?? 0, 1).'s', 'icon' => 'clock'],
                ['label' => 'Ocupación Prom.', 'value' => round($avgOccupancy ?? 0, 1).'%', 'icon' => 'chart-bar'],
            ],
        );
    }

    private function teamPerformance(ReportFilterDTO $filters): ReportPreviewResult
    {
        $rows = $this->repository->getTeamPerformanceData($filters);

        $avgAht = $rows->avg(fn ($r) => $r->avgAht ?? 0);
        $avgOccupancy = $rows->avg(fn ($r) => $r->avgOccupancy ?? 0);
        $avgAdherence = $rows->avg(fn ($r) => $r->avgAdherence ?? 0);
        $totalCalls = $rows->sum(fn ($r) => $r->totalCalls ?? 0);

        return new ReportPreviewResult(
            title: 'Desempeño por Equipo',
            description: 'Métricas agregadas por equipo: llamadas, AHT promedio, ocupación y adherencia.',
            rows: $rows,
            columns: [
                ['label' => 'Equipo', 'key' => 'teamName'],
                ['label' => 'Agentes', 'key' => 'agentCount'],
                ['label' => 'Total Llamadas', 'key' => 'totalCalls'],
                ['label' => 'AHT Promedio', 'key' => 'avgAht'],
                ['label' => 'Ocupación', 'key' => 'avgOccupancy'],
                ['label' => 'Adherencia', 'key' => 'avgAdherence'],
            ],
            summary: [
                ['label' => 'Equipos', 'value' => (string) $rows->count(), 'icon' => 'building-office'],
                ['label' => 'Total Llamadas', 'value' => (string) $totalCalls, 'icon' => 'phone'],
                ['label' => 'AHT Prom.', 'value' => round($avgAht ?? 0, 1).'s', 'icon' => 'clock'],
                ['label' => 'Ocupación Prom.', 'value' => round($avgOccupancy ?? 0, 1).'%', 'icon' => 'chart-bar'],
                ['label' => 'Adherencia Prom.', 'value' => round($avgAdherence ?? 0, 1).'%', 'icon' => 'check-circle'],
            ],
        );
    }

    private function ranking(ReportFilterDTO $filters): ReportPreviewResult
    {
        $rows = $this->repository->getRankingData($filters);

        $avgScore = $rows->avg(fn ($r) => $r->score ?? 0);
        $topAgent = $rows->first();
        $totalAgents = $rows->count();

        return new ReportPreviewResult(
            title: 'Ranking de Agentes',
            description: 'Agentes ordenados por score compuesto (50% llamadas + 30% AHT inverso + 20% ocupación).',
            rows: $rows,
            columns: [
                ['label' => '#', 'key' => 'position'],
                ['label' => 'Empleado', 'key' => 'employeeName'],
                ['label' => 'Número', 'key' => 'employeeNumber'],
                ['label' => 'Equipo', 'key' => 'teamName'],
                ['label' => 'Llamadas', 'key' => 'callsHandled'],
                ['label' => 'AHT', 'key' => 'aht'],
                ['label' => 'Ocupación', 'key' => 'occupancy'],
                ['label' => 'Adherencia', 'key' => 'adherence'],
                ['label' => 'Score', 'key' => 'score'],
            ],
            summary: [
                ['label' => 'Agentes', 'value' => (string) $totalAgents, 'icon' => 'users'],
                ['label' => 'Score Prom.', 'value' => round($avgScore ?? 0, 1), 'icon' => 'chart-bar'],
                ['label' => 'Top', 'value' => $topAgent?->employeeName ?? '—', 'icon' => 'trophy'],
            ],
        );
    }

    private function formatDuration(int $totalMinutes): string
    {
        if ($totalMinutes <= 0) {
            return '0 min';
        }

        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}min";
        }

        return "{$minutes} min";
    }
}
