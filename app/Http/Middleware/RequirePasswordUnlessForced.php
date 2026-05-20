<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Request;

class RequirePasswordUnlessForced
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Si el usuario está forzado a cambiar la contraseña (primer login),
        // no le pedimos que la confirme de nuevo en la pantalla intermedia.
        if ($request->user() && $request->user()->force_password_change) {
            return $next($request);
        }

        // De lo contrario, aplicamos la regla de seguridad estándar de Laravel
        return app(RequirePassword::class)->handle($request, $next);
    }
}
