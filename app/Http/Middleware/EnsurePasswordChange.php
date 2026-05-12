<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePasswordChange
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (is_null($user) || ! $user->force_password_change) {
            return $next($request);
        }

        $routeName = optional($request->route())->getName();

        // Si es una petición de Livewire o una ruta permitida, dejar pasar
        if ($request->hasHeader('X-Livewire') ||
            str_contains($request->url(), 'livewire/update') ||
            str_contains($request->url(), 'livewire-') ||
            $this->isAllowedRoute($routeName)) {
            return $next($request);
        }

        return redirect()->route('security.edit');
    }

    /**
     * Determina si la ruta actual está en la lista blanca.
     */
    protected function isAllowedRoute(?string $routeName): bool
    {
        $allowedRoutes = [
            'security.edit',
            'logout',
            'password.confirm',
            'password.confirm.store',
            'password.update',
            'password.request',
            'password.email',
            'password.reset',
        ];

        return in_array($routeName, $allowedRoutes, true);
    }
}
