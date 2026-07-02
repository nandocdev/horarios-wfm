<?php

declare(strict_types=1);

namespace App\Src\Quality\Infrastructure\Persistence;

use App\Src\Quality\Application\Mappers\QualityMapper;
use App\Src\Quality\Domain\Entities\AgentEvaluation;
use App\Src\Quality\Domain\Entities\DisputeRequest;
use App\Src\Quality\Domain\Entities\EvaluationForm;
use App\Src\Quality\Domain\Repositories\QualityRepositoryInterface;

final class EloquentQualityRepository implements QualityRepositoryInterface
{
    public function saveForm(EvaluationForm $form): EvaluationForm
    {
        $eloquent = EloquentEvaluationForm::updateOrCreate(
            ['id' => $form->id()],
            ['name' => $form->name(), 'description' => $form->description(), 'is_active' => $form->isActive()],
        );

        return QualityMapper::formToDomain($eloquent->load('criteria'));
    }

    public function findFormById(int $id): ?EvaluationForm
    {
        $eloquent = EloquentEvaluationForm::with('criteria')->find($id);
        return $eloquent ? QualityMapper::formToDomain($eloquent) : null;
    }

    public function findAllForms(): array
    {
        return EloquentEvaluationForm::with('criteria')->get()
            ->map(fn (EloquentEvaluationForm $e) => QualityMapper::formToDomain($e))
            ->toArray();
    }

    public function saveEvaluation(AgentEvaluation $evaluation): AgentEvaluation
    {
        $eloquent = EloquentAgentEvaluation::updateOrCreate(
            ['id' => $evaluation->id()],
            QualityMapper::evaluationToEloquent($evaluation),
        );

        return QualityMapper::evaluationToDomain($eloquent);
    }

    public function findEvaluationById(int $id): ?AgentEvaluation
    {
        $eloquent = EloquentAgentEvaluation::find($id);
        return $eloquent ? QualityMapper::evaluationToDomain($eloquent) : null;
    }

    public function findEvaluationsByAgent(int $agentId): array
    {
        return EloquentAgentEvaluation::where('agent_id', $agentId)->latest()->get()
            ->map(fn (EloquentAgentEvaluation $e) => QualityMapper::evaluationToDomain($e))
            ->toArray();
    }

    public function saveDispute(DisputeRequest $dispute): DisputeRequest
    {
        $eloquent = EloquentDisputeRequest::updateOrCreate(
            ['id' => $dispute->id()],
            [
                'evaluation_id' => $dispute->evaluationId(),
                'raised_by_agent_id' => $dispute->raisedByAgentId(),
                'reason' => $dispute->reason(),
                'status' => $dispute->status(),
            ],
        );

        return QualityMapper::disputeToDomain($eloquent);
    }

    public function findDisputesByEvaluation(int $evaluationId): array
    {
        return EloquentDisputeRequest::where('evaluation_id', $evaluationId)->get()
            ->map(fn (EloquentDisputeRequest $e) => QualityMapper::disputeToDomain($e))
            ->toArray();
    }
}
