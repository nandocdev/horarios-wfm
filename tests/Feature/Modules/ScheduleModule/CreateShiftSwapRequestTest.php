<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\EmployeesModule\Models\Employee;
use App\Modules\WfmModule\Livewire\CreateShiftSwapRequest;
use App\Modules\WfmModule\Models\ShiftSwap;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('operator can request a shift swap with another employee', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'operator']);
    Permission::firstOrCreate(['name' => 'requests.create']);
    $role->givePermissionTo('requests.create');
    $user->assignRole('operator');

    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $other = Employee::factory()->create();

    $this->actingAs($user);

    Livewire::test(CreateShiftSwapRequest::class)
        ->set('form.employee_id_to', $other->id)
        ->set('form.swap_date', Carbon::now()->addDays(2)->toDateString())
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('shift_swaps', [
        'employee_id_from' => $employee->id,
        'employee_id_to' => $other->id,
        'status' => 'pending',
    ]);
});

test('cannot request duplicate swap for same date between same employees', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'operator']);
    Permission::firstOrCreate(['name' => 'requests.create']);
    $role->givePermissionTo('requests.create');
    $user->assignRole('operator');

    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $other = Employee::factory()->create();

    $date = Carbon::now()->addDays(3)->toDateString();

    ShiftSwap::create([
        'employee_id_from' => $employee->id,
        'employee_id_to' => $other->id,
        'swap_date' => $date,
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(CreateShiftSwapRequest::class)
        ->set('form.employee_id_to', $other->id)
        ->set('form.swap_date', $date)
        ->call('submit');

    // No new record must be created (duplicate prevented at Action level)
    $this->assertDatabaseCount('shift_swaps', 1);
});
