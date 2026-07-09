<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Baja',
            self::Medium => 'Media',
            self::High => 'Alta',
            self::Urgent => 'Urgente',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Urgent => 1,
            self::High => 2,
            self::Medium => 3,
            self::Low => 4,
        };
    }
}
