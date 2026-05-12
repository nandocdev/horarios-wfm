<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ScheduleRequestStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $requestType, // 'swap' o 'leave'
        protected string $status,      // 'accepted', 'approved', 'rejected', 'cancelled'
        protected array $details       // ['date' => '...', 'approver' => '...']
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $statusLabels = [
            'accepted' => 'Aceptada por Par',
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            'cancelled' => 'Cancelada',
        ];

        $typeLabels = [
            'swap' => 'Intercambio de Turno',
            'leave' => 'Solicitud de Permiso',
        ];

        $label = $statusLabels[$this->status] ?? $this->status;
        $typeLabel = $typeLabels[$this->requestType] ?? $this->requestType;

        return [
            'type' => 'request_status_change',
            'request_type' => $this->requestType,
            'status' => $this->status,
            'title' => "{$typeLabel}: {$label}",
            'message' => "Tu solicitud de {$typeLabel} para el día {$this->details['date']} ha sido {$label}.",
            'action_url' => $this->requestType === 'swap' ? route('schedules.swap-history') : route('schedules.leave-history'),
        ];
    }
}
