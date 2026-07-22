<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Livewire;

use App\Modules\ReportingModule\Actions\ExportAgentPerformanceAction;
use App\Modules\ReportingModule\Actions\ExportAttendanceSummaryAction;
use App\Modules\ReportingModule\Actions\ExportIntradayActivitiesAction;
use App\Modules\ReportingModule\Actions\ExportLeavesAction;
use App\Modules\ReportingModule\Actions\ExportPeriodActivitiesAction;
use App\Modules\ReportingModule\Actions\ExportRankingAction;
use App\Modules\ReportingModule\Actions\ExportRawAbsenteeismAction;
use App\Modules\ReportingModule\Actions\ExportTardinessAction;
use App\Modules\ReportingModule\Actions\ExportTeamPerformanceAction;
use App\Modules\ReportingModule\Actions\ExportVacationsAction;
use App\Modules\ReportingModule\Actions\ExportVolumeByIntervalAction;
use App\Modules\ReportingModule\Actions\ExportVolumeDetailAction;
use App\Modules\ReportingModule\Actions\ExportVolumeSummaryAction;
use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Livewire\Forms\ReportGeneratorForm;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportGenerator extends Component
{
    public string $category = 'attendance';

    public string $subReport = 'absenteeism';

    public ReportGeneratorForm $form;

    public function mount(?string $category = null, ?string $subReport = null): void
    {
        if ($category !== null && $subReport !== null) {
            $this->category = $category;
            $this->subReport = $subReport;
        }
    }

    #[Computed]
    public function reportTitle(): string
    {
        return match ("{$this->category}.{$this->subReport}") {
            'attendance.absenteeism' => 'Ausentismo',
            'attendance.tardiness' => 'Tardanzas',
            'attendance.leaves' => 'Permisos',
            'attendance.vacations' => 'Vacaciones',
            'attendance.summary' => 'Resumen Global de Asistencia',
            'activities.intraday' => 'Actividades Intradía',
            'activities.period' => 'Actividades por Período',
            'volume.queue' => 'Volumen por Cola',
            'volume.interval' => 'Volumen por Intervalo',
            'volume.summary' => 'Volumen Consolidado',
            'performance.agent' => 'Desempeño por Agente',
            'performance.team' => 'Desempeño por Equipo',
            'performance.ranking' => 'Ranking de Agentes',
            default => 'Reporte',
        };
    }

    #[Computed]
    public function reportDescription(): string
    {
        return match ("{$this->category}.{$this->subReport}") {
            'attendance.absenteeism' => 'Detalle de ausencias no justificadas por agente, fecha y causa.',
            'attendance.tardiness' => 'Registro de tardanzas detectadas, con hora programada vs real y minutos de retraso.',
            'attendance.leaves' => 'Permisos registrados (trimestral, compensatorio, licencias, duelos, etc.).',
            'attendance.vacations' => 'Períodos de vacaciones registrados como excepciones de horario.',
            'attendance.summary' => 'Métrica consolidada de asistencia: ausencias, tardanzas, permisos y vacaciones por agente.',
            'activities.intraday' => 'Actividades no telefónicas ejecutadas por el agente durante la jornada.',
            'activities.period' => 'Horas acumuladas por tipo de actividad en el rango de fechas seleccionado.',
            'volume.queue' => 'Métricas de llamadas por cola: ofrecidas, atendidas, abandonadas, AHT, ASA y SLA.',
            'volume.interval' => 'Volumen de llamadas segmentado en intervalos de 30 minutos.',
            'volume.summary' => 'Resumen de tráfico telefónico agregado por cola en el período.',
            'performance.agent' => 'KPIs individuales: llamadas atendidas, AHT, ocupación, talk time, disponible y ACW.',
            'performance.team' => 'Métricas agregadas por equipo: llamadas, AHT promedio, ocupación y adherencia.',
            'performance.ranking' => 'Agentes ordenados por score compuesto (50% llamadas + 30% AHT inverso + 20% ocupación).',
            default => '',
        };
    }

    public function generate(): StreamedResponse
    {
        $this->form->validate();

        $this->authorize('reports.export');

        $filters = new ReportFilterDTO(
            dateFrom: $this->form->dateFrom,
            dateTo: $this->form->dateTo,
            format: ReportFormatEnum::from($this->form->format),
            teamId: $this->form->teamId,
            employeeId: $this->form->employeeId,
            queueId: $this->form->queueId,
            interval: $this->form->interval,
        );

        $action = $this->resolveAction();

        return $action->execute($filters);
    }

    private function resolveAction(): object
    {
        return match ("{$this->category}.{$this->subReport}") {
            'attendance.absenteeism' => app(ExportRawAbsenteeismAction::class),
            'attendance.tardiness' => app(ExportTardinessAction::class),
            'attendance.leaves' => app(ExportLeavesAction::class),
            'attendance.vacations' => app(ExportVacationsAction::class),
            'attendance.summary' => app(ExportAttendanceSummaryAction::class),
            'activities.intraday' => app(ExportIntradayActivitiesAction::class),
            'activities.period' => app(ExportPeriodActivitiesAction::class),
            'volume.queue' => app(ExportVolumeDetailAction::class),
            'volume.interval' => app(ExportVolumeByIntervalAction::class),
            'volume.summary' => app(ExportVolumeSummaryAction::class),
            'performance.agent' => app(ExportAgentPerformanceAction::class),
            'performance.team' => app(ExportTeamPerformanceAction::class),
            'performance.ranking' => app(ExportRankingAction::class),
            default => throw new \InvalidArgumentException("Reporte no válido: {$this->category}.{$this->subReport}"),
        };
    }

    public function render(): View
    {
        return view('reporting::livewire.report-generator');
    }
}
