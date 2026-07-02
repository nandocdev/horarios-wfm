<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\ValueObjects;

enum ContentStatus: string {
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Archived = 'archived';

    public function canBeEdited(): bool
    {
        return match ($this) {
            self::Draft, self::PendingReview => true,
            self::Published, self::Archived => false,
        };
    }

    public function canBeModerated(): bool
    {
        return match ($this) {
            self::PendingReview => true,
            self::Draft, self::Published, self::Archived => false,
        };
    }

    public function isPublic(): bool
    {
        return $this === self::Published;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::PendingReview => 'Pendiente de revisión',
            self::Published => 'Publicado',
            self::Archived => 'Archivado',
        };
    }
}
