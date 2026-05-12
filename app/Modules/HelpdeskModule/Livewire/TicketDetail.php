<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Livewire;

use App\Modules\HelpdeskModule\Models\HelpdeskTicket;
use App\Modules\HelpdeskModule\Models\HelpdeskTicketComment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TicketDetail extends Component
{
    public HelpdeskTicket $ticket;

    public string $newComment = '';

    public bool $isInternalNote = false;

    // Verificar si el usuario actual es de soporte/admin
    public bool $isSupport = false;

    protected $rules = [
        'newComment' => 'required|string|min:2',
    ];

    public function mount(HelpdeskTicket $ticket)
    {
        $this->ticket = $ticket->load(['category', 'creator.position', 'assignedAgent']);

        $employee = Auth::user()->employee;

        // Es soporte si tiene permisos de operaciones (WFM/Admin)
        $this->isSupport = Auth::user()->can('operations.view');

        // Validar acceso: o eres el creador, o eres soporte
        if ($this->ticket->creator_id !== $employee?->id && ! $this->isSupport) {
            abort(403, 'No tienes permiso para ver este ticket.');
        }

        // La auto-asignación automática ha sido desactivada por requerimiento. 
        // El agente debe tomar el ticket manualmente desde la bandeja o al responder.
    }

    public function addComment()
    {
        $this->validate();

        $employee = Auth::user()->employee;

        HelpdeskTicketComment::create([
            'ticket_id' => $this->ticket->id,
            'author_id' => $employee->id,
            'content' => $this->newComment,
            'is_internal' => $this->isSupport ? $this->isInternalNote : false,
        ]);

        // Auto-asignación al comentar si es soporte y no estaba asignado
        if ($this->isSupport && empty($this->ticket->assigned_agent_id)) {
            $this->ticket->update(['assigned_agent_id' => $employee->id]);
        }

        // Si soporte responde, cambiar estado a 'En Progreso' si estaba 'Abierto'
        if ($this->isSupport && in_array($this->ticket->status, ['new', 'open']) && ! $this->isInternalNote) {
            $this->ticket->update(['status' => 'in_progress']);
        }

        $this->reset(['newComment', 'isInternalNote']);
        $this->ticket->refresh();

        \Flux::toast('Comentario añadido.');
    }

    public function changeStatus(string $status)
    {
        if (! in_array($status, ['open', 'in_progress', 'on_hold', 'resolved', 'closed'])) {
            return;
        }

        $updates = ['status' => $status];

        if ($status === 'resolved') {
            $updates['resolved_at'] = now();
        } elseif ($status === 'closed') {
            $updates['closed_at'] = now();
        }

        $this->ticket->update($updates);
        $this->ticket->refresh();

        \Flux::toast('Estado del ticket actualizado a: '.$this->ticket->status_label);
    }

    public function takeTicket()
    {
        if (! $this->isSupport) {
            return;
        }

        $employee = Auth::user()->employee;
        if (! $employee) {
            return;
        }

        $this->ticket->update([
            'assigned_agent_id' => $employee->id,
            'status' => $this->ticket->status === 'new' ? 'open' : $this->ticket->status,
        ]);

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
