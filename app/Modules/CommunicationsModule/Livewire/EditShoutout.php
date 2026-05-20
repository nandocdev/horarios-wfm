<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Livewire;

use App\Modules\CommunicationsModule\Actions\UpdateShoutoutAction;
use App\Modules\CommunicationsModule\Livewire\Forms\ShoutoutForm;
use App\Modules\CommunicationsModule\Models\Shoutout;
use App\Modules\PersonnelModule\Models\Employee;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Componente para la edición de un reconocimiento (shoutout) existente.
 */
class EditShoutout extends Component
{
    use WithFileUploads;

    public ShoutoutForm $form;

    public Shoutout $shoutout;

    /**
     * Inicialización del componente.
     */
    public function mount(Shoutout $shoutout): void
    {
        $this->shoutout = $shoutout;
        $this->form->setShoutout($shoutout);
    }

    /**
     * Actualiza el reconocimiento.
     */
    public function save(UpdateShoutoutAction $action)
    {
        $this->authorize('update', $this->shoutout);
        $this->form->validate();

        $action->execute($this->shoutout, $this->form->toDTO());

        toast('Reconocimiento actualizado correctamente.');

        return $this->redirectRoute('communications.shoutouts.index', navigate: true);
    }

    /**
     * Renderizado utilizando la vista compartida.
     */
    public function render()
    {
        return view('communications::livewire.shoutout-form', [
            'mode' => 'edit',
            'employees' => Employee::active()->with('position')->orderBy('first_name')->get(),
        ]);
    }
}
