<?php

declare(strict_types=1);

use App\Modules\PersonnelModule\Actions\AssignEmployeeToTeamAction;
use App\Modules\PersonnelModule\DTOs\AssignEmployeeToTeamDTO;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\PersonnelModule\Models\TeamMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('assigns an employee to a team and updates employee denormalized fields', function () {
    $employee = Employee::factory()->create(['team_id' => null, 'parent_id' => null]);
    $team = Team::factory()->create();

    $action = app(AssignEmployeeToTeamAction::class);
    $dto = new AssignEmployeeToTeamDTO(
        team_id: $team->id,
        employee_id: $employee->id,
        joined_at: now()->format('Y-m-d')
    );

    $teamMember = $action->execute($dto);

    expect($teamMember)->toBeInstanceOf(TeamMember::class)
        ->and($teamMember->team_id)->toBe($team->id)
        ->and($teamMember->employee_id)->toBe($employee->id)
        ->and($teamMember->is_active)->toBeTrue();

    $employee->refresh();
    expect($employee->team_id)->toBe($team->id)
        ->and($employee->parent_id)->toBe($team->supervisor_id);
});

it('deactivates any previous active memberships for the employee', function () {
    $employee = Employee::factory()->create();
    $oldTeam = Team::factory()->create();
    $newTeam = Team::factory()->create();

    // Create an old active membership
    TeamMember::create([
        'employee_id' => $employee->id,
        'team_id' => $oldTeam->id,
        'is_active' => true,
        'joined_at' => now()->subDays(5)->format('Y-m-d'),
    ]);

    $action = app(AssignEmployeeToTeamAction::class);
    $dto = new AssignEmployeeToTeamDTO(
        team_id: $newTeam->id,
        employee_id: $employee->id,
        joined_at: now()->format('Y-m-d')
    );

    $action->execute($dto);

    $activeMemberships = TeamMember::where('employee_id', $employee->id)
        ->where('is_active', true)
        ->get();

    expect($activeMemberships)->toHaveCount(1)
        ->and($activeMemberships->first()->team_id)->toBe($newTeam->id);

    $oldMembership = TeamMember::where('employee_id', $employee->id)
        ->where('team_id', $oldTeam->id)
        ->first();

    expect($oldMembership->is_active)->toBeFalse()
        ->and($oldMembership->left_at->format('Y-m-d'))->toBe(now()->format('Y-m-d'));
});

it('deactivates previous active membership in the SAME team before re-assigning with new date', function () {
    $employee = Employee::factory()->create();
    $team = Team::factory()->create();

    // Create an old active membership in the SAME team
    TeamMember::create([
        'employee_id' => $employee->id,
        'team_id' => $team->id,
        'is_active' => true,
        'joined_at' => now()->subDays(5)->format('Y-m-d'),
    ]);

    $action = app(AssignEmployeeToTeamAction::class);
    $dto = new AssignEmployeeToTeamDTO(
        team_id: $team->id,
        employee_id: $employee->id,
        joined_at: now()->format('Y-m-d')
    );

    $action->execute($dto);

    $memberships = TeamMember::where('employee_id', $employee->id)
        ->where('team_id', $team->id)
        ->orderBy('joined_at')
        ->get();

    expect($memberships)->toHaveCount(2)
        ->and($memberships->first()->is_active)->toBeFalse()
        ->and($memberships->last()->is_active)->toBeTrue()
        ->and($memberships->last()->joined_at->format('Y-m-d'))->toBe(now()->format('Y-m-d'));
});
