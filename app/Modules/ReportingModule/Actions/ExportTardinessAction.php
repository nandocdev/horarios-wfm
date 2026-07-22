<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\DTOs\TardinessRowDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportTardinessAction
{
    public function __construct(
        private readonly EloquentReportDataRepository $repository,
        private readonly GeneratePdfReportAction $pdfAction,
        private readonly GenerateXlsReportAction $xlsAction,
    ) {}

    public function execute(ReportFilterDTO $filters): StreamedResponse
    {
        $rows = $this->repository->getTardinessData($filters);

        $data = [
            'title' => 'Reporte de Tardanzas',
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'rows' => $rows,
        ];

        return $filters->format === ReportFormatEnum::Pdf
            ? $this->pdfAction->execute($data, 'reporting::reports.attendance.tardiness', 'Tardanzas', 'landscape')
            : $this->xlsAction->execute(
                $rows->map(fn (TardinessRowDTO $r) => [
                    $r->employeeName, $r->employeeNumber, $r->teamName ?? '—',
                    $r->date, $r->scheduledStart ?? '—', $r->actualLogin ?? '—',
                    $r->minutesLate !== null ? (string) $r->minutesLate : '—',
                    $r->incidentType ?? '—', $r->justification ?? '—',
                ])->toArray(),
                ['Empleado', 'Número', 'Equipo', 'Fecha', 'Hora Prog.', 'Hora Real', 'Minutos', 'Tipo', 'Justificación'],
                'tardanzas',
            );
    }
}
