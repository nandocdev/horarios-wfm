<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\DTOs\VolumeIntervalRowDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportVolumeByIntervalAction
{
    public function __construct(
        private readonly EloquentReportDataRepository $repository,
        private readonly GeneratePdfReportAction $pdfAction,
        private readonly GenerateXlsReportAction $xlsAction,
    ) {}

    public function execute(ReportFilterDTO $filters): StreamedResponse
    {
        $rows = $this->repository->getVolumeByIntervalData($filters);

        $data = [
            'title' => 'Volumen por Intervalo',
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'rows' => $rows,
        ];

        return $filters->format === ReportFormatEnum::Pdf
            ? $this->pdfAction->execute($data, 'reporting::reports.volume.interval', 'Volumen_Intervalo', 'landscape')
            : $this->xlsAction->execute(
                $rows->map(fn (VolumeIntervalRowDTO $r) => [
                    $r->queueName, $r->interval, (string) $r->offered, (string) $r->handled,
                    (string) $r->abandoned, number_format($r->abandonmentRate, 1).'%',
                    $r->aht !== null ? gmdate('i:s', (int) $r->aht) : '—',
                    $r->asa !== null ? gmdate('i:s', (int) $r->asa) : '—',
                ])->toArray(),
                ['Cola', 'Intervalo', 'Ofrecidas', 'Atendidas', 'Abandonadas', '% Abandono', 'AHT', 'ASA'],
                'volumen-intervalo',
            );
    }
}
