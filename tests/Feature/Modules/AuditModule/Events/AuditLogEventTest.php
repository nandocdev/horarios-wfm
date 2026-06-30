<?php

declare(strict_types=1);

use App\Modules\AuditModule\Listeners\AuditLeaveRequestCreatedListener;
use App\Modules\AuditModule\Listeners\AuditLeaveRequestDecisionListener;
use App\Modules\AuditModule\Listeners\AuditShiftSwapApprovedListener;
use App\Modules\AuditModule\Listeners\AuditWeeklySchedulePublishedListener;
use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WorkflowsModule\Models\LeaveRequest;
use App\Modules\WorkflowsModule\Models\ShiftSwapRequest;
use App\Shared\Events\LeaveRequestCreated;
use App\Shared\Events\LeaveRequestDecision;
use App\Shared\Events\ShiftSwapApproved;
use App\Shared\Events\WeeklySchedulePublished;

// Nota: estos tests requieren PostgreSQL 16 por ILIKE y RefreshDatabase.
// En SQLite los tests de ILIKE fallan — es esperado.

// ────────────────────────────────────────────
// Auditable Trait — Observer de Eloquent
// ────────────────────────────────────────────

it('Auditable trait registra AuditLog al crear un modelo', function () {
    $user = User::factory()->create();

    $log = AuditLog::where('entity_type', get_class($user))
        ->where('entity_id', $user->id)
        ->where('action', 'created')
        ->latest()
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->after)->not->toBeNull();
});

it('Auditable trait registra AuditLog al actualizar un modelo', function () {
    $user = User::factory()->create(['name' => 'Original']);
    $user->update(['name' => 'NuevoNombre']);

    $log = AuditLog::where('entity_type', get_class($user))
        ->where('entity_id', $user->id)
        ->where('action', 'updated')
        ->latest()
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->before)->not->toBeNull()
        ->and($log->after)->not->toBeNull();
});

it('Auditable trait registra AuditLog al eliminar un modelo', function () {
    $user = User::factory()->create();
    $userId = $user->id;
    $user->delete();

    $log = AuditLog::where('entity_type', get_class($user))
        ->where('entity_id', $userId)
        ->where('action', 'deleted')
        ->latest()
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->before)->not->toBeNull()
        ->and($log->after)->toBeNull();
});

// ────────────────────────────────────────────
// Domain Event Listeners
// ────────────────────────────────────────────

it('AuditWeeklySchedulePublishedListener registra el log con el contenido correcto', function () {
    $user = User::factory()->create();
    $week = WeeklySchedule::create([
        'week_start_date' => now()->startOfWeek(),
        'week_end_date' => now()->endOfWeek(),
        'status' => 'draft',
    ]);

    $listener = app(AuditWeeklySchedulePublishedListener::class);
    $listener->handle(new WeeklySchedulePublished($week, $user->id));

    $log = AuditLog::where('action', 'weekly_schedule.published')->latest()->first();

    expect($log)->not->toBeNull()
        ->and($log->entity_type)->toBe(get_class($week))
        ->and($log->entity_id)->toBe($week->id)
        ->and($log->user_id)->toBe($user->id)
        ->and($log->ip_address)->toBeNull()
        ->and($log->before)->toBeNull()
        ->and($log->after)->not->toBeNull();
});

it('AuditLeaveRequestCreatedListener registra el log con requestedByUserId', function () {
    $user = User::factory()->create();
    $employee = \App\Modules\PersonnelModule\Models\Employee::factory()->create();
    $leave = LeaveRequest::create([
        'employee_id' => $employee->id,
        'type' => 'quarterly',
        'start_time' => now(),
        'end_time' => now()->addHours(4),
        'minutes' => 240,
        'status' => 'pending',
        'reason' => 'Test',
    ]);

    $listener = app(AuditLeaveRequestCreatedListener::class);
    $listener->handle(new LeaveRequestCreated($leave, $user->id));

    $log = AuditLog::where('action', 'leave_request.created')->latest()->first();

    expect($log)->not->toBeNull()
        ->and($log->entity_type)->toBe(get_class($leave))
        ->and($log->entity_id)->toBe($leave->id)
        ->and($log->user_id)->toBe($user->id)
        ->and($log->ip_address)->toBeNull();
});

it('AuditLeaveRequestDecisionListener registra leave_request.approved con decision y reason en after', function () {
    $user = User::factory()->create();
    $employee = \App\Modules\PersonnelModule\Models\Employee::factory()->create();
    $leave = LeaveRequest::create([
        'employee_id' => $employee->id,
        'type' => 'quarterly',
        'start_time' => now(),
        'end_time' => now()->addHours(4),
        'minutes' => 240,
        'status' => 'pending',
        'reason' => 'Test',
    ]);
    $reason = 'Aprobado por cumple requisitos';

    $listener = app(AuditLeaveRequestDecisionListener::class);
    $listener->handle(new LeaveRequestDecision($leave, 'approved', $user->id, $reason));

    $log = AuditLog::where('action', 'leave_request.approved')->latest()->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id)
        ->and($log->after)->toHaveKey('decision', 'approved')
        ->and($log->after)->toHaveKey('reason', $reason);
});

it('AuditLeaveRequestDecisionListener registra leave_request.rejected cuando el status es rejected', function () {
    $user = User::factory()->create();
    $employee = \App\Modules\PersonnelModule\Models\Employee::factory()->create();
    $leave = LeaveRequest::create([
        'employee_id' => $employee->id,
        'type' => 'quarterly',
        'start_time' => now(),
        'end_time' => now()->addHours(4),
        'minutes' => 240,
        'status' => 'pending',
        'reason' => 'Test',
    ]);

    $listener = app(AuditLeaveRequestDecisionListener::class);
    $listener->handle(new LeaveRequestDecision($leave, 'rejected', $user->id, 'No cumple'));

    $log = AuditLog::where('action', 'leave_request.rejected')->latest()->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id)
        ->and($log->after)->toHaveKey('decision', 'rejected');
});

it('AuditShiftSwapApprovedListener registra shift_swap.approved con approverId', function () {
    $user = User::factory()->create();
    $requester = \App\Modules\PersonnelModule\Models\Employee::factory()->create();
    $recipient = \App\Modules\PersonnelModule\Models\Employee::factory()->create();
    $swap = ShiftSwapRequest::create([
        'requester_id' => $requester->id,
        'recipient_id' => $recipient->id,
        'start_date' => now()->addDay()->toDateString(),
        'status' => 'pending',
    ]);

    $listener = app(AuditShiftSwapApprovedListener::class);
    $listener->handle(new ShiftSwapApproved($swap, $user->id));

    $log = AuditLog::where('action', 'shift_swap.approved')->latest()->first();

    expect($log)->not->toBeNull()
        ->and($log->entity_type)->toBe(get_class($swap))
        ->and($log->entity_id)->toBe($swap->id)
        ->and($log->user_id)->toBe($user->id)
        ->and($log->ip_address)->toBeNull();
});

// ────────────────────────────────────────────
// Inmutabilidad — verificacion a nivel DB
// ────────────────────────────────────────────

it('no hay restriccion DB que impida UPDATE directo en audit_logs', function () {
    $log = AuditLog::factory()->create(['action' => 'created', 'user_id' => User::factory()->create()->id]);

    DB::table('audit_logs')->where('id', $log->id)->update(['action' => 'hacked']);

    $fresh = AuditLog::find($log->id);
    expect($fresh->action)->toBe('hacked');
});

it('no hay restriccion DB que impida DELETE directo en audit_logs', function () {
    $log = AuditLog::factory()->create(['user_id' => User::factory()->create()->id]);

    DB::table('audit_logs')->where('id', $log->id)->delete();

    expect(AuditLog::find($log->id))->toBeNull();
});
