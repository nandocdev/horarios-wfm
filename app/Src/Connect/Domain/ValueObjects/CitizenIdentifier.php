<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\ValueObjects;

final class CitizenIdentifier
{
    public function __construct(
        private readonly string $value,
    ) {
        if (! preg_match('/^\d{8,12}$/', $value)) {
            throw new \InvalidArgumentException("Invalid citizen identifier: {$value}");
        }
    }

    public function value(): string { return $this->value; }

    public function __toString(): string { return $this->value; }
}
