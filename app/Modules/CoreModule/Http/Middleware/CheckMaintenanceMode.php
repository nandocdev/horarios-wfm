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
                'message' => $maintenance['message'] ?? 'El sistema se encuentra en mantenimiento.',
            ], 503);
        }

        return $next($request);
    }

    /**
     * Determina si la petición debe ser permitida a pesar del mantenimiento.
     */
    private function shouldAllowAccess(Request $request): bool
    {
        // 1. Permitir rutas esenciales de autenticación y mantenimiento
        if ($request->is('maintenance') || $request->is('login') || $request->is('logout')) {
            return true;
        }

        // 2. Si el usuario tiene privilegios de administración del sistema
        if (Auth::check() && Auth::user()->can('admin.system')) {
            return true;
        }

        return false;
    }
}
