<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('Contraseña Actualizada'),
            'message' => __('Tu contraseña ha sido actualizada exitosamente.'),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'password_changed',
            'title' => __('Contraseña Actualizada'),
            'message' => __('Tu contraseña ha sido actualizada exitosamente.'),
        ];
    }
}
