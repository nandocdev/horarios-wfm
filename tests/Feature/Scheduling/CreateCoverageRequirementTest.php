<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Actions\CreateCoverageRequirementAction;
use App\Modules\WfmModule\DTOs\CoverageRequirementDTO;
use App\Modules\WfmModule\Livewire\CreateCoverageRequirement;
use App\Modules\WfmModule\Models\CoverageRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('creates coverage requirement through action and prevents duplicates', function () {
    $team = Team::firstOrCreate(['name' => 'Equipo A']);

    $dto = new CoverageRequirementDTO(
        team_id: $team->id,
        date: date('Y-m-d'),
        hour: 9,
        required_agents: 3,
    );

    $action = app(CreateCoverageRequirementAction::class);
    $req = $action->execute($dto);

    expect($req)->toBeInstanceOf(CoverageRequirement::class);
    expect(DB::table('coverage_requirements')->where('team_id', $team->id)->count())->toBe(1);

    $this->expectException(InvalidArgumentException::class);
    $action->execute($dto);
});

it('allows wfm role creating coverage requirement via livewire', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(
        ['name' => 'wfm', 'guard_name' => 'web'],
        ['code' => 'WFM', 'hierarchy_level' => 5]
    );
    $user->assignRole($role);
    Permission::firstOrCreate(['name' => 'coverage_requirements.create', 'guard_name' => 'web']);
    $user->givePermissionTo('coverage_requirements.create');

    $team = Team::firstOrCreate(['name' => 'Equipo B']);

    Livewire::actingAs($user)
        ->test(CreateCoverageRequirement::class)
        ->set('form.team_id', $team->id)
        ->set('form.date', date('Y-m-d'))
        ->set('form.hour', 14)
        ->set('form.required_agents', 2)
        ->call('save');

    $this->assertDatabaseHas('coverage_requirements', [
        'team_id' => $team->id,
        'hour' => 14,
    ]);
});
