<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Livewire;

use App\Modules\HelpdeskModule\Models\HelpdeskCategory;
use App\Modules\HelpdeskModule\Models\HelpdeskTicket;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ManageTickets extends Component
{
    use WithPagination;

    // Filtros
    public $statusFilter = 'open_unassigned'; // open_unassigned, my_assigned, all_active, closed

    public $categoryFilter = '';

    public $priorityFilter = '';

    public $search = '';

    public function assignToMe($ticketId)
    {
        $employee = Auth::user()->employee;
        if (! $employee) {
            return;
        }

        $ticket = HelpdeskTicket::findOrFail($ticketId);

        $ticket->update([
            'assigned_agent_id' => $employee->id,
            'status' => $ticket->status === 'new' ? 'open' : $ticket->status,
        ]);

        \Flux::toast('Ticket asignado correctamente.', variant: 'success');
    }

    public function render()
    {
        $employee = Auth::user()->employee;

        $query = HelpdeskTicket::with(['category', 'creator.position', 'assignedAgent'])
            ->orderByRaw("
                CASE priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                    ELSE 5
                END ASC
            ")
            ->orderBy('created_at', 'asc'); // Más viejos primero (SLA)

        // Búsqueda
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('subject', 'ilike', '%'.$this->search.'%')
                    ->orWhere('id', (int) $this->search)
                    ->orWhereHas('creator', function ($q2) {
                        $q2->where('first_name', 'ilike', '%'.$this->search.'%')
                            ->orWhere('last_name', 'ilike', '%'.$this->search.'%');
                    });
            });
        }

        // Filtro de Categoría
        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        // Filtro de Prioridad
        if ($this->priorityFilter) {
            $query->where('priority', $this->priorityFilter);
        }

        // Filtros Rápidos (Bandejas)
        switch ($this->statusFilter) {
            case 'open_unassigned':
                $query->whereIn('status', ['new', 'open'])->whereNull('assigned_agent_id');
                break;
            case 'my_assigned':
                if ($employee) {
                    $query->where('assigned_agent_id', $employee->id)->whereNotIn('status', ['resolved', 'closed']);
                }
                break;
            case 'all_active':
                $query->whereNotIn('status', ['resolved', 'closed']);
                break;
            case 'closed':
                $query->whereIn('status', ['resolved', 'closed'])->orderBy('resolved_at', 'desc');
                break;
        }

        return view('helpdesk::livewire.manage-tickets', [
            'tickets' => $query->paginate(15),
            'categories' => HelpdeskCategory::where('is_active', true)->get(),
        ])->layout('layouts.app');
    }
}
