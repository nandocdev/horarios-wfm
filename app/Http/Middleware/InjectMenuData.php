<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Services\MenuDataService;

class InjectMenuData
{
    /**
     * Inyecta los conteos de menú para que estén disponibles globalmente en las vistas.
     */
    public function handle(Request $request, Closure $next)
    {
        // Solo inyectar si estamos devolviendo HTML, no en API
        if (!$request->wantsJson()) {
            $counts = app(MenuDataService::class)->getCounts(auth()->user());
            View::share('menuCounts', $counts);
        }

        return $next($request);
    }
}
