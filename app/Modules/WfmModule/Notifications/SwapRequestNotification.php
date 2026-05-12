<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Notifications;

use App\Modules\WorkflowsModule\Models\ShiftSwapRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SwapRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ShiftSwapRequest $swapRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $requester = $this->swapRequest->requester;

        return [
            'type' => 'swap_request',
            'swap_request_id' => $this->swapRequest->id,
            'requester_name' => "{$requester->first_name} {$requester->last_name}",
            'requested_date' => $this->swapRequest->requested_date->format('Y-m-d'),
            'title' => 'Nueva Solicitud de Intercambio',
            'message' => "{$requester->first_name} ha solicitado intercambiar un turno contigo para el día {$this->swapRequest->requested_date->format('d/m/Y')}.",
            'action_url' => route('schedules.swap-history'),
        ];
    }
}
