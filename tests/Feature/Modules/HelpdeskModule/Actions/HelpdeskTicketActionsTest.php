<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\HelpdeskModule\Actions;

use App\Modules\CoreModule\Models\User;
use App\Modules\HelpdeskModule\Actions\AddCommentAction;
use App\Modules\HelpdeskModule\Actions\AssignTicketAction;
use App\Modules\HelpdeskModule\Actions\ChangeTicketStatusAction;
use App\Modules\HelpdeskModule\Actions\SubmitTicketAction;
use App\Modules\HelpdeskModule\Enums\TicketPriority;
use App\Modules\HelpdeskModule\Enums\TicketStatus;
use App\Modules\HelpdeskModule\Models\HelpdeskCategory;
use App\Modules\HelpdeskModule\Models\HelpdeskTicket;
use App\Modules\HelpdeskModule\Models\HelpdeskTicketComment;
use App\Modules\PersonnelModule\Models\Employee;

beforeEach(function () {
    $this->category = HelpdeskCategory::factory()->create(['name' => 'Soporte Técnico']);
    $this->employee = Employee::factory()->create();
    $this->user = User::factory()->create();
    $this->employee->user()->associate($this->user)->save();

    $this->supportEmployee = Employee::factory()->create();
    $this->supportUser = User::factory()->create();
    $this->supportEmployee->user()->associate($this->supportUser)->save();
});

it('creates a ticket via SubmitTicketAction', function () {
    $action = app(SubmitTicketAction::class);

    $ticket = $action->execute(
        employee: $this->employee,
        subject: 'No puedo acceder al sistema',
        description: 'Desde esta mañana no puedo iniciar sesión en la plataforma WFM.',
        categoryId: $this->category->id,
        priority: TicketPriority::High,
    );

    expect($ticket)->toBeInstanceOf(HelpdeskTicket::class)
        ->and($ticket->subject)->toBe('No puedo acceder al sistema')
        ->and($ticket->status)->toBe(TicketStatus::New->value)
        ->and($ticket->priority)->toBe(TicketPriority::High->value)
        // creator_id almacena users.id (esquema de actores).
        ->and($ticket->creator_id)->toBe($this->user->id);
});

it('defaults priority to Medium in SubmitTicketAction', function () {
    $action = app(SubmitTicketAction::class);

    $ticket = $action->execute(
        employee: $this->employee,
        subject: 'Consulta general',
        description: 'Quisiera saber cómo solicitar vacaciones.',
        categoryId: $this->category->id,
    );

    expect($ticket->priority)->toBe(TicketPriority::Medium->value);
});

it('assigns a ticket via AssignTicketAction', function () {
    $ticket = HelpdeskTicket::factory()->create(['status' => TicketStatus::New->value]);
    $action = app(AssignTicketAction::class);

    $action->execute($ticket, $this->supportEmployee);

    $ticket->refresh();

    expect($ticket->assigned_agent_id)->toBe($this->supportUser->id)
        ->and($ticket->status)->toBe(TicketStatus::Open->value);
});

it('keeps current status when assigning an already-open ticket', function () {
    $ticket = HelpdeskTicket::factory()->create([
        'status' => TicketStatus::InProgress->value,
        'assigned_agent_id' => $this->supportUser->id,
    ]);
    $anotherAgent = Employee::factory()->create();
    $anotherAgentUser = User::factory()->create();
    $anotherAgent->user()->associate($anotherAgentUser)->save();
    $action = app(AssignTicketAction::class);

    $action->execute($ticket, $anotherAgent);

    $ticket->refresh();

    expect($ticket->assigned_agent_id)->toBe($anotherAgentUser->id)
        ->and($ticket->status)->toBe(TicketStatus::InProgress->value);
});

it('changes ticket status via ChangeTicketStatusAction', function () {
    $ticket = HelpdeskTicket::factory()->create(['status' => TicketStatus::New->value]);
    $action = app(ChangeTicketStatusAction::class);

    $action->execute($ticket, TicketStatus::InProgress);

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::InProgress->value)
        ->and($ticket->resolved_at)->toBeNull()
        ->and($ticket->closed_at)->toBeNull();
});

it('sets resolved_at when changing to Resolved status', function () {
    $ticket = HelpdeskTicket::factory()->create(['status' => TicketStatus::InProgress->value]);
    $action = app(ChangeTicketStatusAction::class);

    $action->execute($ticket, TicketStatus::Resolved);

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::Resolved->value)
        ->and($ticket->resolved_at)->not->toBeNull();
});

it('sets closed_at when changing to Closed status', function () {
    $ticket = HelpdeskTicket::factory()->create(['status' => TicketStatus::Resolved->value]);
    $action = app(ChangeTicketStatusAction::class);

    $action->execute($ticket, TicketStatus::Closed);

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::Closed->value)
        ->and($ticket->closed_at)->not->toBeNull();
});

it('adds a comment via AddCommentAction', function () {
    $ticket = HelpdeskTicket::factory()->create();
    $action = app(AddCommentAction::class);

    $comment = $action->execute(
        ticket: $ticket,
        author: $this->employee,
        content: 'Gracias por la ayuda.',
    );

    expect($comment)->toBeInstanceOf(HelpdeskTicketComment::class)
        ->and($comment->content)->toBe('Gracias por la ayuda.')
        ->and($comment->is_internal)->toBeFalse();
});

it('auto-assigns and progresses ticket when support adds a comment', function () {
    $ticket = HelpdeskTicket::factory()->create([
        'status' => TicketStatus::New->value,
        'assigned_agent_id' => null,
    ]);
    $action = app(AddCommentAction::class);

    $action->execute(
        ticket: $ticket,
        author: $this->supportEmployee,
        content: 'Vamos a revisar su caso.',
        isSupport: true,
    );

    $ticket->refresh();

    expect($ticket->assigned_agent_id)->toBe($this->supportUser->id)
        ->and($ticket->status)->toBe(TicketStatus::InProgress->value);
});

it('does not progress ticket when support adds an internal note', function () {
    $ticket = HelpdeskTicket::factory()->create([
        'status' => TicketStatus::Open->value,
        'assigned_agent_id' => $this->supportUser->id,
    ]);
    $action = app(AddCommentAction::class);

    $action->execute(
        ticket: $ticket,
        author: $this->supportEmployee,
        content: 'Nota interna para el equipo.',
        isSupport: true,
        isInternal: true,
    );

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::Open->value);
});
