<?php

declare(strict_types=1);

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware transversal: exige 2FA cuando está habilitado en Fortify
 * y el usuario aún no lo ha configurado.
 *
 * Respeta el flujo de Fortify (confirm=true) y deja pasar rutas
 * de configuración de 2FA / logout / Livewire.
 */
final class EnsureTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || $user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        $routeName = optional($request->route())->getName();

        if ($this->isAllowedRoute($routeName, $request)) {
            return $next($request);
        }

        // Redirige al panel de seguridad donde se gestiona 2FA
        if (! $request->expectsJson()) {
            return redirect()->route('security.edit');
        }

        return $next($request);
    }

    private function isAllowedRoute(?string $routeName, Request $request): bool
    {
        $allowed = [
            'security.edit',
            'logout',
            'two-factor.login',
            'two-factor.login.store',
            'password.confirm',
            'password.confirm.store',
        ];

        if (in_array($routeName, $allowed, true)) {
            return true;
        }

        // Livewire / Fortify internals
        if ($request->hasHeader('X-Livewire')
            || str_contains($request->url(), 'livewire')
            || str_contains($request->url(), 'fortify')
        ) {
            return true;
        }

        return false;
    }
}
