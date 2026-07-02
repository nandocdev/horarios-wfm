<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Domain\Ports\CuicIntegrationInterface;
use App\Src\Connect\Domain\Repositories\AgentStateTransitionRepositoryInterface;
use App\Src\Connect\Domain\Services\CuicDataNormalizationService;
use Illuminate\Support\Facades\Log;

final readonly class FetchAgentStateTransitionsHandler
{
    public function __construct(
        private CuicIntegrationInterface $cuic,
        private CuicDataNormalizationService $normalizer,
        private AgentStateTransitionRepositoryInterface $repository,
    ) {}

    public function handle(string $agentLoginId, string $dateFrom, string $dateTo): array
    {
        try {
            $rawData = $this->cuic->executeAgentStateTransitions($agentLoginId, $dateFrom, $dateTo);

            $results = [];
            $transitions = [];

            foreach ($rawData as $row) {
                $transition = $this->normalizer->normalizeStateTransition($row);
                $transitions[] = $transition;
                $results[] = $transition;
            }

            if (! empty($transitions)) {
                $this->repository->bulkInsert($transitions);
            }

            Log::info('Agent state transitions fetched from CUIC.', [
                'agent_login_id' => $agentLoginId,
                'records' => count($results),
            ]);

            return $results;
        } catch (\Throwable $e) {
            Log::error('Failed to fetch agent state transitions from CUIC.', [
                'agent_login_id' => $agentLoginId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
