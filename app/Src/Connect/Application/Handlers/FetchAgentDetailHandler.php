<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Domain\Ports\CuicIntegrationInterface;
use App\Src\Connect\Domain\Repositories\AgentCallPerformanceRepositoryInterface;
use App\Src\Connect\Domain\Services\CuicDataNormalizationService;
use Illuminate\Support\Facades\Log;

final readonly class FetchAgentDetailHandler
{
    public function __construct(
        private CuicIntegrationInterface $cuic,
        private CuicDataNormalizationService $normalizer,
        private AgentCallPerformanceRepositoryInterface $repository,
    ) {}

    public function handle(string $agentLoginId, string $dateFrom, string $dateTo): array
    {
        try {
            $rawData = $this->cuic->executeAgentDetailReport($agentLoginId, $dateFrom, $dateTo);

            $results = [];
            foreach ($rawData as $row) {
                $performance = $this->normalizer->normalizePerformance($row);
                $saved = $this->repository->upsert($performance);
                $results[] = $saved;
            }

            Log::info('Agent detail fetched from CUIC.', [
                'agent_login_id' => $agentLoginId,
                'records' => count($results),
            ]);

            return $results;
        } catch (\Throwable $e) {
            Log::error('Failed to fetch agent detail from CUIC.', [
                'agent_login_id' => $agentLoginId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
