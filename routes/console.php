<?php

declare(strict_types=1);

use App\Jobs\CiscoSync;
use App\Modules\AnalyticsModule\Actions\CalculateDailyKpisAction;
use App\Modules\AnalyticsModule\Jobs\RefreshDataMartJob;
use App\Modules\OperationsModule\Jobs\AggregateIntervalMetricsJob;
use App\Modules\QualityModule\Jobs\RecalculateQueueStats;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('communications:publish-scheduled')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('communications:auto-archive')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('communications:send-expired-poll-reminders')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('communications:send-newsletter')
    ->dailyAt('08:00')
    ->withoutOverlapping();

// Schedule: compile daily snapshots for schedules (run early morning)
Schedule::command('schedules:compile-daily-snapshots')
    ->dailyAt('02:00')
    ->withoutOverlapping();
// UCCX Data Ingestion (Hourly sweep)
Schedule::command('uccx:auto-import')
    ->hourly()
    ->withoutOverlapping();

// Reconciliar asistencia (Tardanzas y Ausencias)
Schedule::command('operations:reconcile-attendance')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// Quality Module: Recalcular estadísticas de colas
Schedule::job(new RecalculateQueueStats)
    ->daily()
    ->withoutOverlapping();

// WFM: Limpiar asignaciones temporales expiradas (swap de turnos)
Schedule::command('wfm:clean-temporal-assignments')
    ->dailyAt('04:00')
    ->withoutOverlapping();

// Operaciones: Agregar métricas de intervalo cada 15 minutos
Schedule::job(new AggregateIntervalMetricsJob)
    ->everyFifteenMinutes()
    ->withoutOverlapping();

// Operaciones: Agregar métricas diarias (ETL)
Schedule::command('operations:calculate-daily-metrics')
    ->dailyAt('01:00')
    ->withoutOverlapping();

// Data Mart: Refrescar tablas de hechos y dimensiones cada hora
Schedule::job(new RefreshDataMartJob)
    ->hourly()
    ->withoutOverlapping();

// Analytics: Consolidar KPIs diarios (corre después del Data Mart)
Schedule::call(function () {
    app(CalculateDailyKpisAction::class)
        ->execute(CarbonImmutable::yesterday());
})->name('daily-kpis-calculation')
    ->dailyAt('03:30')
    ->withoutOverlapping();

// Alerts operativas: evaluar reglas cada minuto
Schedule::command('alerts:evaluate')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Disparar el primer eslabón de la cadena de sincronización a las 05:00 AM
Schedule::job(new CiscoSync(true))->dailyAt('05:00');

// Ejecutar el ETL cada 5 minutos
Schedule::command('cuic:sync')->everyFiveMinutes()->withoutOverlapping();

// WFM: Calcular reportes diarios de operadores (después de la sync de telemetría)
Schedule::command('wfm:calculate-daily-reports')
    ->dailyAt('06:00')
    ->withoutOverlapping();