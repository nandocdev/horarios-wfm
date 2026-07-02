<?php

declare(strict_types=1);

namespace App\Src\Quality\Application\Mappers;

use App\Src\Quality\Domain\Entities\AgentEvaluation;
use App\Src\Quality\Domain\Entities\DisputeRequest;
use App\Src\Quality\Domain\Entities\EvaluationCriteria;
use App\Src\Quality\Domain\Entities\EvaluationForm;
use App\Src\Quality\Infrastructure\Persistence\EloquentAgentEvaluation;
use App\Src\Quality\Infrastructure\Persistence\EloquentDisputeRequest;
use App\Src\Quality\Infrastructure\Persistence\EloquentEvaluationCriteria;
use App\Src\Quality\Infrastructure\Persistence\EloquentEvaluationForm;
use DateTimeImmutable;

final class QualityMapper
{
    public static function formToDomain(EloquentEvaluationForm $e): EvaluationForm
    {
        $criteria = [];
        if ($e->relationLoaded('criteria')) {
            $criteria = $e->criteria->map(fn (EloquentEvaluationCriteria $c) => self::criteriaToDomain($c))->toArray();
        }

        return new EvaluationForm($e->id, $e->name, $e->description, (bool) $e->is_active, $criteria);
    }

    public static function criteriaToDomain(EloquentEvaluationCriteria $e): EvaluationCriteria
    {
        return new EvaluationCriteria($e->id, $e->name, (int) $e->max_score, (float) $e->weight, (bool) $e->is_fatal_error, $e->description);
    }

    public static function evaluationToDomain(EloquentAgentEvaluation $e): AgentEvaluation
    {
        return new AgentEvaluation(
            $e->id, $e->agent_id, $e->evaluator_id, $e->form_id,
            $e->scores ?? [], (float) ($e->total_score ?? 0), $e->comments,
            $e->status ?? AgentEvaluation::STATUS_DRAFT,
            $e->evaluated_at ? self::toImmutable($e->evaluated_at) : null,
            self::toImmutable($e->created_at),
        );
    }

    public static function evaluationToEloquent(AgentEvaluation $e): array
    {
        return [
            'agent_id' => $e->agentId(),
            'evaluator_id' => $e->evaluatorId(),
            'form_id' => $e->formId(),
            'scores' => $e->scores(),
            'total_score' => $e->totalScore(),
            'comments' => $e->comments(),
            'status' => $e->status(),
            'evaluated_at' => $e->evaluatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    public static function disputeToDomain(EloquentDisputeRequest $e): DisputeRequest
    {
        return new DisputeRequest(
            $e->id, $e->evaluation_id, $e->raised_by_agent_id, $e->reason,
            $e->status ?? DisputeRequest::STATUS_OPEN,
            $e->resolution_comment, $e->resolved_by_user_id,
            $e->resolved_at ? self::toImmutable($e->resolved_at) : null,
            self::toImmutable($e->created_at),
        );
    }

    private static function toImmutable(mixed $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) return $date;
        if ($date instanceof \DateTime) return DateTimeImmutable::createFromMutable($date);
        return new DateTimeImmutable((string) $date);
    }
}
