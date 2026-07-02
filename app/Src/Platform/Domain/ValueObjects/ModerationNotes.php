<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\ValueObjects;

use DateTimeImmutable;

final readonly class ModerationNotes {
    public function __construct(
        public string $notes = '',
        public bool $requiresAction = false,
        public ?int $moderatedBy = null,
        public ?DateTimeImmutable $moderatedAt = null,
    ) {
    }
}
