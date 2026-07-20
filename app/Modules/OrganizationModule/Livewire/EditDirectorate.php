<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire;

use App\Modules\OrganizationModule\Actions\UpdateDirectorateAction;
use App\Modules\OrganizationModule\DTOs\DirectorateDTO;
use App\Modules\OrganizationModule\Livewire\Forms\DirectorateForm;
use App\Modules\OrganizationModule\Models\Directorate;
use Livewire\Component;

class EditDirectorate extends Component
{
    public DirectorateForm $form;

    public Directorate $directorate;

    public function mount(Directorate $directorate): void
    {
        $this->authorize('update', $directorate);
        $this->directorate = $directorate;

        $this->form->fill([
            'name' => $directorate->name,
            'description' => $directorate->description,
            'is_active' => (bool) $directorate->is_active,
        ]);
    }

    public function save()
    {
        $this->authorize('update', $this->directorate);

        $this->form->validate();

        $dto = DirectorateDTO::fromArray([
            'name' => $this->form->name,
            'description' => $this->form->description,
            'is_active' => $this->form->is_active,
        ]);

        $action = new UpdateDirectorateAction;
        $this->directorate = $action->execute($this->directorate, $dto);

        session()->flash('success', 'Dirección actualizada exitosamente.');

        $this->dispatch('directorateUpdated', directorateId: $this->directorate->id);

        return $this->redirect(route('organization.directorates.show', $this->directorate));
    }

    public function render()
    {
        return view('organization::livewire.edit-directorate')
            ->layout('layouts.app');
    }
}
