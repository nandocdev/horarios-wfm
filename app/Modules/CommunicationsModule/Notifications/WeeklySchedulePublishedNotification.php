<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Notifications;

use App\Shared\Notifications\Concerns\HasWebexSupport;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklySchedulePublishedNotification extends Notification
{
    use HasWebexSupport;

    public function __construct(public readonly array $payload) {}

    protected function additionalVia(): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Horario Semanal Publicado: '.($this->payload['week_period'] ?? 'Nueva Semana'))
            ->greeting('Hola, '.$notifiable->name)
            ->line('Se ha publicado oficialmente el horario semanal para el periodo: **'.($this->payload['week_period'] ?? 'indicado').'**.')
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

    public function toWebex($notifiable): ?string
    {
        $week = $this->payload['week_period'] ?? 'la semana';

        return "*Horario Semanal Publicado*\n\n"
            ."El horario para {$week} ya está disponible.\n\n"
            .'Revisa tus turnos en la plataforma.';
    }
}
