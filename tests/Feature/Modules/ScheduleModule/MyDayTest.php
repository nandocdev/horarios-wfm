<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ScheduleModule;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Livewire\MyDay;
use App\Modules\WfmModule\Models\ActivityType;
use App\Modules\WfmModule\Models\IntradayActivity;
use Livewire\Livewire;

test('my day component shows todays assignments', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id, 'first_name' => 'Test', 'last_name' => 'User']);

    $type = ActivityType::create(['name' => 'Coaching', 'is_productive' => true]);

    $start = now()->startOfDay()->addHours(9);
    $end = now()->startOfDay()->addHours(10);

    // Formato compatible con SQLite y el parser manual en IntradayActivity model
    $range = sprintf('[%s, %s)', $start->toIso8601String(), $end->toIso8601String());

    IntradayActivity::create([
        'employee_id' => $employee->id,
        'activity_type_id' => $type->id,
        'time_range' => $range,
    ]);

    $this->actingAs($user);

    Livewire::test(MyDay::class)
        ->assertSee('Mi Jornada')
        ->assertSee('Coaching');
});
