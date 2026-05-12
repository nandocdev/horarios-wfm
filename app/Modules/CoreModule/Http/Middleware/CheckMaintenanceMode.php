<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Http\Middleware;

use App\Modules\CoreModule\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maintenance = AppSetting::get('maintenance_mode');

        if ($maintenance && ($maintenance['enabled'] ?? false)) {
            // Permitir acceso a administradores o si ya estamos en la página de mantenimiento
            if ($this->shouldAllowAccess($request)) {
                return $next($request);
            }

            return response()->view('core::maintenance', [
                'message' => $maintenance['message'] ?? 'El sistema se encuentra en mantenimiento.'
            ], 503);
        }

        return $next($request);
    }

    /**
     * Determina si la petición debe ser permitida a pesar del mantenimiento.
     */
    private function shouldAllowAccess(Request $request): bool
    {
        // 1. Si la ruta ya es la de mantenimiento (para evitar bucles, aunque aquí usamos una vista directa)
        if ($request->is('maintenance')) {
            return true;
        }

        // 2. Si el usuario es administrador (puedes ajustar esta lógica según tus roles)
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            return true;
        }

        return false;
    }
}
