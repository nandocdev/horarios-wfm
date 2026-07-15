<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Notifications;

use App\Modules\WfmModule\Models\ShiftSwapRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SwapRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ShiftSwapRequest $swapRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $requester = $this->swapRequest->requester;

        $dateRange = $this->swapRequest->start_date->format('d/m/Y');
        if ($this->swapRequest->end_date && $this->swapRequest->end_date->gt($this->swapRequest->start_date)) {
            $dateRange .= ' al '.$this->swapRequest->end_date->format('d/m/Y');
        }

        return [
            'type' => 'swap_request',
            'swap_request_id' => $this->swapRequest->id,
            'requester_name' => "{$requester->first_name} {$requester->last_name}",
            'start_date' => $this->swapRequest->start_date->format('Y-m-d'),
            'end_date' => $this->swapRequest->end_date ? $this->swapRequest->end_date->format('Y-m-d') : null,
            'title' => 'Nueva Solicitud de Intercambio',
            'message' => "{$requester->first_name} ha solicitado intercambiar un turno contigo para el periodo {$dateRange}.",
            'level' => 'info',
            'action_url' => route('schedules.swap-history', [], false),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
