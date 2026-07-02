<?php

declare(strict_types=1);

namespace App\Src\Shared\Domain\ValueObjects;

use App\Src\Shared\Domain\Exceptions\InvalidArgumentDomainException;

final class DateRange {
    private \DateTimeImmutable $start;
    private \DateTimeImmutable $end;

    public function __construct(\DateTimeImmutable $start, \DateTimeImmutable $end) {
        if ($start > $end) {
            throw new InvalidArgumentDomainException('Start date must be before or equal to end date.');
        }

        $this->start = $start;
        $this->end = $end;
    }

    public static function fromStrings(string $start, string $end, string $format = 'Y-m-d'): self {
        $startDt = \DateTimeImmutable::createFromFormat($format, $start);
        $endDt = \DateTimeImmutable::createFromFormat($format, $end);

        if (!$startDt || !$endDt) {
            throw new InvalidArgumentDomainException('Invalid date format. Expected format: ' . $format);
        }

        return new self($startDt, $endDt);
    }

    public function start(): \DateTimeImmutable {
        return $this->start;
    }

    public function end(): \DateTimeImmutable {
        return $this->end;
    }

    public function contains(\DateTimeImmutable $date): bool {
        return $date >= $this->start && $date <= $this->end;
    }

    public function overlaps(self $other): bool {
        return $this->start <= $other->end && $this->end >= $other->start;
    }

    public function daysCount(): int {
        return (int) $this->start->diff($this->end)->days + 1;
    }

    public function toArray(): array {
        return [
            'start' => $this->start->format('Y-m-d'),
            'end' => $this->end->format('Y-m-d'),
        ];
    }
}
