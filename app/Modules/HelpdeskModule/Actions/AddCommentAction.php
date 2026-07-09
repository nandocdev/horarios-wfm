<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Actions;

use App\Modules\HelpdeskModule\Enums\TicketStatus;
use App\Modules\HelpdeskModule\Models\HelpdeskTicket;
use App\Modules\HelpdeskModule\Models\HelpdeskTicketComment;
use App\Shared\Contracts\Employees\EmployeeInterface;

final class AddCommentAction
{
    public function __construct(
        private readonly AssignTicketAction $assignTicket,
        private readonly ChangeTicketStatusAction $changeStatus,
    ) {}

    public function execute(
        HelpdeskTicket $ticket,
        EmployeeInterface $author,
        string $content,
        bool $isSupport = false,
        bool $isInternal = false,
    ): HelpdeskTicketComment {
        $comment = HelpdeskTicketComment::create([
            'ticket_id' => $ticket->id,
            'author_id' => $author->getId(),
            'content' => $content,
            'is_internal' => $isSupport ? $isInternal : false,
        ]);

        if ($isSupport) {
            if (empty($ticket->assigned_agent_id)) {
                $this->assignTicket->execute($ticket, $author);
                $ticket->refresh();
            }

            if (! $isInternal && in_array($ticket->status, array_map(fn (TicketStatus $s) => $s->value, TicketStatus::active()))) {
                $this->changeStatus->execute($ticket, TicketStatus::InProgress);
                $ticket->refresh();
            }
        }

        return $comment;
    }
}
