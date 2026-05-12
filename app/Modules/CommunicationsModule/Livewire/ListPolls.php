<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Livewire;

use App\Modules\CommunicationsModule\Models\Poll;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado de encuestas en el panel de administración.
 */
class ListPolls extends Component
{
    use WithPagination;

    public string $search = '';

    /**
     * Elimina una encuesta.
     */
    public function deletePoll(int $id): void
    {
        $poll = Poll::findOrFail($id);
        $this->authorize('delete', $poll);

        $poll->delete();
        toast('Encuesta eliminada correctamente.');
    }

    /**
     * Archiva (cierra) una encuesta manualmente.
     */
    public function archivePoll(int $id): void
    {
        $poll = Poll::findOrFail($id);
        $this->authorize('archive', $poll);

        $poll->archive();
        toast('Encuesta cerrada correctamente.');
    }

    /**
     * Renderizado con paginación.
     */
    public function render()
    {
        return view('communications::livewire.list-polls', [
            'polls' => Poll::query()
                ->withCount('responses')
                ->where('question', 'ilike', "%{$this->search}%")
                ->latest()
                ->paginate(10),
        ]);
    }
}
