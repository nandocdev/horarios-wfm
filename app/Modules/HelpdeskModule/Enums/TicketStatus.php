<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case Open = 'open';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nuevo',
            self::Open => 'Abierto',
            self::InProgress => 'En Progreso',
            self::OnHold => 'En Espera',
            self::Resolved => 'Resuelto',
            self::Closed => 'Cerrado',
        };
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::New, self::Open, self::InProgress, self::OnHold => true,
            self::Resolved, self::Closed => false,
        };
    }

    public static function active(): array
    {
        return [self::New, self::Open, self::InProgress, self::OnHold];
    }

    public static function closed(): array
    {
        return [self::Resolved, self::Closed];
    }
}
