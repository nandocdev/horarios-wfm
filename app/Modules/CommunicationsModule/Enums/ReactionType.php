<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Enums;

enum ReactionType: string
{
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
            self::Support => '🙌',
            self::Insightful => '💡',
        };
    }

    public static function fromName(string $name): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $name || $case->name === $name) {
                return $case;
            }
        }

        return null;
    }
}
