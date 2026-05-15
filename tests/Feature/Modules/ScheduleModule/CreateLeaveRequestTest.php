<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Livewire\CreateLeaveRequest;
use Livewire\Livewire;

test('operator can create a full day leave request', function () {
    $user = User::factory()->create();

    // assign operator role (seeder already defines it)
    $user->assignRole('operator');

    // create employee and link to user
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $component = Livewire::test(CreateLeaveRequest::class)
        ->set('form.starts_at', now()->startOfDay()->toDateTimeString())
        ->set('form.ends_at', now()->endOfDay()->toDateTimeString())
        ->set('form.reason', 'Necesito permiso por motivos personales')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('leave_requests', [
        'employee_id' => $employee->id,
        'status' => 'pending',
    ]);
});
