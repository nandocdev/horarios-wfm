<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Livewire\Shared;

use App\Modules\CoreModule\Models\UserTourProgress as UserTourProgressModel;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class UserTourProgress extends Component
{
    public function render()
    {
        return view('core::livewire.shared.user-tour-progress', [
            'progress' => Auth::user() ? UserTourProgressModel::mapFor(Auth::user()) : [],
        ]);
    }

    /**
     * Persiste el avance de un tour para el usuario autenticado.
     * Disparado desde el frontend (resources/js/tours) vía Livewire.dispatch.
     */
    #[On('tour:record')]
    public function record(string $tour, int $version = 1, string $state = 'completed'): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        UserTourProgressModel::record($user, $tour, $version, $state);
    }
}
