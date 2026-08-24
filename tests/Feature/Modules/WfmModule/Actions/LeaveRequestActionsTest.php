<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\WfmModule\Actions;

use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\Actions\ApproveLeaveRequestAction;
use App\Modules\WfmModule\Actions\RejectLeaveRequestAction;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Shared\Events\LeaveRequestDecision;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    // Códigos con los short_codes que resuelve LeaveRequestObserver (sin ids fijos).
    AbsenceReasonCode::insert([
        ['name' => 'VACACIONES', 'short_code' => 'V.', 'color' => '#3b82f6', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'AUSENCIA INJUSTIFICADA', 'short_code' => 'A.I.', 'color' => '#ef4444', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->approver = User::factory()->create();
    $this->leave = LeaveRequest::factory()->create(['status' => 'pending']);

    Event::fake([LeaveRequestDecision::class]);
});

it('approves a pending leave request via ApproveLeaveRequestAction', function () {
    $action = app(ApproveLeaveRequestAction::class);

    $result = $action->execute(
        leaveId: $this->leave->id,
        approverId: $this->approver->id,
        userId: 1,
        comment: 'Aprobado',
    );

    $result->refresh();

    expect($result->status)->toBe('approved')
        ->and($result->approvals()->count())->toBe(1)
        ->and($result->approvals()->first()->status)->toBe('approved');
});

it('rejects a pending leave request via RejectLeaveRequestAction', function () {
    $action = app(RejectLeaveRequestAction::class);

    $result = $action->execute(
        leaveId: $this->leave->id,
        approverId: $this->approver->id,
        userId: 1,
        comment: 'Rechazado',
    );

    $result->refresh();

    expect($result->status)->toBe('rejected')
        ->and($result->approvals()->count())->toBe(1)
        ->and($result->approvals()->first()->status)->toBe('rejected');
});

it('throws ModelNotFoundException when approving a non-pending leave', function () {
    $approvedLeave = LeaveRequest::factory()->approved()->create();

    Event::fake([LeaveRequestDecision::class]);
    $action = app(ApproveLeaveRequestAction::class);

    $action->execute(
        leaveId: $approvedLeave->id,
        approverId: $this->approver->id,
        userId: 1,
    );
})->throws(ModelNotFoundException::class);

it('throws ModelNotFoundException when approving a non-existent leave', function () {
    $action = app(ApproveLeaveRequestAction::class);

    $action->execute(
        leaveId: 99999,
        approverId: $this->approver->id,
        userId: 1,
    );
})->throws(ModelNotFoundException::class);
