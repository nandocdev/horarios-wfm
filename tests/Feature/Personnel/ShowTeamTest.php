<?php

declare(strict_types=1);

namespace Tests\Feature\Personnel;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Livewire\ShowTeam;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\PersonnelModule\Models\TeamMember;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'teams.viewAny', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'teams.update', 'guard_name' => 'web']);
    
    $user = User::factory()->create();
    $user->givePermissionTo(['teams.viewAny', 'teams.update']);
    
    $this->actingAs($user);
});

it('can add a member to the team', function () {
    $team = Team::factory()->create();
    $employee = Employee::factory()->create(['team_id' => null]);

    Livewire::test(ShowTeam::class, ['team' => $team])
        ->call('loadAvailableEmployees')
        ->set('selectedEmployeeId', $employee->id)
        ->call('addMember')
        ->assertHasNoErrors();

    expect(TeamMember::where('team_id', $team->id)
        ->where('employee_id', $employee->id)
        ->where('is_active', true)
        ->exists())->toBeTrue();
        
    expect($employee->fresh()->team_id)->toBe($team->id);
});

it('can remove a member from the team', function () {
    $team = Team::factory()->create();
    $employee = Employee::factory()->create(['team_id' => $team->id]);
    
    TeamMember::create([
        'team_id' => $team->id,
        'employee_id' => $employee->id,
        'joined_at' => now(),
        'is_active' => true,
    ]);

    Livewire::test(ShowTeam::class, ['team' => $team])
        ->call('removeMember', $employee->id)
        ->assertHasNoErrors();

    expect(TeamMember::where('team_id', $team->id)
        ->where('employee_id', $employee->id)
        ->where('is_active', true)
        ->exists())->toBeFalse();
        
    expect($employee->fresh()->team_id)->toBeNull();
});
