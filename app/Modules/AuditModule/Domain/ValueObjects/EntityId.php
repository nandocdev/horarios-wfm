<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Domain\ValueObjects;

final readonly class EntityId
{
    public function __construct(
        private string|int $value
    ) {
        if (empty($this->value) && $this->value !== 0 && $this->value !== '0') {
            throw new \InvalidArgumentException('Entity ID cannot be empty');
        }
    }

    public function value(): string|int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
