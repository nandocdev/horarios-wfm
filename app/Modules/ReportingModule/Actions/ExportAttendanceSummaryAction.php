<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\AttendanceSummaryRowDTO;
use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportAttendanceSummaryAction
{
    public function __construct(
        private readonly EloquentReportDataRepository $repository,
        private readonly GeneratePdfReportAction $pdfAction,
        private readonly GenerateXlsReportAction $xlsAction,
    ) {}

    public function execute(ReportFilterDTO $filters): StreamedResponse
    {
        $rows = $this->repository->getAttendanceSummaryData($filters);

        $data = [
            'title' => 'Resumen Global de Asistencia',
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'rows' => $rows,
        ];

        return $filters->format === ReportFormatEnum::Pdf
            ? $this->pdfAction->execute($data, 'reporting::reports.attendance.summary', 'Asistencia_Global', 'landscape')
            : $this->xlsAction->execute(
                $rows->map(fn (AttendanceSummaryRowDTO $r) => [
                    $r->entityName,
                    (string) $r->totalScheduledDays,
                    (string) $r->totalAbsences,
                    (string) $r->totalTardiness,
                    (string) $r->totalLeaves,
                    (string) $r->totalVacationDays,
                    number_format($r->attendanceRate, 1).'%',
                    number_format($r->tardinessRate, 1).'%',
                ])->toArray(),
                ['Agente', 'Días Prog.', 'Ausencias', 'Tardanzas', 'Permisos', 'Vacaciones', '% Asistencia', '% Tardanza'],
                'asistencia-global',
            );
    }
}
