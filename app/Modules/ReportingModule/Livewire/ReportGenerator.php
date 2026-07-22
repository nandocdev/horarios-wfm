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
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportGenerator extends Component
{
    public string $category = 'attendance';

    public string $subReport = 'absenteeism';

    public ReportGeneratorForm $form;

    public function updatedCategory(string $value): void
    {
        $this->subReport = match ($value) {
            'attendance' => 'absenteeism',
            'activities' => 'intraday',
            'volume' => 'queue',
            'performance' => 'agent',
            default => 'absenteeism',
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
