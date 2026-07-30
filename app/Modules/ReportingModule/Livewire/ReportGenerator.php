<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Livewire;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
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
use App\Modules\ReportingModule\Actions\FetchReportDataAction;
use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Livewire\Forms\ReportGeneratorForm;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportGenerator extends Component
{
    public string $category = 'attendance';

    public string $subReport = 'absenteeism';

    public ReportGeneratorForm $form;

    public ?array $preview = null;

    public bool $loading = false;

    /** @var list<array{id: int, name: string}> */
    public array $employeeOptions = [];

    public function mount(?string $category = null, ?string $subReport = null): void
    {
        if ($category !== null && $subReport !== null) {
            $this->category = $category;
            $this->subReport = $subReport;
        }
        $this->loadEmployeeOptions();
    }

    public function updatedFormTeamId(string|int|null $value): void
    {
        $this->form->employeeId = null;
        $this->loadEmployeeOptions();
    }

    private function loadEmployeeOptions(): void
    {
        if (! $this->form->teamId) {
            $this->employeeOptions = [];

            return;
        }

        $this->employeeOptions = Employee::where('team_id', $this->form->teamId)
            ->active()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (Employee $e): array => [
                'id' => $e->id,
                'name' => "{$e->first_name} {$e->last_name}",
            ])
            ->values()
            ->toArray();
    }

    public function selectCategory(string $category): void
    {
        $this->category = $category;
        $this->subReport = $this->defaultSubReport($category);
        $this->preview = null;
    }

    public function selectSubReport(string $subReport): void
    {
        $this->subReport = $subReport;
        $this->preview = null;
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

    #[Computed]
    public function categories(): array
    {
        return [
            'attendance' => ['label' => 'Asistencia', 'icon' => 'clipboard-document-check'],
            'activities' => ['label' => 'Actividades', 'icon' => 'arrow-right-circle'],
            'volume' => ['label' => 'Volumen', 'icon' => 'phone'],
            'performance' => ['label' => 'Rendimiento', 'icon' => 'chart-bar'],
        ];
    }

    #[Computed]
    public function subReports(): array
    {
        return match ($this->category) {
            'attendance' => [
                'absenteeism' => 'Ausentismo',
                'tardiness' => 'Tardanzas',
                'leaves' => 'Permisos',
                'vacations' => 'Vacaciones',
                'summary' => 'Resumen',
            ],
            'activities' => [
                'intraday' => 'Actividades Intradía',
                'period' => 'Actividades por Período',
            ],
            'volume' => [
                'queue' => 'Volumen por Cola',
                'interval' => 'Volumen por Intervalo',
                'summary' => 'Consolidado',
            ],
            'performance' => [
                'agent' => 'Desempeño por Agente',
                'team' => 'Desempeño por Equipo',
                'ranking' => 'Ranking',
            ],
            default => [],
        };
    }

    private function defaultSubReport(string $category): string
    {
        return match ($category) {
            'attendance' => 'absenteeism',
            'activities' => 'intraday',
            'volume' => 'queue',
            'performance' => 'agent',
            default => 'absenteeism',
        };
    }

    #[Computed]
    public function teams(): array
    {
        return Team::active()->orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function generate(): void
    {
        Gate::authorize('reports.export', User::class);

        $this->form->validate();
        $this->loading = true;

        try {
            $filters = new ReportFilterDTO(
                dateFrom: $this->form->dateFrom,
                dateTo: $this->form->dateTo,
                format: ReportFormatEnum::Pdf,
                teamId: $this->form->teamId,
                employeeId: $this->form->employeeId,
                queueId: $this->form->queueId,
                interval: $this->form->interval,
            );

            $result = app(FetchReportDataAction::class)->execute(
                $this->category,
                $this->subReport,
                $filters,
            );

            $this->preview = [
                'title' => $result->title,
                'description' => $result->description,
                'rows' => $result->rows->map(fn ($r) => json_decode(json_encode($r), true))->toArray(),
                'columns' => $result->columns,
                'summary' => $result->summary,
                'chartConfig' => $result->chartConfig,
            ];
        } finally {
            $this->loading = false;
        }
    }

    public function exportPdf(): StreamedResponse
    {
        return $this->export(ReportFormatEnum::Pdf);
    }

    public function exportXls(): StreamedResponse
    {
        return $this->export(ReportFormatEnum::Xls);
    }

    private function export(ReportFormatEnum $format): StreamedResponse
    {
        Gate::authorize('reports.export', User::class);

        $filters = new ReportFilterDTO(
            dateFrom: $this->form->dateFrom,
            dateTo: $this->form->dateTo,
            format: $format,
            teamId: $this->form->teamId,
            employeeId: $this->form->employeeId,
            queueId: $this->form->queueId,
            interval: $this->form->interval,
        );

        return $this->resolveExportAction()->execute($filters);
    }

    private function resolveExportAction(): object
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
