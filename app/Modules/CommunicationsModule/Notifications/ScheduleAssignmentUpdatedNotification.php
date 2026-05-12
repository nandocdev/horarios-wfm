<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScheduleAssignmentUpdatedNotification extends Notification
{
    public function __construct(public readonly array $payload) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $date = $this->payload['date_human'] ?? 'la fecha indicada';
        
        return (new MailMessage)
            ->subject('Modificación en tu Horario - ' . $date)
            ->greeting('Hola, ' . $notifiable->name)
            ->line("Se ha realizado una modificación en tu horario programado para la semana del **{$this->payload['week_period']}**.")
            ->line("### Detalles del turno para el {$date}:")
            ->line("* **Entrada:** {$this->payload['start_time']}")
            ->line("* **Salida:** {$this->payload['end_time']}")
            ->line("* **Almuerzo:** {$this->payload['lunch']}")
            ->line("* **Descanso (Break):** {$this->payload['break']}")
            ->action('Ver mi Horario Completo', $this->payload['action_url'])
            ->line('Por favor, asegúrate de revisar estos cambios para cumplir con la cobertura programada.')
            ->salutation('Atentamente, El equipo de WFM');
    }

    public function toDatabase($notifiable): array
    {
        return $this->payload;
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload);
    }
}
