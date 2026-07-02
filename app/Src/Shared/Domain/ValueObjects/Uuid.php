<?php

declare(strict_types=1);

namespace App\Src\Shared\Domain\ValueObjects;

use App\Src\Shared\Domain\Exceptions\InvalidArgumentDomainException;

final class Uuid {
    private string $value;

    public function __construct(string $value) {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            throw new InvalidArgumentDomainException("Invalid UUID format: {$value}");
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self {
        return new self($value);
    }

    public function value(): string {
        return $this->value;
    }

    public function equals(self $other): bool {
        return $this->value === $other->value;
    }

    public function __toString(): string {
        return $this->value;
    }
}
