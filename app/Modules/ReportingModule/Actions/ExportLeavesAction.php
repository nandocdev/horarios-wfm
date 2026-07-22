<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\LeaveRowDTO;
use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportLeavesAction
{
    public function __construct(
        private readonly EloquentReportDataRepository $repository,
        private readonly GeneratePdfReportAction $pdfAction,
        private readonly GenerateXlsReportAction $xlsAction,
    ) {}

    public function execute(ReportFilterDTO $filters): StreamedResponse
    {
        $rows = $this->repository->getLeavesData($filters);

        $data = [
            'title' => 'Reporte de Permisos',
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'rows' => $rows,
        ];

        return $filters->format === ReportFormatEnum::Pdf
            ? $this->pdfAction->execute($data, 'reporting::reports.attendance.leaves', 'Permisos', 'landscape')
            : $this->xlsAction->execute(
                $rows->map(fn (LeaveRowDTO $r) => [
                    $r->employeeName, $r->employeeNumber, $r->teamName ?? '—',
                    $r->date, $r->leaveType, $r->isExcused ? 'Sí' : 'No',
                    $r->status, $r->minutes !== null ? (string) $r->minutes : '—',
                    $r->remarks ?? '—',
                ])->toArray(),
                ['Empleado', 'Número', 'Equipo', 'Fecha', 'Tipo', 'Justificado', 'Estado', 'Minutos', 'Observaciones'],
                'permisos',
            );
    }
}
