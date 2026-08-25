<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use App\Modules\WorkflowsModule\Actions\ApproveWorkflowAction;
use App\Modules\WorkflowsModule\Actions\DelegateApprovalAction;
use App\Modules\WorkflowsModule\Actions\RejectWorkflowAction;
use App\Modules\WorkflowsModule\Actions\SubmitWorkflowAction;
use App\Modules\WorkflowsModule\DTOs\WorkflowDTO;
use App\Modules\WorkflowsModule\Enums\WorkflowStatus;
use App\Modules\WorkflowsModule\Models\WorkflowDelegation;
use App\Modules\WorkflowsModule\Models\WorkflowRequest;

beforeEach(function () {
    $directorate = Directorate::create(['name' => 'Dir Test']);
    $department = Department::create(['directorate_id' => $directorate->id, 'name' => 'Dept Test']);
    $position = Position::create(['department_id' => $department->id, 'name' => 'Pos Test', 'position_code' => 'PT-001']);
    $status = EmploymentStatus::create(['name' => 'Activo', 'code' => 'ACT']);

    // requester_id/approver_id referencian users.id (esquema de actores).
    $this->requesterUser = User::factory()->create();
    $this->approver1User = User::factory()->create();
    $this->approver2User = User::factory()->create();

    $this->requester = Employee::factory()->create([
        'user_id' => $this->requesterUser->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'employment_status_id' => $status->id,
    ]);

    $this->approver1 = Employee::factory()->create([
        'user_id' => $this->approver1User->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'employment_status_id' => $status->id,
    ]);

    $this->approver2 = Employee::factory()->create([
        'user_id' => $this->approver2User->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'employment_status_id' => $status->id,
    ]);
});

test('crea un workflow con multiples pasos de aprobacion', function () {
    $dto = new WorkflowDTO(
        requestable_type: 'leave_request',
        requestable_id: 1,
        requester_id: $this->requesterUser->id,
        type: 'leave',
        reason: 'Solicitud de permiso',
    );

    $workflow = (new SubmitWorkflowAction)->execute($dto, [
        $this->approver1User->id,
        $this->approver2User->id,
    ]);

    expect($workflow)->toBeInstanceOf(WorkflowRequest::class);
    expect($workflow->status)->toBe('pending');
    expect($workflow->approvals)->toHaveCount(2);
    expect($workflow->approvals[0]->step_order)->toBe(1);
    expect($workflow->approvals[1]->step_order)->toBe(2);
});

test('aprueba un workflow paso a paso', function () {
    $dto = new WorkflowDTO(
        requestable_type: 'swap',
        requestable_id: 1,
        requester_id: $this->requesterUser->id,
        type: 'swap',
    );

    $workflow = (new SubmitWorkflowAction)->execute($dto, [
        $this->approver1User->id,
        $this->approver2User->id,
    ]);

    $workflow = (new ApproveWorkflowAction)->execute($workflow, $this->approver1User->id, 'Ok');
    expect($workflow->status)->toBe('pending');

    $workflow = (new ApproveWorkflowAction)->execute($workflow, $this->approver2User->id, 'Aprobado');
    expect($workflow->status)->toBe(WorkflowStatus::Approved->value);
});

test('rechaza un workflow inmediatamente', function () {
    $dto = new WorkflowDTO(
        requestable_type: 'exception',
        requestable_id: 1,
        requester_id: $this->requesterUser->id,
        type: 'exception',
    );

    $workflow = (new SubmitWorkflowAction)->execute($dto, [$this->approver1User->id]);

    $workflow = (new RejectWorkflowAction)->execute($workflow, $this->approver1User->id, 'No procede');

    expect($workflow->status)->toBe(WorkflowStatus::Rejected->value);
});

test('crea una delegacion de aprobacion', function () {
    $delegation = (new DelegateApprovalAction)->execute(
        $this->approver1User->id,
        $this->approver2User->id,
        '2026-01-01',
        '2026-01-31',
    );

    expect($delegation)->toBeInstanceOf(WorkflowDelegation::class);
    expect($delegation->is_active)->toBeTrue();
    expect($delegation->original_approver_id)->toBe($this->approver1User->id);
});

test('WorkflowRequest usa SoftDeletes', function () {
    $dto = new WorkflowDTO(
        requestable_type: 'test',
        requestable_id: 1,
        requester_id: $this->requesterUser->id,
        type: 'test',
    );

    $workflow = (new SubmitWorkflowAction)->execute($dto, [$this->approver1User->id]);
    $workflow->delete();

    expect(WorkflowRequest::find($workflow->id))->toBeNull();
    expect(WorkflowRequest::withTrashed()->find($workflow->id))->not->toBeNull();
});
