<?php

declare(strict_types=1);

namespace App\Src\Quality\Domain\Entities;

final class EvaluationCriteria {
    public function __construct(
        private readonly ?int $id,
        private readonly string $name,
        private readonly int $maxScore,
        private readonly float $weight,
        private readonly bool $isFatalError = false,
        private readonly ?string $description = null,
    ) {
    }

    public static function create(string $name, int $maxScore, float $weight, bool $isFatalError = false, ?string $description = null): self {
        return new self(null, $name, $maxScore, $weight, $isFatalError, $description);
    }

    public function id(): ?int {
        return $this->id;
    }
    public function name(): string {
        return $this->name;
    }
    public function maxScore(): int {
        return $this->maxScore;
    }
    public function weight(): float {
        return $this->weight;
    }
    public function isFatalError(): bool {
        return $this->isFatalError;
    }
    public function description(): ?string {
        return $this->description;
    }
}
