<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * El token de restablecimiento de contraseña.
     */
    public string $token;

    /**
     * Crea una nueva instancia de notificación.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Obtiene los canales de notificación.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Obtiene la representación de correo de la notificación.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject(__('CSS - Seguridad: Solicitud de Restablecimiento de Contraseña'))
            ->greeting(__('Hola, :name', ['name' => $notifiable->name]))
            ->line(__('Recibiste este correo porque recibimos una solicitud de restablecimiento de contraseña para tu cuenta.'))
            ->action(__('Restablecer Contraseña'), $url)
            ->line(__('Este enlace de restablecimiento de contraseña expirará en :count minutos.', ['count' => config('auth.passwords.users.expire')]))
            ->line(__('Si no solicitaste un restablecimiento de contraseña, no es necesario realizar ninguna otra acción.'))
            ->salutation(__('Saludos, :app', ['app' => config('app.name')]));
    }
}
