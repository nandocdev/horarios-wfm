<?php

declare(strict_types=1);

use App\Modules\QualityModule\Jobs\RecalculateQueueStats;
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
