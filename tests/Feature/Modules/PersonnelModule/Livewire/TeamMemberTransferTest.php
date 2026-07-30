<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Livewire\TeamMemberTransfer;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\PersonnelModule\Models\TeamMember;
use Livewire\Livewire;

it('renders the team member transfer component', function () {
    $team = Team::factory()->create();

    Livewire::actingAs(adminUser())
        ->test(TeamMemberTransfer::class, ['team' => $team])
        ->assertStatus(200)
        ->assertSee($team->name);
});

it('moves selected employees from left to right team (> button)', function () {
    $currentTeam = Team::factory()->create(['name' => 'Team A']);
    $targetTeam = Team::factory()->create(['name' => 'Team B']);
    $employee = Employee::factory()->create(['team_id' => $currentTeam->id]);

    // Create active membership
    TeamMember::create([
        'employee_id' => $employee->id,
        'team_id' => $currentTeam->id,
        'is_active' => true,
        'joined_at' => now()->subDays(5)->format('Y-m-d'),
    ]);

    Livewire::actingAs(adminUser())
        ->test(TeamMemberTransfer::class, ['team' => $currentTeam])
        ->set('leftFilter', (string) $currentTeam->id)
        ->set('rightFilter', (string) $targetTeam->id)
        ->set('leftSelected', [$employee->id])
        ->call('moveSelectedToRight')
        ->assertHasNoErrors();

    $employee->refresh();
    expect($employee->team_id)->toBe($targetTeam->id);

    $membership = TeamMember::where('employee_id', $employee->id)
        ->where('is_active', true)
        ->first();

    expect($membership->team_id)->toBe($targetTeam->id);
});

it('moves all visible employees from left to right team (>> button)', function () {
    $currentTeam = Team::factory()->create();
    $targetTeam = Team::factory()->create();
    $employees = Employee::factory()->count(3)->create(['team_id' => $currentTeam->id]);

    foreach ($employees as $emp) {
        TeamMember::create([
            'employee_id' => $emp->id,
            'team_id' => $currentTeam->id,
            'is_active' => true,
            'joined_at' => now()->subDays(5)->format('Y-m-d'),
        ]);
    }

    Livewire::actingAs(adminUser())
        ->test(TeamMemberTransfer::class, ['team' => $currentTeam])
        ->set('leftFilter', (string) $currentTeam->id)
        ->set('rightFilter', (string) $targetTeam->id)
        ->call('moveAllToRight')
        ->assertHasNoErrors();

    foreach ($employees as $emp) {
        $emp->refresh();
        expect($emp->team_id)->toBe($targetTeam->id);
    }
});

it('removes selected employees from team when moving to "none" destination', function () {
    $currentTeam = Team::factory()->create();
    $employee = Employee::factory()->create(['team_id' => $currentTeam->id]);

    TeamMember::create([
        'employee_id' => $employee->id,
        'team_id' => $currentTeam->id,
        'is_active' => true,
        'joined_at' => now()->subDays(5)->format('Y-m-d'),
    ]);

    Livewire::actingAs(adminUser())
        ->test(TeamMemberTransfer::class, ['team' => $currentTeam])
        ->set('leftFilter', (string) $currentTeam->id)
        ->set('rightFilter', 'none')
        ->set('leftSelected', [$employee->id])
        ->call('moveSelectedToRight')
        ->assertHasNoErrors();

    $employee->refresh();
    expect($employee->team_id)->toBeNull();

    expect(TeamMember::where('employee_id', $employee->id)->where('is_active', true)->exists())->toBeFalse();
});

function adminUser()
{
    return User::factory()->create()->assignRole('admin');
}
