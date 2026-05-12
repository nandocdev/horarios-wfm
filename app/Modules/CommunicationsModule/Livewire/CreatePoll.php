<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Livewire;

use App\Modules\CommunicationsModule\Actions\CreatePollAction;
use App\Modules\CommunicationsModule\Livewire\Forms\PollForm;
use App\Modules\CommunicationsModule\Models\Poll;
use Livewire\Component;

/**
 * Vista de creación de encuestas en el panel de administración.
 */
class CreatePoll extends Component
{
    public PollForm $form;

    /**
     * Inicialización del componente.
     */
    public function mount(): void
    {
        $this->authorize('create', Poll::class);
        $this->form->expires_at = now()->addDays(7)->format('Y-m-d\TH:i');
        $this->form->scheduled_at = now()->format('Y-m-d\TH:i');
    }

    /**
     * Agrega una opción al formulario.
     */
    public function addOption(): void
    {
        $this->form->addOption();
    }

    /**
     * Elimina una opción del formulario.
     */
    public function removeOption(int $index): void
    {
        $this->form->removeOption($index);
    }

    /**
     * Guarda la nueva encuesta.
     */
    public function save(CreatePollAction $action)
    {
        $this->form->validate();

        $poll = $action->execute($this->form->toDTO());

        // Manejar el flujo de moderación
        if ($this->form->workflow_action === 'submit_review') {
            $poll->submitForReview();
        }

        toast('Encuesta creada satisfactoriamente.');

        $this->redirectRoute('communications.polls.index', navigate: true);
    }

    /**
     * Renderizado del formulario.
     */
    public function render()
    {
        return view('communications::livewire.poll-form', [
            'mode' => 'create',
        ]);
    }
}
