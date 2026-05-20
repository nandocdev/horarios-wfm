<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Livewire;

use App\Modules\CommunicationsModule\Actions\CreateShoutoutAction;
use App\Modules\CommunicationsModule\Livewire\Forms\ShoutoutForm;
use App\Modules\PersonnelModule\Models\Employee;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Componente para la creación de un nuevo reconocimiento (shoutout).
 */
class CreateShoutout extends Component
{
    use WithFileUploads;

    public ShoutoutForm $form;

    /**
     * Guarda el reconocimiento.
     */
    public function save(CreateShoutoutAction $action)
    {
        $this->authorize('create', Shoutout::class);
        $this->form->validate();

        $action->execute($this->form->toDTO());

        toast('Reconocimiento guardado correctamente.');

        return $this->redirectRoute('communications.shoutouts.index', navigate: true);
    }

    /**
     * Renderizado.
     */
    public function render()
    {
        return view('communications::livewire.shoutout-form', [
            'mode' => 'create',
            'employees' => Employee::active()->with('position')->orderBy('first_name')->get(),
        ]);
    }
}
