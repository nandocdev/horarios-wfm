<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Domain\Entities;

final class IncidentType
{
    public const CODE_LATE = 'LATE';
    public const CODE_ABSENT = 'ABSENT';
    public const CODE_EARLY_EXIT = 'EARLY_EXIT';
    public const CODE_MISSING_PUNCH = 'MISSING_PUNCH';

    public function __construct(
        private readonly ?int $id,
        private readonly string $code,
        private readonly string $name,
        private readonly string $color = '#ef4444',
        private readonly bool $requiresJustification = true,
        private readonly bool $affectsAvailability = true,
    ) {}

    public static function late(): self
    {
        return new self(null, self::CODE_LATE, 'Tardanza', '#f59e0b', true, true);
    }

    public static function absent(): self
    {
        return new self(null, self::CODE_ABSENT, 'Ausencia', '#ef4444', true, true);
    }

    public static function earlyExit(): self
    {
        return new self(null, self::CODE_EARLY_EXIT, 'Salida Temprana', '#f97316', true, true);
    }

    public function id(): ?int { return $this->id; }
    public function code(): string { return $this->code; }
    public function name(): string { return $this->name; }
    public function color(): string { return $this->color; }
    public function requiresJustification(): bool { return $this->requiresJustification; }
    public function affectsAvailability(): bool { return $this->affectsAvailability; }
}
