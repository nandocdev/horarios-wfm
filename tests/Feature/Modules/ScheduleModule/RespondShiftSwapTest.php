<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\EmployeesModule\Models\Employee;
use App\Modules\WfmModule\Events\ShiftSwapResponded;
use App\Modules\WfmModule\Livewire\RespondShiftSwap;
use App\Modules\WfmModule\Models\ShiftSwap;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('recipient can accept a shift swap', function () {
    Event::fake();

    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'operator']);
    Permission::firstOrCreate(['name' => 'requests.create']);
    $role->givePermissionTo('requests.create');
    $user->assignRole('operator');

    $recipient = Employee::factory()->create(['user_id' => $user->id]);
    $origin = Employee::factory()->create();

    $swap = ShiftSwap::create([
        'employee_id_from' => $origin->id,
        'employee_id_to' => $recipient->id,
        'swap_date' => now()->addDays(4)->toDateString(),
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(RespondShiftSwap::class, ['swapId' => $swap->id])
        ->call('accept')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('shift_swaps', ['id' => $swap->id, 'status' => 'approved']);

    Event::assertDispatched(ShiftSwapResponded::class);
});

test('recipient can reject a shift swap', function () {
    Event::fake();

    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'operator']);
    Permission::firstOrCreate(['name' => 'requests.create']);
    $role->givePermissionTo('requests.create');
    $user->assignRole('operator');

    $recipient = Employee::factory()->create(['user_id' => $user->id]);
    $origin = Employee::factory()->create();

    $swap = ShiftSwap::create([
        'employee_id_from' => $origin->id,
        'employee_id_to' => $recipient->id,
        'swap_date' => now()->addDays(5)->toDateString(),
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(RespondShiftSwap::class, ['swapId' => $swap->id])
        ->call('reject')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('shift_swaps', ['id' => $swap->id, 'status' => 'rejected']);

    Event::assertDispatched(ShiftSwapResponded::class);
});
