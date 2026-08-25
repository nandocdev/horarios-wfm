<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Actions;

use App\Modules\HelpdeskModule\Enums\TicketStatus;
use App\Modules\HelpdeskModule\Models\HelpdeskTicket;
use App\Shared\Contracts\Employees\EmployeeInterface;

final class AssignTicketAction
{
    public function execute(HelpdeskTicket $ticket, EmployeeInterface $agent): void
    {
        $newStatus = $ticket->status === TicketStatus::New->value
            ? TicketStatus::Open->value
            : $ticket->status;

        $ticket->update([
            // assigned_agent_id referencia users.id (esquema de actores).
            'assigned_agent_id' => $agent->getUserId(),
            'status' => $newStatus,
            'status' => $newStatus,
        ]);
    }
}
