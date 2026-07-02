<?php

declare(strict_types=1);

namespace App\Src\Quality\Application\Handlers;

use App\Src\Quality\Application\DTOs\SubmitEvaluationDTO;
use App\Src\Quality\Domain\Entities\AgentEvaluation;
use App\Src\Quality\Domain\Events\EvaluationCompleted;
use App\Src\Quality\Domain\Repositories\QualityRepositoryInterface;

final class SubmitEvaluationHandler
{
    public function __construct(
        private QualityRepositoryInterface $repository,
    ) {}

    public function handle(SubmitEvaluationDTO $dto): AgentEvaluation
    {
        $form = $this->repository->findFormById($dto->formId);

        if ($form === null) {
            throw new \RuntimeException("Evaluation form #{$dto->formId} not found.");
        }

        $evaluation = AgentEvaluation::create(
            agentId: $dto->agentId,
            evaluatorId: $dto->evaluatorId,
            formId: $dto->formId,
            scores: $dto->scores,
            comments: $dto->comments,
        );

        $evaluation->complete($form->criteria(), 100);

        $saved = $this->repository->saveEvaluation($evaluation);

        event(new EvaluationCompleted($saved));

        return $saved;
    }
}
