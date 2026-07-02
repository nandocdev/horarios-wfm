<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Domain\Entities\CsqRealtimeStat;
use App\Src\Connect\Domain\Ports\CuicIntegrationInterface;
use App\Src\Connect\Domain\Repositories\CsqRealtimeStatRepositoryInterface;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;

final readonly class SyncCsqRealtimeStatsHandler
{
    public function __construct(
        private CuicIntegrationInterface $cuic,
        private CsqRealtimeStatRepositoryInterface $repository,
    ) {}

    public function handle(): int
    {
        try {
            $rawStats = $this->cuic->executeRealtimeSnapshot('csq_realtime');

            $saved = 0;
            foreach ($rawStats as $stat) {
                $entity = new CsqRealtimeStat(
                    id: null,
                    csqName: $stat['csq_name'] ?? null,
                    callsWaiting: isset($stat['calls_waiting']) ? (int) $stat['calls_waiting'] : null,
                    longestCallInQueue: isset($stat['longest_call_in_queue']) ? (int) $stat['longest_call_in_queue'] : null,
                    agentsLoggedOn: isset($stat['agents_logged_on']) ? (int) $stat['agents_logged_on'] : null,
                    agentsTalking: isset($stat['agents_talking']) ? (int) $stat['agents_talking'] : null,
                    agentsReady: isset($stat['agents_ready']) ? (int) $stat['agents_ready'] : null,
                    agentsNotReady: isset($stat['agents_not_ready']) ? (int) $stat['agents_not_ready'] : null,
                    agentsAfterCallWork: isset($stat['agents_after_call_work']) ? (int) $stat['agents_after_call_work'] : null,
                    agentsReserved: isset($stat['agents_reserved']) ? (int) $stat['agents_reserved'] : null,
                    serviceLevelShortTerm: isset($stat['service_level_short_term']) ? (float) $stat['service_level_short_term'] : null,
                    serviceLevelLongTerm: isset($stat['service_level_long_term']) ? (float) $stat['service_level_long_term'] : null,
                    callsAbandonedSinceMidnight: isset($stat['calls_abandoned_since_midnight']) ? (int) $stat['calls_abandoned_since_midnight'] : null,
                    callsHandledSinceMidnight: isset($stat['calls_handled_since_midnight']) ? (int) $stat['calls_handled_since_midnight'] : null,
                    totalCallsSinceMidnight: isset($stat['total_calls_since_midnight']) ? (int) $stat['total_calls_since_midnight'] : null,
                    metadata: $stat,
                    createdAt: new DateTimeImmutable(),
                );

                $this->repository->save($entity);
                $saved++;
            }

            Log::info('CSQ realtime stats synced.', [
                'count' => $saved,
            ]);

            return $saved;
        } catch (\Throwable $e) {
            Log::error('CSQ realtime stats sync failed.', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
