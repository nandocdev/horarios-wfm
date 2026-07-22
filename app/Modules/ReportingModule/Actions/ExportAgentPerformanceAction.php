<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\AgentPerformanceRowDTO;
use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportAgentPerformanceAction
{
    public function __construct(
        private readonly EloquentReportDataRepository $repository,
        private readonly GeneratePdfReportAction $pdfAction,
        private readonly GenerateXlsReportAction $xlsAction,
    ) {}

    public function execute(ReportFilterDTO $filters): StreamedResponse
    {
        $rows = $this->repository->getAgentPerformanceData($filters);

        $data = [
            'title' => 'Desempeño por Agente',
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'rows' => $rows,
        ];

        return $filters->format === ReportFormatEnum::Pdf
            ? $this->pdfAction->execute($data, 'reporting::reports.performance.agent', 'Desempeno_Agente', 'landscape')
            : $this->xlsAction->execute(
                $rows->map(fn (AgentPerformanceRowDTO $r) => [
                    $r->employeeName, $r->employeeNumber, $r->teamName ?? '—',
                    (string) $r->callsHandled, gmdate('i:s', (int) $r->aht),
                    number_format($r->occupancy, 1).'%',
                    gmdate('H:i:s', (int) $r->talkTime),
                    gmdate('H:i:s', (int) $r->readyTime),
                    gmdate('H:i:s', (int) $r->acwTime),
                ])->toArray(),
                ['Agente', 'Número', 'Equipo', 'Llamadas', 'AHT', 'Ocupación', 'Talk Time', 'Disponible', 'ACW'],
                'desempeno-agente',
            );
    }
}
