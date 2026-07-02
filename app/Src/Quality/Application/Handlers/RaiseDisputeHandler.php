<?php

declare(strict_types=1);

namespace App\Src\Quality\Application\Handlers;

use App\Src\Quality\Application\DTOs\RaiseDisputeDTO;
use App\Src\Quality\Domain\Entities\DisputeRequest;
use App\Src\Quality\Domain\Repositories\QualityRepositoryInterface;

final class RaiseDisputeHandler
{
    public function __construct(
        private QualityRepositoryInterface $repository,
    ) {}

    public function handle(RaiseDisputeDTO $dto): DisputeRequest
    {
        $evaluation = $this->repository->findEvaluationById($dto->evaluationId);

        if ($evaluation === null) {
            throw new \RuntimeException("Evaluation #{$dto->evaluationId} not found.");
        }

        $evaluation->dispute();

        $this->repository->saveEvaluation($evaluation);

        $dispute = DisputeRequest::raise($dto->evaluationId, $dto->agentId, $dto->reason);

        return $this->repository->saveDispute($dispute);
    }
}
