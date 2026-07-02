<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\ValueObjects;

final class QueueName
{
    public function __construct(
        private readonly string $value,
    ) {
        if (mb_strlen(trim($value)) === 0) {
            throw new \InvalidArgumentException('Queue name cannot be empty.');
        }
        if (mb_strlen($value) > 100) {
            throw new \InvalidArgumentException('Queue name cannot exceed 100 characters.');
        }
    }

    public function value(): string { return $this->value; }

    public function __toString(): string { return $this->value; }
}
