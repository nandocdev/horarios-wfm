<?php

declare(strict_types=1);

if (! function_exists('toast')) {
    /**
     * Envía una notificación global (Toast).
     *
     * @param  string  $message  Mensaje a mostrar.
     * @param  string  $variant  Variante (success, danger, warning, info).
     * @param  string|null  $heading  Título opcional.
     */
    function toast(string $message, ?string $heading = null, string $variant = 'success'): void
    {
        session()->flash('toast', [
            'message' => $message,
            'heading' => $heading,
            'variant' => $variant,
        ]);
    }
}
