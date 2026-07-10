<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\WfmModule\Actions;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Actions\ApproveShiftSwapAction;
use App\Modules\WfmModule\Actions\RejectShiftSwapAction;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Shared\Events\ShiftSwapApproved;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->approver = Employee::factory()->create();
    Event::fake([ShiftSwapApproved::class]);
});

it('approves an accepted swap request via ApproveShiftSwapAction', function () {
    $swap = ShiftSwapRequest::factory()->create(['status' => 'accepted']);
    $action = app(ApproveShiftSwapAction::class);

    $result = $action->execute(
        requestId: $swap->id,
        approverEmployeeId: $this->approver->id,
    );

    $result->refresh();

    expect($result->status)->toBe('approved')
        ->and($result->approvals()->count())->toBe(1)
        ->and($result->approvals()->first()->status)->toBe('approved');
});

it('rejects an accepted swap request via RejectShiftSwapAction', function () {
    $swap = ShiftSwapRequest::factory()->create(['status' => 'accepted']);
    $action = app(RejectShiftSwapAction::class);

    $result = $action->execute(
        requestId: $swap->id,
        approverEmployeeId: $this->approver->id,
        reason: 'No cumple con los requisitos',
    );

    $result->refresh();

    expect($result->status)->toBe('rejected')
        ->and($result->approvals()->count())->toBe(1);
});

it('throws RuntimeException when approving a non-accepted swap', function () {
    $swap = ShiftSwapRequest::factory()->create(['status' => 'pending']);
    $action = app(ApproveShiftSwapAction::class);

    $this->expectException(\RuntimeException::class);

    $action->execute(
        requestId: $swap->id,
        approverEmployeeId: $this->approver->id,
    );
});

it('throws ModelNotFoundException when approving non-existent swap', function () {
    $action = app(ApproveShiftSwapAction::class);

    $this->expectException(ModelNotFoundException::class);

    $action->execute(requestId: 99999, approverEmployeeId: 1);
});
