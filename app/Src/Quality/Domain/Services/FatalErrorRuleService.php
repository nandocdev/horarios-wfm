<?php

declare(strict_types=1);

namespace App\Src\Quality\Domain\Services;

use App\Src\Quality\Domain\Entities\EvaluationCriteria;

final class FatalErrorRuleService
{
    public function hasFatalError(array $scores, array $criteria): bool
    {
        foreach ($criteria as $criterion) {
            if (! $criterion instanceof EvaluationCriteria) continue;
            if (! $criterion->isFatalError()) continue;

            $score = $scores[$criterion->id() ?? 0] ?? $criterion->maxScore();
            if ($score <= 0) {
                return true;
            }
        }

        return false;
    }

    public function getFatalErrors(array $scores, array $criteria): array
    {
        $errors = [];
        foreach ($criteria as $criterion) {
            if (! $criterion instanceof EvaluationCriteria) continue;
            if (! $criterion->isFatalError()) continue;

            $score = $scores[$criterion->id() ?? 0] ?? $criterion->maxScore();
            if ($score <= 0) {
                $errors[] = [
                    'criterion_id' => $criterion->id(),
                    'criterion_name' => $criterion->name(),
                    'description' => $criterion->description(),
                ];
            }
        }
        return $errors;
    }
}
