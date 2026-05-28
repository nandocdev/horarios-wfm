<?php

declare(strict_types=1);

use App\Http\Middleware\EnsurePasswordChange;
use App\Http\Middleware\InjectMenuData;
use App\Modules\CoreModule\Http\Middleware\CheckMaintenanceMode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', CheckMaintenanceMode::class);
        $middleware->appendToGroup('web', EnsurePasswordChange::class);
        $middleware->appendToGroup('web', InjectMenuData::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, $request) {
            if ($request->expectsJson() || $request->header('X-Livewire')) {
                return response()->json([
                    'message' => 'El archivo es demasiado grande para el servidor (Límite post_max_size excedido).',
                ], 413);
            }

            return back()->withErrors(['uploads' => 'El archivo es demasiado grande para el servidor.']);
        });
    })->create();
