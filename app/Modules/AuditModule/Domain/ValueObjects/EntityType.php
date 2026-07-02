<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Domain\ValueObjects;

final readonly class EntityType
{
    public function __construct(
        private string $value
    ) {
        if (empty($this->value)) {
            throw new \InvalidArgumentException('Entity type cannot be empty');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function basename(): string
    {
        return class_basename($this->value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
