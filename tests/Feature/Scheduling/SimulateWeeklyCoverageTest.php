<?php

declare(strict_types=1);

use App\Modules\OrganizationModule\Models\Team;
use App\Modules\WfmModule\Actions\SimulateWeeklyCoverageAction;
use App\Modules\WfmModule\Models\CoverageRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('simulates weekly coverage and reports deficits', function () {
    $team = Team::firstOrCreate(['name' => 'Equipo Sim']);
    $monday = date('Y-m-d', strtotime('monday this week'));

    CoverageRequirement::create([
        'team_id' => $team->id,
        'date' => $monday,
        'hour' => 9,
        'required_agents' => 4,
    ]);

    CoverageRequirement::create([
        'team_id' => $team->id,
        'date' => date('Y-m-d', strtotime($monday.' +1 day')),
        'hour' => 10,
        'required_agents' => 2,
    ]);

    $action = app(SimulateWeeklyCoverageAction::class);
    $result = $action->execute($monday, $team->id);

    expect($result)->toBeArray();
    expect(count($result))->toBe(2);
    expect($result[0]['deficit'])->toBe(4);
    expect($result[1]['deficit'])->toBe(2);
});
