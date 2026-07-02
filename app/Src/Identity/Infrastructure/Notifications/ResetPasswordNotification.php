<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

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

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'password_reset',
        ];
    }
}
