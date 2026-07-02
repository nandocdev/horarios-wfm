<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\ValueObjects;

use DateTimeImmutable;

final readonly class DateRange
{
    public function __construct(
        private ?DateTimeImmutable $scheduledAt,
        private ?DateTimeImmutable $archiveAt,
    ) {
        if ($this->scheduledAt !== null && $this->archiveAt !== null && $this->scheduledAt > $this->archiveAt) {
            throw new \InvalidArgumentException('Scheduled date must be before archive date');
        }
    }

    public function scheduledAt(): ?DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function archiveAt(): ?DateTimeImmutable
    {
        return $this->archiveAt;
    }

    public function shouldPublish(DateTimeImmutable $now): bool
    {
        return $this->scheduledAt !== null && $this->scheduledAt <= $now;
    }

    public function shouldArchive(DateTimeImmutable $now): bool
    {
        return $this->archiveAt !== null && $this->archiveAt <= $now;
    }
}
