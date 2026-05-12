<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Livewire;

use App\Modules\HelpdeskModule\Models\HelpdeskCategory;
use App\Modules\HelpdeskModule\Models\HelpdeskTicket;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MyTickets extends Component
{
    use WithPagination;

    // Campos del formulario
    public $subject = '';

    public $description = '';

    public $categoryId = '';

    public $priority = 'medium';

    public $showCreateModal = false;

    protected $rules = [
        'subject' => 'required|string|max:255',
        'description' => 'required|string|min:10',
        'categoryId' => 'required|exists:helpdesk_categories,id',
        'priority' => 'required|in:low,medium,high,urgent',
    ];

    public function openCreateModal()
    {
        $this->reset(['subject', 'description', 'categoryId', 'priority']);
        $this->showCreateModal = true;
    }

    public function submit()
    {
        $this->validate();

        $employee = Auth::user()->employee;
        if (! $employee) {
            return;
        }

        HelpdeskTicket::create([
            'subject' => $this->subject,
            'description' => $this->description,
            'category_id' => $this->categoryId,
            'priority' => $this->priority,
            'creator_id' => $employee->id,
            'status' => 'new',
        ]);

        $this->showCreateModal = false;
        \Flux::toast('Ticket creado exitosamente. El equipo de soporte lo revisará pronto.', variant: 'success');
    }

    public function render()
    {
        $employee = Auth::user()->employee;

        $tickets = $employee
            ? HelpdeskTicket::with(['category', 'assignedAgent'])
                ->where('creator_id', $employee->id)
                ->orderByRaw("CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END") // Abiertos primero
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
