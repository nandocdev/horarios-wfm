<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Livewire;

use App\Modules\ReportingModule\Actions\ExportAhtDetailAction;
use App\Modules\ReportingModule\Actions\ExportAhtSummaryAction;
use App\Modules\ReportingModule\Actions\ExportExceptionSummaryAction;
use App\Modules\ReportingModule\Actions\ExportRawAbsenteeismAction;
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
    public ReportGeneratorForm $form;

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

        $action = match ($this->form->reportType) {
            'absenteeism-raw' => app(ExportRawAbsenteeismAction::class),
            'absenteeism-exceptions' => app(ExportExceptionSummaryAction::class),
            'aht-detail' => app(ExportAhtDetailAction::class),
            'aht-summary' => app(ExportAhtSummaryAction::class),
            'volume-detail' => app(ExportVolumeDetailAction::class),
            'volume-summary' => app(ExportVolumeSummaryAction::class),
        };

        return $action->execute($filters);
    }

    public function render(): View
    {
        return view('reporting::livewire.report-generator');
    }
}
