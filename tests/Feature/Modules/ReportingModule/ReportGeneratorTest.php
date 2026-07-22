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

it('renders the report generator page for authorized users', function (): void {
    $user = User::factory()->create();
    $user->assignRole('coordinator');

    $this->actingAs($user)
        ->get('/reportes')
        ->assertOk();
});

it('redirects unauthenticated users to login', function (): void {
    $this->get('/reportes')
        ->assertRedirect('/login');
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

it('switches category and resets sub-report', function (): void {
    $user = User::factory()->create();
    $user->assignRole('coordinator');

    Livewire::actingAs($user)
        ->test(ReportGenerator::class)
        ->set('category', 'attendance')
        ->assertSet('subReport', 'absenteeism')
        ->set('category', 'activities')
        ->assertSet('subReport', 'intraday')
        ->set('category', 'volume')
        ->assertSet('subReport', 'queue')
        ->set('category', 'performance')
        ->assertSet('subReport', 'agent');
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
