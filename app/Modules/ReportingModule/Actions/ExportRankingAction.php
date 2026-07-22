<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\RankingRowDTO;
use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportRankingAction
{
    public function __construct(
        private readonly EloquentReportDataRepository $repository,
        private readonly GeneratePdfReportAction $pdfAction,
        private readonly GenerateXlsReportAction $xlsAction,
    ) {}

    public function execute(ReportFilterDTO $filters): StreamedResponse
    {
        $rows = $this->repository->getRankingData($filters);

        $data = [
            'title' => 'Ranking de Agentes',
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'rows' => $rows,
        ];

        return $filters->format === ReportFormatEnum::Pdf
            ? $this->pdfAction->execute($data, 'reporting::reports.performance.ranking', 'Ranking_Agentes', 'landscape')
            : $this->xlsAction->execute(
                $rows->map(fn (RankingRowDTO $r) => [
                    (string) $r->position, $r->employeeName, $r->employeeNumber,
                    $r->teamName ?? '—', (string) $r->callsHandled,
                    gmdate('i:s', (int) $r->aht),
                    number_format($r->occupancy, 1).'%',
                    number_format($r->adherence, 1).'%',
                    number_format($r->score, 1),
                ])->toArray(),
                ['#', 'Agente', 'Número', 'Equipo', 'Llamadas', 'AHT', 'Ocupación', 'Adherencia', 'Score'],
                'ranking-agentes',
            );
    }
}
