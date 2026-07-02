<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Domain\Entities\AgentCallPerformance;
use App\Src\Connect\Domain\Repositories\AgentCallPerformanceRepositoryInterface;
use App\Src\Connect\Domain\Services\CuicDataNormalizationService;
use Illuminate\Support\Facades\Log;

final readonly class ImportUccxPerformanceHandler
{
    public function __construct(
        private CuicDataNormalizationService $normalizer,
        private AgentCallPerformanceRepositoryInterface $repository,
    ) {}

    public function handle(array $rawPerformance): int
    {
        $imported = 0;

        foreach ($rawPerformance as $raw) {
            $performance = $this->normalizer->normalizePerformance($raw);
            $this->repository->upsert($performance);
            $imported++;
        }

        Log::info('UCCX performance records imported.', [
            'count' => $imported,
        ]);

        return $imported;
    }
}
