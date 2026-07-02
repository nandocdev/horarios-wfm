<?php

declare(strict_types=1);

namespace App\Src\Quality\Domain\Repositories;

use App\Src\Quality\Domain\Entities\AgentEvaluation;
use App\Src\Quality\Domain\Entities\DisputeRequest;
use App\Src\Quality\Domain\Entities\EvaluationForm;

interface QualityRepositoryInterface {
    public function saveForm(EvaluationForm $form): EvaluationForm;
    public function findFormById(int $id): ?EvaluationForm;
    public function findAllForms(): array;

    public function saveEvaluation(AgentEvaluation $evaluation): AgentEvaluation;
    public function findEvaluationById(int $id): ?AgentEvaluation;
    public function findEvaluationsByAgent(int $agentId): array;

    public function saveDispute(DisputeRequest $dispute): DisputeRequest;
    public function findDisputesByEvaluation(int $evaluationId): array;
}
