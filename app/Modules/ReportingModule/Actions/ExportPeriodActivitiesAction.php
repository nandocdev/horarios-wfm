<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\PeriodActivityRowDTO;
use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportPeriodActivitiesAction
{
    public function __construct(
        private readonly EloquentReportDataRepository $repository,
        private readonly GeneratePdfReportAction $pdfAction,
        private readonly GenerateXlsReportAction $xlsAction,
    ) {}

    public function execute(ReportFilterDTO $filters): StreamedResponse
    {
        $rows = $this->repository->getPeriodActivitiesData($filters);

        $data = [
            'title' => 'Actividades por Período',
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'rows' => $rows,
        ];

        return $filters->format === ReportFormatEnum::Pdf
            ? $this->pdfAction->execute($data, 'reporting::reports.activities.period', 'Actividades_Periodo', 'landscape')
            : $this->xlsAction->execute(
                $rows->map(fn (PeriodActivityRowDTO $r) => [
                    $r->entityName, $r->activityName, (string) $r->totalMinutes,
                    $r->isProductive ? 'Sí' : 'No',
                    $r->compliancePct !== null ? number_format($r->compliancePct, 1).'%' : '—',
                ])->toArray(),
                ['Entidad', 'Actividad', 'Minutos', 'Productiva', '% Cumplimiento'],
                'actividades-periodo',
            );
    }
}
