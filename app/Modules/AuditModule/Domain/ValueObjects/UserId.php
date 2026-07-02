<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Domain\ValueObjects;

final readonly class UserId
{
    public function __construct(
        private ?int $value
    ) {}

    public function value(): ?int
    {
        return $this->value;
    }

    public function isSystem(): bool
    {
        return $this->value === null;
    }
}
