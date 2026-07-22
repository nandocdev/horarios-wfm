<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\AhtRowDTO;
use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportAhtDetailAction
{
    public function __construct(
        private readonly EloquentReportDataRepository $repository,
        private readonly GeneratePdfReportAction $pdfAction,
        private readonly GenerateXlsReportAction $xlsAction,
    ) {}

    public function execute(ReportFilterDTO $filters): StreamedResponse
    {
        $rows = $this->repository->getAhtDetailData($filters);

        $data = [
            'title' => 'AHT Detallado por Agente',
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'rows' => $rows,
        ];

        return $filters->format === ReportFormatEnum::Pdf
            ? $this->pdfAction->execute($data, 'reporting::reports.aht-detail', 'AHT_Detallado', 'landscape')
            : $this->xlsAction->execute(
                $rows->map(fn (AhtRowDTO $r) => [
                    $r->agentName,
                    $r->queueName,
                    $r->date,
                    (string) $r->callsHandled,
                    $this->formatSeconds((int) $r->avgTalkTime),
                    $this->formatSeconds((int) $r->avgWorkTime),
                    $this->formatSeconds((int) $r->avgHoldTime),
                    $this->formatSeconds((int) $r->aht),
                    $r->ahtGoal !== null ? $this->formatSeconds($r->ahtGoal) : '—',
                    $r->deviation !== null ? $this->formatSeconds((int) $r->deviation) : '—',
                ])->toArray(),
                ['Agente', 'Cola', 'Fecha', 'Llamadas', 'Talk Time', 'Work Time', 'Hold Time', 'AHT', 'Objetivo', 'Desviación'],
                'aht-detallado',
            );
    }

    private function formatSeconds(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $secs = $seconds % 60;

        return $minutes > 0
            ? sprintf('%dm %02ds', $minutes, $secs)
            : sprintf('%ds', $secs);
    }
}
