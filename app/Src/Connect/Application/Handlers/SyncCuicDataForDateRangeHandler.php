<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\CuicBackfillDTO;
use App\Src\Connect\Application\DTOs\SyncCuicFilterDTO;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

final readonly class SyncCuicDataForDateRangeHandler
{
    public function __construct(
        private SyncCuicDataHandler $syncHandler,
    ) {}

    public function handle(CuicBackfillDTO $dto): array
    {
        $start = CarbonImmutable::parse($dto->dateFrom);
        $end = CarbonImmutable::parse($dto->dateTo);

        $results = [
            'total_days' => 0,
            'total_records' => 0,
            'errors' => 0,
        ];

        $current = $start;

        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');

            foreach ($dto->reportTypes as $reportType) {
                try {
                    $filter = new SyncCuicFilterDTO(
                        reportType: $reportType,
                        dateFrom: $dateStr,
                        dateTo: $dateStr,
                    );

                    $dayResults = $this->syncHandler->handle($filter);

                    $results['total_records'] += array_sum($dayResults);
                } catch (\Throwable $e) {
                    $results['errors']++;

                    Log::error('Backfill sync failed for date/report.', [
                        'date' => $dateStr,
                        'report_type' => $reportType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $results['total_days']++;
            $current = $current->addDay();
        }

        Log::info('CUIC backfill completed.', $results);

        return $results;
    }
}
