<?php

declare(strict_types=1);

namespace App\Src\Wfm\Infrastructure\Services;

use App\Src\Wfm\Domain\Repositories\IntradayRepositoryInterface;
use App\Src\Wfm\Domain\Services\GetExpectedAgentStateService;
use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;

final class CachedIntradayService
{
    private const CACHE_TTL = 60;

    public function __construct(
        private GetExpectedAgentStateService $stateService,
        private IntradayRepositoryInterface $intradayRepo,
    ) {}

    public function getAgentState(int $employeeId, ?DateTimeImmutable $now = null): array
    {
        $now = $now ?? new DateTimeImmutable();
        $cacheKey = "agent_state:{$employeeId}:{$now->format('Y-m-d H:i')}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($employeeId, $now) {
            $activities = $this->intradayRepo->findActivitiesByEmployee($employeeId, $now);

            return $this->stateService->execute(
                now: $now,
                intradayActivities: $activities,
                exceptions: [],
            );
        });
    }

    public function getAgentStateBatch(array $employeeIds, ?DateTimeImmutable $now = null): array
    {
        $now = $now ?? new DateTimeImmutable();
        $results = [];

        foreach ($employeeIds as $id) {
            $results[$id] = $this->getAgentState((int) $id, $now);
        }

        return $results;
    }
}
