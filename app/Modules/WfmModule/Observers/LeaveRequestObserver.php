<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Observers;

use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WorkflowsModule\Models\LeaveRequest;
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
                'remarks' => __('Sincronizado desde Solicitud #') . $leaveRequest->id . ': ' . $leaveRequest->reason,
                'created_by' => auth()->id() ?? $leaveRequest->employee_id, 
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
        return match (strtolower($type)) {
            'vacation', 'vacaciones' => 12,
            'leave', 'licencia' => 9,
            'compensatory', 'tiempo compensatorio' => 14,
            'permiso', 'personal' => 10,
            'duelo' => 11,
            'enfermedad', 'sick' => 5,
            default => 1, // Ausencia injustificada como fallback
        };
    }
}
