<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire;

use App\Modules\OrganizationModule\Actions\CreateDirectorateAction;
use App\Modules\OrganizationModule\DTOs\DirectorateDTO;
use App\Modules\OrganizationModule\Livewire\Forms\DirectorateForm;
use App\Modules\OrganizationModule\Models\Directorate;
use Livewire\Component;

class CreateDirectorate extends Component
{
    public DirectorateForm $form;

    public function save(): void
    {
        $this->authorize('create', Directorate::class);

        $this->form->validate();

        $dto = DirectorateDTO::fromArray([
            'name' => $this->form->name,
            'description' => $this->form->description,
            'is_active' => $this->form->is_active,
        ]);

        $action = new CreateDirectorateAction;
        $directorate = $action->execute($dto);

        session()->flash('success', 'Dirección creada exitosamente.');

        $this->dispatch('directorateCreated', directorateId: $directorate->id);

        $this->form->reset();
    }

    public function render()
    {
        return view('organization::livewire.create-directorate');
    }
}
