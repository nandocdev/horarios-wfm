<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Domain\ValueObjects;

use App\Modules\AuditModule\Domain\Enums\AuditActionEnum;

final readonly class AuditAction
{
    public function __construct(
        private AuditActionEnum $action
    ) {}

    public static function fromString(string $value): self
    {
        return new self(AuditActionEnum::fromString($value));
    }

    public function value(): string
    {
        return $this->action->value;
    }

    public function equals(self $other): bool
    {
        return $this->action === $other->action;
    }
}
