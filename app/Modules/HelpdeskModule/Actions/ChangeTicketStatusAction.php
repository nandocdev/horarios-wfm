<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Actions;

use App\Modules\HelpdeskModule\Enums\TicketStatus;
use App\Modules\HelpdeskModule\Models\HelpdeskTicket;

final class ChangeTicketStatusAction
{
    public function execute(HelpdeskTicket $ticket, TicketStatus $status): void
    {
        $updates = ['status' => $status->value];

        if ($status === TicketStatus::Resolved) {
            $updates['resolved_at'] = now();
        } elseif ($status === TicketStatus::Closed) {
            $updates['closed_at'] = now();
        }

        $ticket->update($updates);
    }
}
