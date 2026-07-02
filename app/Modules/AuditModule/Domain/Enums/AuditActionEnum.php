<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Domain\Enums;

enum AuditActionEnum: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case WeeklySchedulePublished = 'weekly_schedule.published';
    case LeaveRequestCreated = 'leave_request.created';
    case LeaveRequestApproved = 'leave_request.approved';
    case LeaveRequestRejected = 'leave_request.rejected';
    case ShiftSwapApproved = 'shift_swap.approved';

    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? throw new \InvalidArgumentException("Unknown audit action: {$value}");
    }
}
