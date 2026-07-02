<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\ValueObjects;

enum ReactionType: string {
    case Like = 'like';
    case Love = 'love';
    case Celebrate = 'celebrate';
    case Support = 'support';
    case Insightful = 'insightful';

    public function emoji(): string
    {
        return match ($this) {
            self::Like => '👍',
            self::Love => '❤️',
            self::Celebrate => '🎉',
            self::Support => '🤝',
            self::Insightful => '💡',
        };
    }
}
