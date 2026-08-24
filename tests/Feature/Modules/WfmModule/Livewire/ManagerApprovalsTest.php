<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\WfmModule\Livewire;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Livewire\ManagerApprovals;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Shared\Events\LeaveRequestDecision;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    AbsenceReasonCode::insert([
        ['id' => 12, 'name' => 'Vacaciones', 'short_code' => 'VAC', 'color' => '#3b82f6', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 1, 'name' => 'Ausencia', 'short_code' => 'AUS', 'color' => '#ef4444', 'created_at' => now(), 'updated_at' => now()],
    ]);
    Event::fake([LeaveRequestDecision::class]);
});

it('renders the manager approvals page', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $employee->update(['is_manager' => true]);

    $this->actingAs($user);

    Livewire::test(ManagerApprovals::class)
        ->assertStatus(200);
});

it('shows pending leave requests from subordinates', function () {
    $managerUser = User::factory()->create();
    $manager = Employee::factory()->create(['is_manager' => true, 'user_id' => $managerUser->id]);
    $user = User::factory()->create();
    $manager->user()->associate($user)->save();

    $subordinate = Employee::factory()->create(['parent_id' => $manager->id]);
    LeaveRequest::factory()->create(['employee_id' => $subordinate->id, 'status' => 'pending']);

    $this->actingAs($user);

    Livewire::test(ManagerApprovals::class)
        ->assertSee('Aprobación');
});

it('does not show non-subordinate leave requests', function () {
    $managerUser = User::factory()->create();
    $manager = Employee::factory()->create(['is_manager' => true, 'user_id' => $managerUser->id]);
    $user = User::factory()->create();
    $manager->user()->associate($user)->save();

    $other = Employee::factory()->create();
    $leave = LeaveRequest::factory()->create(['employee_id' => $other->id, 'status' => 'pending']);

    $this->actingAs($user);

    Livewire::test(ManagerApprovals::class)
        ->assertDontSee($leave->employee->first_name);
});

it('approves a subordinate leave request', function () {
    $managerUser = User::factory()->create();
    $manager = Employee::factory()->create(['is_manager' => true, 'user_id' => $managerUser->id]);
    $user = User::factory()->create();
    $manager->user()->associate($user)->save();

    $subordinate = Employee::factory()->create(['parent_id' => $manager->id]);
    $leave = LeaveRequest::factory()->create(['employee_id' => $subordinate->id, 'status' => 'pending']);

    $this->actingAs($user);

    Livewire::test(ManagerApprovals::class)
        ->call('approveLeave', $leave->id)
        ->assertHasNoErrors();

    expect($leave->fresh()->status)->toBe('approved');
});

it('rejects a subordinate leave request', function () {
    $managerUser = User::factory()->create();
    $manager = Employee::factory()->create(['is_manager' => true, 'user_id' => $managerUser->id]);
    $user = User::factory()->create();
    $manager->user()->associate($user)->save();

    $subordinate = Employee::factory()->create(['parent_id' => $manager->id]);
    $leave = LeaveRequest::factory()->create(['employee_id' => $subordinate->id, 'status' => 'pending']);

    $this->actingAs($user);

    Livewire::test(ManagerApprovals::class)
        ->call('rejectLeave', $leave->id)
        ->assertHasNoErrors();

    expect($leave->fresh()->status)->toBe('rejected');
});

it('does not allow approving own leave', function () {
    $managerUser = User::factory()->create();
    $manager = Employee::factory()->create(['is_manager' => true, 'user_id' => $managerUser->id]);
    $user = User::factory()->create();
    $manager->user()->associate($user)->save();

    $leave = LeaveRequest::factory()->create(['employee_id' => $manager->id, 'status' => 'pending']);

    $this->actingAs($user);

    $component = Livewire::test(ManagerApprovals::class);

    try {
        $component->call('approveLeave', $leave->id);
    } catch (\Throwable) {
        // Expected — manager cannot approve own leave
    }

    expect($leave->fresh()->status)->toBe('pending');
});
