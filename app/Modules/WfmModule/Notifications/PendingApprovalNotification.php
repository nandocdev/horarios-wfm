<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PendingApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $requestType,
        protected string $requesterName,
        protected array $details
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $typeLabels = [
            'swap' => 'Intercambio de Turno',
            'leave' => 'Solicitud de Permiso',
        ];

        $typeLabel = $typeLabels[$this->requestType] ?? $this->requestType;

        return [
            'type' => 'pending_approval',
            'request_type' => $this->requestType,
            'requester_name' => $this->requesterName,
            'title' => "Nueva Aprobación Pendiente: {$typeLabel}",
            'message' => "{$this->requesterName} ha solicitado un {$typeLabel} para el día {$this->details['date']}. Requiere tu revisión.",
            'action_url' => route('schedules.manager-approvals', [], false),
        ];
    }
}
