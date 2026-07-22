<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\AbsenteeismRowDTO;
use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use Barryvdh\DomPDF\PDF as DomPDFInstance;
use Illuminate\Http\Response;

final class ExportRawAbsenteeismAction
{
    public function __construct(
        private readonly EloquentReportDataRepository $repository,
        private readonly GeneratePdfReportAction $pdfAction,
        private readonly GenerateXlsReportAction $xlsAction,
    ) {}

    public function execute(ReportFilterDTO $filters): DomPDFInstance|Response
    {
        $rows = $this->repository->getRawAbsenteeismData($filters);

        $data = [
            'title' => 'Reporte de Ausentismo — Detalle',
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'rows' => $rows,
        ];

        return $filters->format === ReportFormatEnum::Pdf
            ? $this->pdfAction->execute($data, 'reporting::reports.ausentismo-raw', 'Reporte_Ausentismo_Detalle', 'landscape')
            : $this->xlsAction->execute(
                $rows->map(fn (AbsenteeismRowDTO $r) => [
                    $r->employeeName,
                    $r->employeeNumber,
                    $r->teamName ?? '—',
                    $r->date,
                    $r->originType === 'schedule_exception' ? 'Planificado' : 'Incidencia',
                    $r->causeName,
                    $r->isJustified ? 'Sí' : 'No',
                    $r->isFullDay ? 'Sí' : 'No',
                    $r->minutesAbsent !== null ? (string) $r->minutesAbsent : '—',
                    $r->remarks ?? '—',
                ])->toArray(),
                ['Empleado', 'Número', 'Equipo', 'Fecha', 'Origen', 'Causa', 'Justificado', 'Día completo', 'Minutos', 'Observaciones'],
                'ausentismo-detalle',
            );
    }
}
