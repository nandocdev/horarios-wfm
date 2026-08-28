<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

test('analytics:calculate-daily-kpis command runs successfully for yesterday', function () {
    $this->artisan('analytics:calculate-daily-kpis')
        ->expectsOutputToContain('Consolidación de KPIs diarios completada.')
        ->assertSuccessful();
});

test('analytics:calculate-daily-kpis command runs for a date range', function () {
    $from = now()->subDays(2)->toDateString();
    $to = now()->subDays(1)->toDateString();

    $this->artisan('analytics:calculate-daily-kpis', [
        '--from' => $from,
        '--to' => $to,
    ])
        ->expectsOutputToContain("Consolidando KPIs diarios desde {$from} hasta {$to}...")
        ->expectsOutputToContain('Consolidación de KPIs diarios completada.')
        ->assertSuccessful();
});

test('analytics:calculate-daily-kpis fails when from is after to', function () {
    $this->artisan('analytics:calculate-daily-kpis', [
        '--from' => '2026-08-10',
        '--to' => '2026-08-01',
    ])
        ->expectsOutputToContain('La fecha inicial (--from) no puede ser posterior a la fecha final (--to).')
        ->assertFailed();
});
