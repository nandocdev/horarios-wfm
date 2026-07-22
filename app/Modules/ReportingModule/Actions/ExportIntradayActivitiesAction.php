<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\IntradayActivityRowDTO;
use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportIntradayActivitiesAction
{
    public function __construct(
        private readonly EloquentReportDataRepository $repository,
        private readonly GeneratePdfReportAction $pdfAction,
        private readonly GenerateXlsReportAction $xlsAction,
    ) {}

    public function execute(ReportFilterDTO $filters): StreamedResponse
    {
        $rows = $this->repository->getIntradayActivitiesData($filters);

        $data = [
            'title' => 'Actividades Intradía',
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'rows' => $rows,
        ];

        return $filters->format === ReportFormatEnum::Pdf
            ? $this->pdfAction->execute($data, 'reporting::reports.activities.intraday', 'Actividades_Intradia', 'landscape')
            : $this->xlsAction->execute(
                $rows->map(fn (IntradayActivityRowDTO $r) => [
                    $r->employeeName, $r->date, $r->startTime, $r->endTime,
                    $r->activityName, $r->isProductive ? 'Sí' : 'No', $r->notes ?? '—',
                ])->toArray(),
                ['Agente', 'Fecha', 'Inicio', 'Fin', 'Actividad', 'Productiva', 'Notas'],
                'actividades-intradia',
            );
    }
}
