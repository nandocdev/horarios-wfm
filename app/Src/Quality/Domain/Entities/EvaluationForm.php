<?php

declare(strict_types=1);

namespace App\Src\Quality\Domain\Entities;

final class EvaluationForm {
    public function __construct(
        private readonly ?int $id,
        private readonly string $name,
        private readonly ?string $description,
        private readonly bool $isActive,
        private array $criteria = [],
    ) {
    }

    public static function create(string $name, ?string $description = null): self {
        return new self(null, $name, $description, true);
    }

    public function id(): ?int {
        return $this->id;
    }
    public function name(): string {
        return $this->name;
    }
    public function description(): ?string {
        return $this->description;
    }
    public function isActive(): bool {
        return $this->isActive;
    }
    public function criteria(): array {
        return $this->criteria;
    }

    public function addCriterion(EvaluationCriteria $criterion): void {
        $this->criteria[] = $criterion;
    }
}
