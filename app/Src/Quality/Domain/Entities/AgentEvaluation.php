<?php

declare(strict_types=1);

namespace App\Src\Quality\Domain\Entities;

use DateTimeImmutable;

final class AgentEvaluation {
    public function __construct(
        private ?int $id,
        private readonly int $agentId,
        private readonly int $evaluatorId,
        private readonly int $formId,
        private readonly array $scores,
        private ?float $totalScore,
        private ?string $comments,
        private string $status,
        private ?DateTimeImmutable $evaluatedAt,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public const STATUS_DRAFT = 'draft';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_VOID = 'void';

    public static function create(int $agentId, int $evaluatorId, int $formId, array $scores, ?string $comments = null): self {
        return new self(null, $agentId, $evaluatorId, $formId, $scores, null, $comments, self::STATUS_DRAFT, null, new DateTimeImmutable());
    }

    public function id(): ?int {
        return $this->id;
    }
    public function agentId(): int {
        return $this->agentId;
    }
    public function evaluatorId(): int {
        return $this->evaluatorId;
    }
    public function formId(): int {
        return $this->formId;
    }
    public function scores(): array {
        return $this->scores;
    }
    public function totalScore(): ?float {
        return $this->totalScore;
    }
    public function comments(): ?string {
        return $this->comments;
    }
    public function status(): string {
        return $this->status;
    }
    public function evaluatedAt(): ?DateTimeImmutable {
        return $this->evaluatedAt;
    }

    public function complete(array $criteria, float $maxScore): void {
        $hasFatalError = false;
        $weightedSum = 0.0;
        $totalWeight = 0.0;

        foreach ($criteria as $criterion) {
            if (!$criterion instanceof EvaluationCriteria)
                continue;

            $score = $this->scores[$criterion->id() ?? 0] ?? 0;
            $totalWeight += $criterion->weight();

            if ($criterion->isFatalError() && $score <= 0) {
                $hasFatalError = true;
            }

            $weightedSum += ($score / max($criterion->maxScore(), 1)) * $criterion->weight() * $maxScore;
        }

        if ($hasFatalError) {
            $this->totalScore = 0.0;
            $this->comments = ($this->comments ? $this->comments . "\n" : '') . '[ANULADO] Error crítico detectado.';
            $this->status = self::STATUS_VOID;
        } else {
            $this->totalScore = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : 0;
            $this->status = self::STATUS_COMPLETED;
        }

        $this->evaluatedAt = new DateTimeImmutable();
    }

    public function dispute(): void {
        if ($this->status !== self::STATUS_COMPLETED) {
            throw new \DomainException('Solo evaluaciones completadas pueden ser disputadas.');
        }
        $this->status = self::STATUS_DISPUTED;
    }

    public function getScoreForCriterion(int $criterionId): int {
        return $this->scores[$criterionId] ?? 0;
    }
}
