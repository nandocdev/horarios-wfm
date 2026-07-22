<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\DTOs\VacationRowDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportVacationsAction
{
    public function __construct(
        private readonly EloquentReportDataRepository $repository,
        private readonly GeneratePdfReportAction $pdfAction,
        private readonly GenerateXlsReportAction $xlsAction,
    ) {}

    public function execute(ReportFilterDTO $filters): StreamedResponse
    {
        $rows = $this->repository->getVacationsData($filters);

        $data = [
            'title' => 'Reporte de Vacaciones',
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'rows' => $rows,
        ];

        return $filters->format === ReportFormatEnum::Pdf
            ? $this->pdfAction->execute($data, 'reporting::reports.attendance.vacations', 'Vacaciones', 'landscape')
            : $this->xlsAction->execute(
                $rows->map(fn (VacationRowDTO $r) => [
                    $r->employeeName, $r->employeeNumber, $r->teamName ?? '—',
                    $r->startDate, $r->endDate, (string) $r->daysTaken,
                    $r->remarks ?? '—',
                ])->toArray(),
                ['Empleado', 'Número', 'Equipo', 'Inicio', 'Fin', 'Días', 'Observaciones'],
                'vacaciones',
            );
    }
}
