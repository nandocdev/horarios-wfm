<?php

declare(strict_types=1);

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Actions\AssignEmployeeToTeamAction;
use App\Modules\PersonnelModule\Actions\RemoveEmployeeFromTeamAction;
use App\Modules\PersonnelModule\DTOs\AssignEmployeeToTeamDTO;
use App\Modules\PersonnelModule\DTOs\RemoveEmployeeFromTeamDTO;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\PersonnelModule\Models\TeamMember;

test('can assign employee to team', function () {
    $employee = Employee::factory()->create();
    $team = Team::factory()->create();

    $dto = new AssignEmployeeToTeamDTO(
        employee_id: $employee->id,
        team_id: $team->id,
        joined_at: '2024-01-01',
    );

    $action = new AssignEmployeeToTeamAction;
    $result = $action->execute($dto);

    expect($result)->toBeInstanceOf(TeamMember::class);
    expect($result->employee_id)->toBe($employee->id);
    expect($result->team_id)->toBe($team->id);
    expect($result->joined_at->format('Y-m-d'))->toBe('2024-01-01');
    expect($result->is_active)->toBe(true);

    // Verify team has the employee
    $team->load('users');
    expect($team->users)->toHaveCount(1);
    expect($team->users->first()->id)->toBe($employee->id);
});

test('can remove employee from team', function () {
    $employee = Employee::factory()->create();
    $team = Team::factory()->create();

    // First assign
    $assignDto = new AssignEmployeeToTeamDTO(
        employee_id: $employee->id,
        team_id: $team->id,
        joined_at: '2024-01-01',
    );
    $assignAction = new AssignEmployeeToTeamAction;
    $assignAction->execute($assignDto);

    // Then remove
    $removeDto = new RemoveEmployeeFromTeamDTO(
        employee_id: $employee->id,
        team_id: $team->id,
        left_at: '2024-12-31',
    );

    $removeAction = new RemoveEmployeeFromTeamAction;
    $result = $removeAction->execute($removeDto);

    expect($result)->toBeInstanceOf(TeamMember::class);
    expect($result->is_active)->toBe(false);
    expect($result->left_at->format('Y-m-d'))->toBe('2024-12-31');

    // Verify team no longer has the employee
    $team->load('users');
    expect($team->users)->toHaveCount(0);
});

test('prevents assigning employee to multiple active teams', function () {
    $employee = Employee::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    // Assign to first team
    $dto1 = new AssignEmployeeToTeamDTO(
        employee_id: $employee->id,
        team_id: $team1->id,
        joined_at: '2024-01-01',
    );
    $action = new AssignEmployeeToTeamAction;
    $action->execute($dto1);

    // Try to assign to second team
    $dto2 = new AssignEmployeeToTeamDTO(
        employee_id: $employee->id,
        team_id: $team2->id,
        joined_at: '2024-01-01',
    );
    $action->execute($dto2);

    // First assignment should be deactivated
    $oldMember = TeamMember::where('employee_id', $employee->id)
        ->where('team_id', $team1->id)
        ->first();
    expect($oldMember->is_active)->toBe(false);

    // New assignment should be active
    $newMember = TeamMember::where('employee_id', $employee->id)
        ->where('team_id', $team2->id)
        ->first();
    expect($newMember->is_active)->toBe(true);
});
