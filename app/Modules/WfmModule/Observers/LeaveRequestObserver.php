<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Observers;

use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\ScheduleException;
use Illuminate\Support\Facades\Log;

class LeaveRequestObserver
{
    /**
     * Handle the LeaveRequest "updated" event.
     */
    public function updated(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->isDirty('status')) {
            if ($leaveRequest->status === 'approved') {
                $this->syncToSchedule($leaveRequest);
            } elseif ($leaveRequest->status === 'rejected' || $leaveRequest->status === 'pending') {
                $this->removeFromSchedule($leaveRequest);
            }
        }
    }

    /**
     * Sincroniza el permiso aprobado con la tabla de excepciones de horario.
     */
    protected function syncToSchedule(LeaveRequest $leaveRequest): void
    {
        $reasonId = $this->mapTypeToReasonId($leaveRequest->type);

        // Sin entrada de catálogo no se puede sincronizar: evitar FK huérfana.
        if ($reasonId === 0) {
            Log::warning('[LeaveRequestObserver] Sin AbsenceReasonCode para el tipo', [
                'leave_id' => $leaveRequest->id,
                'type' => $leaveRequest->type,
            ]);

            return;
        }

        ScheduleException::updateOrCreate(
            [
                'origin_type' => LeaveRequest::class,
                'origin_id' => $leaveRequest->id,
            ],
            [
                'employee_id' => $leaveRequest->employee_id,
                'absence_reason_code_id' => $reasonId,
                'start_at' => $leaveRequest->start_time,
                'end_at' => $leaveRequest->end_time,
                'is_full_day' => $leaveRequest->minutes >= 480, // Asumimos día completo si son >= 8h
                'remarks' => __('Sincronizado desde Solicitud #').$leaveRequest->id.': '.$leaveRequest->reason,
                // created_by referencia users.id; sin sesión autenticada queda NULL
                // (el fallback anterior escribía un employees.id y violaba la FK).
                'created_by' => auth()->id(),
            ]
        );

        Log::info("LeaveRequest #{$leaveRequest->id} sincronizado con ScheduleException.");
    }

    /**
     * Elimina la excepción si el permiso deja de estar aprobado.
     */
    protected function removeFromSchedule(LeaveRequest $leaveRequest): void
    {
        ScheduleException::where('origin_type', LeaveRequest::class)
            ->where('origin_id', $leaveRequest->id)
            ->delete();
    }

    /**
     * Mapea los tipos de permiso del workflow a los códigos de motivo de WFM.
     */
    protected function mapTypeToReasonId(string $type): int
    {
        // Resolver por short_code del catálogo institucional, no por id hardcodeado
        // (los ids pueden diferir entre entornos).
        $shortCode = match (strtolower($type)) {
            'vacation', 'vacaciones' => 'V.',
            'leave', 'licencia' => 'L.',
            'compensatory', 'tiempo compensatorio' => 'T.C.',
            'permiso', 'personal' => 'P',
            'duelo' => 'D.',
            'enfermedad', 'sick' => 'C.M.',
            default => 'A.I.', // Ausencia injustificada como fallback
        };

        return (int) (AbsenceReasonCode::where('short_code', $shortCode)->value('id') ?? 0);
    }
}
