<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\ValueObjects;

final class CallStatus {
    public const QUEUED = 'queued';
    public const RINGING = 'ringing';
    public const CONNECTED = 'connected';
    public const ON_HOLD = 'on_hold';
    public const COMPLETED = 'completed';
    public const CLOSED = 'closed';
    public const FAILED = 'failed';

    private const VALID = [
        self::QUEUED, self::RINGING, self::CONNECTED, self::ON_HOLD, self::COMPLETED, self::CLOSED, self::FAILED,
    ];

    public function __construct(
        private readonly string $value = self::QUEUED,
    ) {
        if (!in_array($value, self::VALID, true)) {
            throw new \InvalidArgumentException("Invalid CallStatus: {$value}");
        }
    }

    public function value(): string {
        return $this->value;
    }
    public function isFinal(): bool {
        return in_array($this->value, [self::COMPLETED, self::CLOSED, self::FAILED], true);
    }
    public function isActive(): bool {
        return !$this->isFinal();
    }

    public static function queued(): self {
        return new self(self::QUEUED);
    }
    public static function connected(): self {
        return new self(self::CONNECTED);
    }
    public static function completed(): self {
        return new self(self::COMPLETED);
    }

    public static function closed(): self {
        return new self(self::CLOSED);
    }

    public static function failed(): self {
        return new self(self::FAILED);
    }

    public static function ringing(): self {
        return new self(self::RINGING);
    }

    public static function onHold(): self {
        return new self(self::ON_HOLD);
    }

    public function __toString(): string {
        return $this->value;
    }
}
