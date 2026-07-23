<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum NotificationType: string
{
    case ShiftSwapRequested = 'shift_swap.requested';
    case ShiftSwapAccepted = 'shift_swap.accepted';
    case ShiftSwapApproved = 'shift_swap.approved';
    case ShiftSwapRejected = 'shift_swap.rejected';
    case ShiftSwapCancelled = 'shift_swap.cancelled';
    case LeaveRequestCreated = 'leave_request.created';
    case LeaveRequestDecision = 'leave_request.decision';
    case SchedulePublished = 'schedule.published';
    case ScheduleUpdated = 'schedule.updated';
    case IntradayActivity = 'intraday.activity';
    case AttendanceIncident = 'attendance.incident';
    case AdherenceAlert = 'adherence.alert';
}
