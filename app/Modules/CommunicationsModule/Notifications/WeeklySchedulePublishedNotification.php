<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class WeeklySchedulePublishedNotification extends Notification
{
    public function __construct(public readonly array $payload) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Horario Semanal Publicado: ' . ($this->payload['week_period'] ?? 'Nueva Semana'))
            ->greeting('Hola, ' . $notifiable->name)
            ->line('Se ha publicado oficialmente el horario semanal para el periodo: **' . ($this->payload['week_period'] ?? 'indicado') . '**.')
            ->line('Por favor, ingresa a la plataforma para revisar tus turnos asignados y asegurar el cumplimiento de tu jornada.')
            ->action('Ver mi Horario', $this->payload['action_url'] ?? url('/schedules/my-schedule'))
            ->line('Gracias por tu compromiso con la operación.')
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
