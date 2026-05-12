<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\EmployeesModule\Models\Employee;
use App\Modules\WfmModule\Actions\AssignIntradayActivityAction;
use App\Modules\WfmModule\DTOs\AssignIntradayActivityDTO;
use App\Modules\WfmModule\Models\IntradayActivity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('wfm can assign intraday activity to employee via Action', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'wfm']);
    Permission::firstOrCreate(['name' => 'intraday.assign']);
    $role->givePermissionTo('intraday.assign');
    $user->assignRole('wfm');

    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $activity = IntradayActivity::create(['name' => 'Coaching', 'type' => 'coaching', 'duration_minutes' => 30]);

    $this->actingAs($user);

    $dto = new AssignIntradayActivityDTO(
        $activity->id,
        $employee->id,
        now()->addHour()->toDateTimeString(),
        now()->addHours(2)->toDateTimeString(),
        'Session'
    );

    app(AssignIntradayActivityAction::class)->execute($dto);

    $this->assertDatabaseHas('intraday_activity_assignments', [
        'intraday_activity_id' => $activity->id,
        'employee_id' => $employee->id,
    ]);
});
