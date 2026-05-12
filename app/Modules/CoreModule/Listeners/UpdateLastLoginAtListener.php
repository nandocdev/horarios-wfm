<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Listeners;

use App\Modules\CoreModule\Models\User;
use Illuminate\Auth\Events\Login;

/**
 * Actualiza la marca de último acceso exitoso del usuario.
 */
final class UpdateLastLoginAtListener
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $isFirstLogin = is_null($event->user->last_login_at);

        $event->user->forceFill([
            'last_login_at' => now(),
            'force_password_change' => $isFirstLogin ? true : $event->user->force_password_change,
        ])->saveQuietly();
    }
}
