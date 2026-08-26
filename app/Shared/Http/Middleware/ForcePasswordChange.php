<?php

declare(strict_types=1);

namespace App\Shared\Http\Middleware;

use App\Http\Middleware\EnsurePasswordChange;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware transversal: fuerza cambio de contraseña cuando force_password_change=true.
 *
 * Ubicación canónica en Shared (ADR Modules.md §2). Mantiene BC con
 * App\Http\Middleware\EnsurePasswordChange — delega la lógica allí
 * para no duplicar allow-list.
 */
final class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        return app(EnsurePasswordChange::class)->handle($request, $next);
    }
}
