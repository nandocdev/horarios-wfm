<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\ValueObjects;

final class TelephonyProvider
{
    public const CISCO_FINESSE = 'cisco_finesse';
    public const AVAYA = 'avaya';
    public const GENESYS = 'genesys';
    public const MANUAL = 'manual';

    private const ALL = [self::CISCO_FINESSE, self::AVAYA, self::GENESYS, self::MANUAL];

    public function __construct(
        private readonly string $value,
    ) {
        if (! in_array($value, self::ALL, true)) {
            throw new \InvalidArgumentException("Unknown provider: {$value}");
        }
    }

    public function value(): string { return $this->value; }
    public function isCisco(): bool { return $this->value === self::CISCO_FINESSE; }
    public function isAvaya(): bool { return $this->value === self::AVAYA; }

    public static function ciscoFinesse(): self { return new self(self::CISCO_FINESSE); }
    public static function avaya(): self { return new self(self::AVAYA); }

    public function __toString(): string { return $this->value; }
}
