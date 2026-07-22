<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\DTOs\VolumeRowDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use Barryvdh\DomPDF\PDF as DomPDFInstance;
use Illuminate\Http\Response;

final class ExportVolumeSummaryAction
{
    public function __construct(
        private readonly EloquentReportDataRepository $repository,
        private readonly GeneratePdfReportAction $pdfAction,
        private readonly GenerateXlsReportAction $xlsAction,
    ) {}

    public function execute(ReportFilterDTO $filters): DomPDFInstance|Response
    {
        $rows = $this->repository->getVolumeSummaryData($filters);

        $data = [
            'title' => 'Volumen de Llamadas — Resumen',
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'rows' => $rows,
        ];

        return $filters->format === ReportFormatEnum::Pdf
            ? $this->pdfAction->execute($data, 'reporting::reports.volume-summary', 'Volumen_Resumen')
            : $this->xlsAction->execute(
                $rows->map(fn (VolumeRowDTO $r) => [
                    $r->queueName,
                    (string) $r->received,
                    (string) $r->handled,
                    (string) $r->abandoned,
                    number_format($r->abandonmentRate, 1).'%',
                    $r->aht !== null ? $this->formatSeconds((int) $r->aht) : '—',
                    $r->asa !== null ? $this->formatSeconds((int) $r->asa) : '—',
                ])->toArray(),
                ['Cola', 'Recibidos', 'Atendidos', 'Abandonados', '% Abandono', 'AHT', 'ASA'],
                'volumen-resumen',
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
