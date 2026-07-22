<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\ReportingModule\Livewire\ReportGenerator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('redirects unauthenticated users to login', function (): void {
    $this->get('/reportes')
        ->assertRedirect('/login');
});

it('renders each report route for authorized users', function (string $route): void {
    $user = User::factory()->create();
    $user->assignRole('coordinator');

    $this->actingAs($user)
        ->get($route)
        ->assertOk();
})->with([
    '/reportes',
    '/reportes/attendance/absenteeism',
    '/reportes/attendance/tardiness',
    '/reportes/attendance/leaves',
    '/reportes/attendance/vacations',
    '/reportes/attendance/summary',
    '/reportes/activities/intraday',
    '/reportes/activities/period',
    '/reportes/volume/queue',
    '/reportes/volume/interval',
    '/reportes/volume/summary',
    '/reportes/performance/agent',
    '/reportes/performance/team',
    '/reportes/performance/ranking',
]);

it('mounts with correct category and subReport from route', function (): void {
    $user = User::factory()->create();
    $user->assignRole('coordinator');

    Livewire::actingAs($user)
        ->test(ReportGenerator::class, ['category' => 'volume', 'subReport' => 'interval'])
        ->assertSet('category', 'volume')
        ->assertSet('subReport', 'interval');
});

it('shows validation errors when date range is missing', function (): void {
    $user = User::factory()->create();
    $user->assignRole('coordinator');

    Livewire::actingAs($user)
        ->test(ReportGenerator::class)
        ->call('generate')
        ->assertHasErrors(['form.dateFrom', 'form.dateTo']);
});

it('shows validation error when dateFrom is after dateTo', function (): void {
    $user = User::factory()->create();
    $user->assignRole('coordinator');

    Livewire::actingAs($user)
        ->test(ReportGenerator::class)
        ->set('form.dateFrom', '2026-07-20')
        ->set('form.dateTo', '2026-07-15')
        ->call('generate')
        ->assertHasErrors(['form.dateTo']);
});

it('blocks unauthorized roles from exporting', function (): void {
    $user = User::factory()->create();
    $user->assignRole('operator');

    Gate::define('reports.export', fn (): bool => false);

    Livewire::actingAs($user)
        ->test(ReportGenerator::class)
        ->set('form.dateFrom', '2026-07-01')
        ->set('form.dateTo', '2026-07-15')
        ->call('generate')
        ->assertForbidden();
});

it('allows authorized roles to access the generator', function (): void {
    $user = User::factory()->create();
    $user->assignRole('coordinator');

    Livewire::actingAs($user)
        ->test(ReportGenerator::class)
        ->assertOk();
});

it('rejects invalid report combination with exception', function (): void {
    $user = User::factory()->create();
    $user->assignRole('coordinator');

    $comp = Livewire::actingAs($user)
        ->test(ReportGenerator::class)
        ->set('form.dateFrom', '2026-07-01')
        ->set('form.dateTo', '2026-07-15');

    $comp->set('category', 'attendance');
    $comp->set('subReport', 'nonexistent');

    try {
        $comp->call('generate');
    } catch (InvalidArgumentException $e) {
        expect($e->getMessage())->toContain('attendance.nonexistent');

        return;
    }

    $this->fail('Expected InvalidArgumentException was not thrown.');
});
