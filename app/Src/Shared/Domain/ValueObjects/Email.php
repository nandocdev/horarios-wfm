<?php

declare(strict_types=1);

namespace App\Src\Shared\Domain\ValueObjects;

use App\Src\Shared\Domain\Exceptions\InvalidArgumentDomainException;

final class Email {
    private string $value;

    public function __construct(string $value) {
        $value = strtolower(trim($value));

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentDomainException("Invalid email address: {$value}");
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self {
        return new self($value);
    }

    public function value(): string {
        return $this->value;
    }

    public function localPart(): string {
        return explode('@', $this->value)[0];
    }

    public function domain(): string {
        return explode('@', $this->value)[1];
    }

    public function equals(self $other): bool {
        return $this->value === $other->value;
    }

    public function __toString(): string {
        return $this->value;
    }
}
