<?php

declare(strict_types=1);

use App\Modules\ConnectModule\Actions\SyncFinesseAgentStatesAction;
use App\Modules\PersonnelModule\Actions\SyncEmployeeDataWithCiscoAction;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->travelTo(Carbon::create(2026, 8, 3, 10));
});

it('returns failure when a one-shot state synchronization fails', function () {
    $this->mock(SyncEmployeeDataWithCiscoAction::class)
        ->shouldReceive('execute')
        ->once()
        ->andReturn([
            'total_cisco_users' => 1,
            'updated_employees' => 0,
            'team_mismatches' => 0,
        ]);

    $this->mock(SyncFinesseAgentStatesAction::class)
        ->shouldReceive('execute')
        ->once()
        ->andThrow(new RuntimeException('Finesse no disponible'));

    $this->artisan('cisco:sync')
        ->expectsOutputToContain('Error en el ciclo de sincronización: Finesse no disponible')
        ->assertFailed();
});

it('completes a one-shot synchronization successfully', function () {
    $this->mock(SyncEmployeeDataWithCiscoAction::class)
        ->shouldReceive('execute')
        ->once()
        ->andReturn([
            'total_cisco_users' => 1,
            'updated_employees' => 1,
            'team_mismatches' => 0,
        ]);

    $this->mock(SyncFinesseAgentStatesAction::class)
        ->shouldReceive('execute')
        ->once()
        ->andReturn(['success' => 1, 'error' => 0]);

    $this->artisan('cisco:sync --isolated=1')
        ->expectsOutputToContain('Resumen: 1 éxitos, 0 fallos.')
        ->assertSuccessful();
});

it('does not return an empty response for Cisco HTTP failures', function () {
    config()->set('services.uccx.url_base', 'https://uccx.test');
    config()->set('services.uccx.username', 'user');
    config()->set('services.uccx.password', 'secret');
    config()->set('services.uccx.max_retries', 0);
    Cache::forget('cisco_circuit_breaker_failures');
    Cache::forget('cisco_circuit_breaker_last_failure');

    Http::preventStrayRequests();
    Http::fake([
        'https://uccx.test/*' => Http::response('<Error>Unavailable</Error>', 503),
    ]);

    expect(fn () => (new CiscoFinesseClient)->get('Users'))
        ->toThrow(Exception::class, 'Cisco Finesse HTTP 503 en Users');
});
