<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Enums;

enum WorkflowStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Approved => 'Aprobado',
            self::Rejected => 'Rechazado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected, self::Cancelled]);
    }
}
