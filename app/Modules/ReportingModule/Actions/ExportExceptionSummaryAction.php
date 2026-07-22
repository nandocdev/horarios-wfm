<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\ExceptionSummaryRowDTO;
use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use Illuminate\Http\Response;

final class ExportExceptionSummaryAction
{
    public function __construct(
        private readonly EloquentReportDataRepository $repository,
        private readonly GeneratePdfReportAction $pdfAction,
        private readonly GenerateXlsReportAction $xlsAction,
    ) {}

    public function execute(ReportFilterDTO $filters): Response
    {
        $rows = $this->repository->getExceptionSummaryData($filters);

        $data = [
            'title' => 'Resumen de Ausencias por Causa',
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'rows' => $rows,
        ];

        return $filters->format === ReportFormatEnum::Pdf
            ? $this->pdfAction->execute($data, 'reporting::reports.ausentismo-exceptions', 'Resumen_Ausencias')
            : $this->xlsAction->execute(
                $rows->map(fn (ExceptionSummaryRowDTO $r) => [
                    $r->causeName,
                    $r->shortCode,
                    $r->isExcused ? 'Sí' : 'No',
                    (string) $r->totalOccurrences,
                    (string) $r->totalMinutesLost,
                    (string) $r->employeesAffected,
                ])->toArray(),
                ['Causa', 'Código', 'Justificado', 'Ocurrencias', 'Minutos perdidos', 'Empleados afectados'],
                'resumen-ausencias',
            );
    }
}
