<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('CSS - Seguridad: Contraseña Actualizada'))
            ->greeting(__('Hola, :name', ['name' => $notifiable->name]))
            ->line(__('Te informamos que la contraseña de tu cuenta en el Sistema WFM ha sido actualizada exitosamente.'))
            ->line(__('Si no realizaste este cambio, por favor contacta al departamento de TI de la CSS de inmediato.'))
            ->action(__('Acceder a la Plataforma'), url('/dashboard'))
            ->line(__('Gracias por ayudarnos a mantener segura la información institucional.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'password_changed',
            'message' => __('Contraseña actualizada exitosamente.'),
        ];
    }
}
