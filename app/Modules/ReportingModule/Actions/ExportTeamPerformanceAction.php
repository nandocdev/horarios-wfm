<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\DTOs\TeamPerformanceRowDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportTeamPerformanceAction
{
    public function __construct(
        private readonly EloquentReportDataRepository $repository,
        private readonly GeneratePdfReportAction $pdfAction,
        private readonly GenerateXlsReportAction $xlsAction,
    ) {}

    public function execute(ReportFilterDTO $filters): StreamedResponse
    {
        $rows = $this->repository->getTeamPerformanceData($filters);

        $data = [
            'title' => 'Desempeño por Equipo',
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'rows' => $rows,
        ];

        return $filters->format === ReportFormatEnum::Pdf
            ? $this->pdfAction->execute($data, 'reporting::reports.performance.team', 'Desempeno_Equipo', 'landscape')
            : $this->xlsAction->execute(
                $rows->map(fn (TeamPerformanceRowDTO $r) => [
                    $r->teamName, (string) $r->agentCount, (string) $r->totalCalls,
                    gmdate('i:s', (int) $r->avgAht),
                    number_format($r->avgOccupancy, 1).'%',
                    number_format($r->avgAdherence, 1).'%',
                ])->toArray(),
                ['Equipo', 'Agentes', 'Llamadas', 'AHT Prom', 'Ocupación Prom', 'Adherencia Prom'],
                'desempeno-equipo',
            );
    }
}
