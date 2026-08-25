<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Actions;

use App\Modules\HelpdeskModule\Enums\TicketPriority;
use App\Modules\HelpdeskModule\Enums\TicketStatus;
use App\Modules\HelpdeskModule\Models\HelpdeskTicket;
use App\Shared\Contracts\Employees\EmployeeInterface;

final class SubmitTicketAction
{
    public function execute(
        EmployeeInterface $employee,
        string $subject,
        string $description,
        int $categoryId,
        TicketPriority $priority = TicketPriority::Medium,
    ): HelpdeskTicket {
        return HelpdeskTicket::create([
            'subject' => $subject,
            'description' => $description,
            'category_id' => $categoryId,
            'priority' => $priority->value,
            // creator_id referencia users.id (esquema de actores).
            'creator_id' => $employee->getUserId(),
            'status' => TicketStatus::New->value,
        ]);
    }
}
