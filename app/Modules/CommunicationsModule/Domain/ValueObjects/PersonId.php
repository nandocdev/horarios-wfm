<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\ValueObjects;

final readonly class PersonId
{
    public function __construct(
        private int $value
    ) {
        if ($this->value < 1) {
            throw new \InvalidArgumentException('Person ID must be a positive integer');
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
