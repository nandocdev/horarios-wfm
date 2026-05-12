<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\EmployeesModule\Models\Employee;
use App\Modules\WfmModule\Livewire\MyDay;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\IntradayActivityAssignment;
use Livewire\Livewire;

test('my day component shows todays assignments', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $activity = IntradayActivity::create(['name' => 'Coaching', 'type' => 'coaching']);

    IntradayActivityAssignment::create([
        'intraday_activity_id' => $activity->id,
        'employee_id' => $employee->id,
        'start_at' => now()->startOfDay()->addHours(9),
        'end_at' => now()->startOfDay()->addHours(10),
    ]);

    $this->actingAs($user);

    Livewire::test(MyDay::class)
        ->assertSee('Mi Día')
        ->assertSee('Coaching');
});
