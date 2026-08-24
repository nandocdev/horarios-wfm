<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\AnalyticsModule;

use App\Modules\AnalyticsModule\Jobs\RefreshDataMartJob;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // El ETL trunca y repuebla las dimensiones; aislamos el ruido de logs.
    Log::spy();
});

it('refreshes dim_team with supervisor name without lazy loading violations', function () {
    $supervisor = Employee::factory()->create([
        'first_name' => 'Laura',
        'last_name' => 'García',
    ]);

    Team::create([
        'name' => 'Soporte N2',
        'supervisor_id' => $supervisor->id,
        'is_active' => true,
    ]);

    (new RefreshDataMartJob)->handle();

    $row = DB::table('dim_team')->where('name', 'Soporte N2')->first();

    expect($row)->not->toBeNull()
        ->and($row->supervisor_id)->toBe($supervisor->id)
        ->and($row->supervisor_name)->toBe('Laura García');
});
