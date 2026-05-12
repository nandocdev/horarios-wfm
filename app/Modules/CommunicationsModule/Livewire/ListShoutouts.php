<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Livewire;

use App\Modules\CommunicationsModule\Models\Shoutout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado de reconocimientos (shoutouts) en el panel de administración.
 */
class ListShoutouts extends Component
{
    use WithPagination;

    public string $search = '';

    /**
     * Elimina un shoutout.
     */
    public function deleteShoutout(int $id): void
    {
        $shoutout = Shoutout::findOrFail($id);
        $this->authorize('delete', $shoutout);

        $shoutout->delete();
        toast('Reconocimiento eliminado correctamente.');
    }

    /**
     * Renderizado con paginación.
     */
    public function render()
    {
        return view('communications::livewire.list-shoutouts', [
            'shoutouts' => Shoutout::query()
                ->with('employee')
                ->where('message', 'ilike', "%{$this->search}%")
                ->latest()
                ->paginate(10),
        ]);
    }
}
