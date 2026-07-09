<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\HelpdeskModule\Models;

use App\Modules\HelpdeskModule\Enums\TicketStatus;
use App\Modules\HelpdeskModule\Models\HelpdeskCategory;
use App\Modules\HelpdeskModule\Models\HelpdeskTicket;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = HelpdeskCategory::factory()->create(['sla_hours' => 48]);
    $this->employee = Employee::factory()->create();
});

it('computes sla_deadline based on category sla_hours', function () {
    $ticket = HelpdeskTicket::factory()->create([
        'category_id' => $this->category->id,
        'created_at' => now()->subHours(10),
    ]);

    $ticket->load('category');

    $expected = $ticket->created_at->addHours(48);

    expect($ticket->sla_deadline->toDateTimeString())->toBe($expected->toDateTimeString());
});

it('returns on_track status when ticket is within 75% of SLA', function () {
    $ticket = HelpdeskTicket::factory()->create([
        'category_id' => $this->category->id,
        'created_at' => now()->subHours(10),
    ]);

    $ticket->load('category');

    expect($ticket->sla_status)->toBe('on_track');
});

it('returns at_risk status when ticket has passed 75% of SLA', function () {
    $ticket = HelpdeskTicket::factory()->create([
        'category_id' => $this->category->id,
        'created_at' => now()->subHours(40),
    ]);

    $ticket->load('category');

    expect($ticket->sla_status)->toBe('at_risk');
});

it('returns breached status when ticket has exceeded SLA deadline', function () {
    $ticket = HelpdeskTicket::factory()->create([
        'category_id' => $this->category->id,
        'created_at' => now()->subHours(50),
    ]);

    $ticket->load('category');

    expect($ticket->sla_status)->toBe('breached');
});

it('returns compliant status when ticket was resolved before SLA deadline', function () {
    $ticket = HelpdeskTicket::factory()->create([
        'category_id' => $this->category->id,
        'created_at' => now()->subHours(50),
        'status' => TicketStatus::Resolved->value,
        'resolved_at' => now()->subHours(30),
    ]);

    $ticket->load('category');

    expect($ticket->sla_status)->toBe('compliant');
});

it('returns breached status when ticket was resolved after SLA deadline', function () {
    $ticket = HelpdeskTicket::factory()->create([
        'category_id' => $this->category->id,
        'created_at' => now()->subHours(50),
        'status' => TicketStatus::Resolved->value,
        'resolved_at' => now()->subHour(), // resolved 1h ago, deadline was 2h ago
    ]);

    $ticket->load('category');

    expect($ticket->sla_status)->toBe('breached');
});
