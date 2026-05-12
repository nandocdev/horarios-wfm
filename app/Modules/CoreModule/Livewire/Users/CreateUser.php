<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Livewire\Users;

use App\Modules\CoreModule\Actions\CreateUserAction;
use App\Modules\CoreModule\Livewire\Forms\UserForm;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Registrar Nuevo Usuario')]
class CreateUser extends Component
{
    public UserForm $form;

    /**
     * Guarda el nuevo usuario delegando en la Action.
     */
    public function save(CreateUserAction $action)
    {
        $this->authorize('create', User::class);

        $this->validate();

        $action->execute($this->form->toDTO());

        toast('Usuario registrado correctamente con roles institucionales.');

        return $this->redirectRoute('users.index', navigate: true);
    }

    public function render()
    {
        return view('core::livewire.users.create-user', [
            'available_roles' => Role::all(),
        ]);
    }
}
