<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\ScheduleSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('allows users with analytics.export to download CSV', function () {
    // Crear employee y snapshots
    $emp = Employee::create(['employee_number' => 'EX1', 'username' => 'ux', 'first_name' => 'Export', 'last_name' => 'User', 'email' => 'export@example.com']);

    ScheduleSnapshot::create([
        'snapshot_date' => '2026-04-01',
        'employee_id' => $emp->id,
        'scheduled_minutes' => 480,
        'actual_minutes' => 450,
        'adherence_pct' => 93.75,
        'primary_status' => 'present',
    ]);

    $user = User::factory()->create();
    // Asegurar permiso existe y asignarlo
    Permission::firstOrCreate(['name' => 'analytics.export', 'guard_name' => 'web']);
    $user->givePermissionTo('analytics.export');

    $response = $this->actingAs($user)->get(route('schedules.analytics.export', ['start_date' => '2026-04-01', 'end_date' => '2026-04-01']));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type');
    $disposition = $response->headers->get('content-disposition');
    expect($disposition)->toContain('attachment');
});
