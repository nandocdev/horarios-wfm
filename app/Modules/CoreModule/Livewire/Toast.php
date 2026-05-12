<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Componente global de notificaciones (Toasts) [REEMPLAZA TOAST PREMIUM DE FLUX].
 *
 * Se suscribe al evento 'toast' para mostrar mensajes dinámicos.
 */
class Toast extends Component
{
    public array $toasts = [];

    /**
     * Añade una notificación a la pila.
     *
     * @param  string  $message  El cuerpo del mensaje.
     * @param  string  $variant  El tipo de notificación (success, danger, warning, info).
     * @param  string|null  $heading  Título opcional.
     */
    #[On('toast')]
    public function addToast(string $message, string $variant = 'success', ?string $heading = null): void
    {
        $id = uniqid('toast_', true);

        $this->toasts[$id] = [
            'id' => $id,
            'message' => $message,
            'variant' => $variant,
            'heading' => $heading,
            'visible' => true,
        ];

        // Se auto-elimina en el cliente y luego se limpia en el servidor
        $this->dispatch('auto-hide-toast', id: $id);
    }

    /**
     * Elimina físicamente el toast de la memoria del componente.
     */
    #[On('remove-toast-server')]
    public function removeToast(string $id): void
    {
        unset($this->toasts[$id]);
    }

    public function render()
    {
        $this->checkSession();

        return view('core::livewire.toast');
    }

    /**
     * Revisa si hay notificaciones pendientes en la sesión y las integra.
     */
    protected function checkSession(): void
    {
        if (session()->has('toast')) {
            $data = session()->get('toast');
            $this->addToast(
                $data['message'],
                $data['variant'] ?? 'success',
                $data['heading'] ?? null
            );
            session()->forget('toast');
        }
    }
}
