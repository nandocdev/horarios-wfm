<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Domain\Entities;

use DateTimeImmutable;

final class AttendancePunch
{
    public const TYPE_ENTRY = 'entry';
    public const TYPE_EXIT = 'exit';
    public const TYPE_BREAK_START = 'break_start';
    public const TYPE_BREAK_END = 'break_end';
    public const TYPE_LUNCH_START = 'lunch_start';
    public const TYPE_LUNCH_END = 'lunch_end';

    public function __construct(
        private readonly ?int $id,
        private readonly int $employeeId,
        private readonly string $type,
        private readonly DateTimeImmutable $punchedAt,
        private readonly ?string $source = 'manual',
        private readonly ?string $externalId = null,
    ) {}

    public static function create(
        int $employeeId,
        string $type,
        DateTimeImmutable $punchedAt,
        ?string $source = 'manual',
        ?string $externalId = null,
    ): self {
        return new self(null, $employeeId, $type, $punchedAt, $source, $externalId);
    }

    public function id(): ?int { return $this->id; }
    public function employeeId(): int { return $this->employeeId; }
    public function type(): string { return $this->type; }
    public function punchedAt(): DateTimeImmutable { return $this->punchedAt; }
    public function source(): ?string { return $this->source; }
    public function externalId(): ?string { return $this->externalId; }
}
