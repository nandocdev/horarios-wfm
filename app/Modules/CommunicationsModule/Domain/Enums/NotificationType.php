<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Enums;

enum NotificationType: string
{
    case Comment = 'comment';
    case Reaction = 'reaction';
    case Mention = 'mention';
    case Reply = 'reply';
    case NewsPublished = 'news_published';
    case PollExpired = 'poll_expired';
    case NewsletterAuto = 'newsletter_auto';
    case WeeklySchedulePublished = 'weekly_schedule.published';
    case ScheduleAssignmentUpdated = 'schedule.assignment_updated';
    case LeaveRequestCreated = 'leave_request.created';
    case LeaveRequestDecision = 'leave_request.decision';
    case ShiftSwapRequested = 'shift_swap.requested';
    case ShiftSwapApproved = 'shift_swap.approved';
}
