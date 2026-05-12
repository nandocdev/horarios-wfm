<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\EmployeesModule\Models\Employee;
use App\Modules\WfmModule\Livewire\CreateLeaveRequest;
use App\Modules\WfmModule\Models\LeaveRequest;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('operator can create a partial leave request', function () {
    $user = User::factory()->create();
    $user->assignRole('operator');

    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $start = Carbon::now()->setTime(10, 0)->toDateTimeString();
    $end = Carbon::now()->setTime(12, 0)->toDateTimeString();

    Livewire::test(CreateLeaveRequest::class)
        ->set('form.starts_at', $start)
        ->set('form.ends_at', $end)
        ->set('form.type', 'partial')
        ->set('form.reason', '')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('leave_requests', [
        'employee_id' => $employee->id,
        'type' => 'partial',
        'status' => 'pending',
    ]);
});

test('partial leave cannot be created if overlapping existing leave exists', function () {
    $user = User::factory()->create();
    $user->assignRole('operator');

    $employee = Employee::factory()->create(['user_id' => $user->id]);

    // existing leave 09:00 - 13:00
    $existingStart = Carbon::now()->setTime(9, 0)->toDateTimeString();
    $existingEnd = Carbon::now()->setTime(13, 0)->toDateTimeString();

    LeaveRequest::create([
        'employee_id' => $employee->id,
        'status' => 'approved',
        'time_range' => sprintf("['%s','%s')", $existingStart, $existingEnd),
        'type' => 'full',
        'reason' => 'Existing',
    ]);

    $this->actingAs($user);

    // try create partial that overlaps 11:00 - 12:00
    $start = Carbon::now()->setTime(11, 0)->toDateTimeString();
    $end = Carbon::now()->setTime(12, 0)->toDateTimeString();

    Livewire::test(CreateLeaveRequest::class)
        ->set('form.starts_at', $start)
        ->set('form.ends_at', $end)
        ->set('form.type', 'partial')
        ->set('form.reason', '')
        ->call('submit')
        ->assertHasErrors(['form.starts_at']);
});
