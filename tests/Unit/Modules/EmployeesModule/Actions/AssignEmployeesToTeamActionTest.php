<?php

declare(strict_types=1);

use App\Modules\PersonnelModule\Actions\AssignEmployeesToTeamAction;
use App\Modules\PersonnelModule\DTOs\AssignEmployeesToTeamDTO;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\PersonnelModule\Models\TeamMember;

it('assigns employees to a team correctly', function () {
    // Arrange
    $team = Team::create(['name' => 'Test Team', 'description' => 'Test']);
    $employees = collect([
        Employee::create(['employee_number' => 'EMP001', 'username' => 'johndoe', 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']),
        Employee::create(['employee_number' => 'EMP002', 'username' => 'janesmith', 'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com']),
    ]);

    $dto = new AssignEmployeesToTeamDTO(
        teamId: $team->id,
        employeeIds: $employees->pluck('id')->toArray()
    );

    $action = new AssignEmployeesToTeamAction;

    // Act
    $action->assign($dto);

    // Assert
    foreach ($employees as $employee) {
        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'employee_id' => $employee->id,
            'is_active' => true,
        ]);
    }
});

it('unassigns employees from a team correctly', function () {
    // Arrange
    $team = Team::create(['name' => 'Test Team 2', 'description' => 'Test']);
    $employees = collect([
        Employee::create(['employee_number' => 'EMP003', 'username' => 'bobwilson', 'first_name' => 'Bob', 'last_name' => 'Wilson', 'email' => 'bob@example.com']),
        Employee::create(['employee_number' => 'EMP004', 'username' => 'alicebrown', 'first_name' => 'Alice', 'last_name' => 'Brown', 'email' => 'alice@example.com']),
    ]);

    // First assign
    TeamMember::create([
        'team_id' => $team->id,
        'employee_id' => $employees[0]->id,
        'joined_at' => now(),
        'is_active' => true,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'employee_id' => $employees[1]->id,
        'joined_at' => now(),
        'is_active' => true,
    ]);

    $dto = new AssignEmployeesToTeamDTO(
        teamId: $team->id,
        employeeIds: [$employees[0]->id]
    );

    $action = new AssignEmployeesToTeamAction;

    // Act
    $action->unassign($dto);

    // Assert
    $this->assertDatabaseHas('team_members', [
        'team_id' => $team->id,
        'employee_id' => $employees[0]->id,
        'is_active' => false,
    ]);

    $this->assertDatabaseHas('team_members', [
        'team_id' => $team->id,
        'employee_id' => $employees[1]->id,
        'is_active' => true,
    ]);
});
