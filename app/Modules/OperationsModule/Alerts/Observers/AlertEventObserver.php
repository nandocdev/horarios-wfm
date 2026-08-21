<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Alerts\Observers;

use App\Modules\OperationsModule\Alerts\Models\AlertEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Observa el ciclo de vida de AlertEvent.
 * Efectos secundarios: registro en log, notificaciones, broadcast via websockets.
 *
 * @module OperationsModule
 *
 * @author GitHub Copilot
 *
 * @created 2026-08-21
 */
class AlertEventObserver
{
    public function created(AlertEvent $alertEvent): void
    {
        Log::info('Alerta activada', [
            'alert_event_id' => $alertEvent->id,
            'alert_rule_id' => $alertEvent->alert_rule_id,
            'employee_id' => $alertEvent->employee_id,
            'queue_id' => $alertEvent->queue_id,
            'level' => $alertEvent->level,
            'source' => $alertEvent->source,
        ]);

        // Broadcasting para notificaciones en tiempo real
        // Dispatch job para procesamiento adicional
        \App\Modules\OperationsModule\Jobs\ProcessAlertEventJob::dispatch($alertEvent);
    }

    public function updated(AlertEvent $alertEvent): void
    {
        if ($alertEvent->isDirty('is_acknowledged')) {
            Log::info('Alerta reconocida', [
                'alert_event_id' => $alertEvent->id,
                'acknowledged_by' => $alertEvent->acknowledged_by,
                'acknowledged_at' => $alertEvent->acknowledged_at,
            ]);
        }

        if ($alertEvent->isDirty('resolved_at')) {
            Log::info('Alerta resuelta', [
                'alert_event_id' => $alertEvent->id,
                'resolved_at' => $alertEvent->resolved_at,
            ]);
        }
    }

    public function deleted(AlertEvent $alertEvent): void
    {
        Log::info('Alerta eliminada', [
            'alert_event_id' => $alertEvent->id,
        ]);
    }

    public function restored(AlertEvent $alertEvent): void
    {
        Log::info('Alerta restaurada', [
            'alert_event_id' => $alertEvent->id,
        ]);
    }

    public function forceDeleted(AlertEvent $alertEvent): void
    {
        Log::info('Alerta eliminada permanentemente', [
            'alert_event_id' => $alertEvent->id,
        ]);
    }
}