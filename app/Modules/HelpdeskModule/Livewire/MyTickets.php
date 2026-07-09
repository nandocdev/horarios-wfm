<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Livewire;

use App\Modules\HelpdeskModule\Actions\SubmitTicketAction;
use App\Modules\HelpdeskModule\Enums\TicketPriority;
use App\Modules\HelpdeskModule\Models\HelpdeskCategory;
use App\Modules\HelpdeskModule\Models\HelpdeskTicket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class MyTickets extends Component
{
    use WithPagination;

    #[Rule('required|string|max:255')]
    public string $subject = '';

    #[Rule('required|string|min:10')]
    public string $description = '';

    #[Rule('required|exists:helpdesk_categories,id')]
    public string $categoryId = '';

    #[Rule('required|in:low,medium,high,urgent')]
    public string $priority = 'medium';

    public bool $showCreateModal = false;

    public function openCreateModal(): void
    {
        $this->reset(['subject', 'description', 'categoryId', 'priority']);
        $this->showCreateModal = true;
    }

    public function submit(SubmitTicketAction $action): void
    {
        $this->validate();

        $employee = Auth::user()->employee;
        if (! $employee) {
            return;
        }

        Gate::authorize('create', HelpdeskTicket::class);

        $action->execute(
            employee: $employee,
            subject: $this->subject,
            description: $this->description,
            categoryId: (int) $this->categoryId,
            priority: TicketPriority::from($this->priority),
        );

        $this->showCreateModal = false;
        \Flux::toast('Ticket creado exitosamente. El equipo de soporte lo revisará pronto.', variant: 'success');
    }

    public function render()
    {
        $employee = Auth::user()->employee;

        $tickets = $employee
            ? HelpdeskTicket::with(['category', 'assignedAgent'])
                ->where('creator_id', $employee->id)
                ->orderByRaw("CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END")
                ->orderBy('created_at', 'desc')
                ->paginate(10)
            : collect();

        $categories = HelpdeskCategory::where('is_active', true)->orderBy('name')->get();

        return view('helpdesk::livewire.my-tickets', [
            'tickets' => $tickets,
            'categories' => $categories,
        ])->layout('layouts.app');
    }
}
