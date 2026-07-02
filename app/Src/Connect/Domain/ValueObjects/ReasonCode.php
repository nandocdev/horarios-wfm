<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\ValueObjects;

final class ReasonCode
{
    public function __construct(
        private readonly int $value,
    ) {
        if ($value < 0 || $value > 999) {
            throw new \InvalidArgumentException("Reason code must be between 0 and 999, got {$value}.");
        }
    }

    public function value(): int { return $this->value; }

    public function __toString(): string { return (string) $this->value; }
}
