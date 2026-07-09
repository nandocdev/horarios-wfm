<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Livewire;

use App\Modules\HelpdeskModule\Actions\AddCommentAction;
use App\Modules\HelpdeskModule\Actions\AssignTicketAction;
use App\Modules\HelpdeskModule\Actions\ChangeTicketStatusAction;
use App\Modules\HelpdeskModule\Enums\TicketStatus;
use App\Modules\HelpdeskModule\Models\HelpdeskTicket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Rule;
use Livewire\Component;

class TicketDetail extends Component
{
    public HelpdeskTicket $ticket;

    #[Rule('required|string|min:2')]
    public string $newComment = '';

    public bool $isInternalNote = false;

    public bool $isSupport = false;

    public function mount(HelpdeskTicket $ticket): void
    {
        $this->ticket = $ticket->load(['category', 'creator.position', 'assignedAgent']);

        Gate::authorize('view', $this->ticket);

        $this->isSupport = Auth::user()->can('helpdesk.manage');
    }

    public function addComment(AddCommentAction $action): void
    {
        $this->validate();

        $employee = Auth::user()->employee;
        if (! $employee) {
            return;
        }

        $action->execute(
            ticket: $this->ticket,
            author: $employee,
            content: $this->newComment,
            isSupport: $this->isSupport,
            isInternal: $this->isInternalNote,
        );

        $this->reset(['newComment', 'isInternalNote']);
        $this->ticket->refresh();

        \Flux::toast('Comentario añadido.');
    }

    public function changeStatus(string $status, ChangeTicketStatusAction $action): void
    {
        Gate::authorize('update', $this->ticket);

        $newStatus = TicketStatus::tryFrom($status);
        if (! $newStatus) {
            return;
        }

        $action->execute($this->ticket, $newStatus);
        $this->ticket->refresh();

        \Flux::toast('Estado del ticket actualizado a: '.$this->ticket->status_label);
    }

    public function takeTicket(AssignTicketAction $action): void
    {
        Gate::authorize('assign', $this->ticket);

        $employee = Auth::user()->employee;
        if (! $employee) {
            return;
        }

        $action->execute($this->ticket, $employee);
        $this->ticket->refresh();

        \Flux::toast('Has tomado el ticket correctamente.');
    }

    public function render()
    {
        return view('helpdesk::livewire.ticket-detail', [
            'comments' => $this->ticket->comments()
                ->with('author.position')
                ->orderBy('created_at', 'asc')
                ->get(),
        ])->layout('layouts.app');
    }
}
