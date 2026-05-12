<?php

declare(strict_types=1);

use App\Modules\EmployeesModule\Models\Employee;
use App\Modules\OrganizationModule\Models\Team;
use App\Modules\WfmModule\Actions\FetchAttendanceAction;
use App\Modules\WfmModule\DTOs\AttendanceFiltersDTO;
use App\Modules\WfmModule\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('filters attendance by team and date range', function () {
    $teamA = Team::create(['name' => 'Team A']);
    $teamB = Team::create(['name' => 'Team B']);

    $emp1 = Employee::create(['employee_number' => 'E1', 'username' => 'u1', 'first_name' => 'Alice', 'last_name' => 'One', 'email' => 'a1@example.com', 'team_id' => $teamA->id]);
    $emp2 = Employee::create(['employee_number' => 'E2', 'username' => 'u2', 'first_name' => 'Bob', 'last_name' => 'Two', 'email' => 'b2@example.com', 'team_id' => $teamB->id]);

    Attendance::create(['employee_id' => $emp1->id, 'check_in' => '2026-04-01 08:00:00']);
    Attendance::create(['employee_id' => $emp2->id, 'check_in' => '2026-04-02 09:00:00']);

    $action = new FetchAttendanceAction;

    $dto = new AttendanceFiltersDTO(employeeId: null, teamId: $teamA->id, startDate: '2026-04-01', endDate: '2026-04-01');

    $results = $action->execute($dto);

    expect($results->total())->toBe(1);
    $first = $results->items()[0];
    expect($first->employee->id)->toBe($emp1->id);
});
