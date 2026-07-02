<?php

declare(strict_types=1);

namespace App\Src\Workflows\Domain\ValueObjects;

final class WorkflowState
{
    public const PENDING = 'pending';
    public const L1_APPROVED = 'l1_approved';
    public const L2_APPROVED = 'l2_approved';
    public const L3_APPROVED = 'l3_approved';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';

    private const VALID_TRANSITIONS = [
        self::PENDING => [self::L1_APPROVED, self::REJECTED, self::CANCELLED],
        self::L1_APPROVED => [self::L2_APPROVED, self::REJECTED, self::CANCELLED],
        self::L2_APPROVED => [self::L3_APPROVED, self::APPROVED, self::REJECTED, self::CANCELLED],
        self::L3_APPROVED => [self::APPROVED, self::REJECTED],
        self::APPROVED => [],
        self::REJECTED => [],
        self::CANCELLED => [],
    ];

    private const LEVEL = [
        self::PENDING => 0,
        self::L1_APPROVED => 1,
        self::L2_APPROVED => 2,
        self::L3_APPROVED => 3,
        self::APPROVED => 4,
        self::REJECTED => -1,
        self::CANCELLED => -2,
    ];

    public function __construct(
        private readonly string $value = self::PENDING,
    ) {
        if (! in_array($value, self::validStates(), true)) {
            throw new \InvalidArgumentException("Invalid workflow state: {$value}");
        }
    }

    public static function validStates(): array
    {
        return [
            self::PENDING, self::L1_APPROVED, self::L2_APPROVED, self::L3_APPROVED,
            self::APPROVED, self::REJECTED, self::CANCELLED,
        ];
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target->value, self::VALID_TRANSITIONS[$this->value] ?? [], true);
    }

    public function transitionTo(self $target): self
    {
        if (! $this->canTransitionTo($target)) {
            throw new \DomainException(
                "Cannot transition from '{$this->value}' to '{$target->value}'."
            );
        }
        return $target;
    }

    public function level(): int
    {
        return self::LEVEL[$this->value] ?? 0;
    }

    public function needsMoreApprovals(): bool
    {
        return in_array($this->value, [self::PENDING, self::L1_APPROVED, self::L2_APPROVED], true);
    }

    public function isFinal(): bool
    {
        return in_array($this->value, [self::APPROVED, self::REJECTED, self::CANCELLED], true);
    }

    public function isRejected(): bool { return $this->value === self::REJECTED; }
    public function isApproved(): bool { return $this->value === self::APPROVED; }
    public function isPending(): bool { return $this->value === self::PENDING; }

    public function value(): string { return $this->value; }

    public function equals(self $other): bool { return $this->value === $other->value; }

    public function __toString(): string { return $this->value; }
}
