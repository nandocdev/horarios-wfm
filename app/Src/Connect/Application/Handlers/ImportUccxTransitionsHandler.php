<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\UccxCallDataDTO;
use App\Src\Connect\Domain\Entities\AgentStateTransition;
use App\Src\Connect\Domain\Events\AgentStateTransitioned;
use App\Src\Connect\Domain\Repositories\AgentStateTransitionRepositoryInterface;
use App\Src\Connect\Domain\Services\CuicDataNormalizationService;
use Illuminate\Support\Facades\Log;

final readonly class ImportUccxTransitionsHandler
{
    public function __construct(
        private CuicDataNormalizationService $normalizer,
        private AgentStateTransitionRepositoryInterface $repository,
    ) {}

    public function handle(array $rawTransitions): int
    {
        $imported = 0;
        $batch = [];

        foreach ($rawTransitions as $raw) {
            $transition = $this->normalizer->normalizeStateTransition($raw);
            $batch[] = $transition;

            event(new AgentStateTransitioned($transition));
            $imported++;
        }

        if (! empty($batch)) {
            $this->repository->bulkInsert($batch);
        }

        Log::info('UCCX transitions imported.', [
            'count' => $imported,
        ]);

        return $imported;
    }
}
