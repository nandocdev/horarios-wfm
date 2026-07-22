<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\DTOs\VolumeRowDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use App\Modules\ReportingModule\Support\FormatDuration;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportVolumeDetailAction
{
    use FormatDuration;

    public function __construct(
        private readonly EloquentReportDataRepository $repository,
        private readonly GeneratePdfReportAction $pdfAction,
        private readonly GenerateXlsReportAction $xlsAction,
    ) {}

    public function execute(ReportFilterDTO $filters): StreamedResponse
    {
        $rows = $this->repository->getVolumeDetailData($filters);

        $data = [
            'title' => 'Volumen de Llamadas — Detalle',
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'rows' => $rows,
        ];

        return $filters->format === ReportFormatEnum::Pdf
            ? $this->pdfAction->execute($data, 'reporting::reports.volume-summary', 'Volumen_Detalle', 'landscape')
            : $this->xlsAction->execute(
                $rows->map(fn (VolumeRowDTO $r) => [
                    $r->queueName,
                    $r->date,
                    (string) $r->received,
                    (string) $r->handled,
                    (string) $r->abandoned,
                    number_format($r->abandonmentRate, 1).'%',
                    $r->aht !== null ? $this->formatSeconds((int) $r->aht) : '—',
                    $r->asa !== null ? $this->formatSeconds((int) $r->asa) : '—',
                    $r->slaPercentage !== null ? number_format($r->slaPercentage, 1).'%' : '—',
                ])->toArray(),
                ['Cola', 'Fecha', 'Recibidos', 'Atendidos', 'Abandonados', '% Abandono', 'AHT', 'ASA', 'SLA'],
                'volumen-detalle',
            );
    }
}
