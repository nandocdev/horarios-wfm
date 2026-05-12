<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Livewire\Users;

use App\Modules\CoreModule\Actions\UpdateUserAction;
use App\Modules\CoreModule\Livewire\Forms\UserForm;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Editar Usuario')]
class EditUser extends Component
{
    public User $user;

    public UserForm $form;

    public function mount(User $user): void
    {
        $this->authorize('update', $user);

        $this->user = $user;
        $this->form->set($user);
    }

    /**
     * Actualiza el usuario existente delegando en la Action.
     */
    public function save(UpdateUserAction $action)
    {
        $this->validate();

        $action->execute($this->user, $this->form->toDTO());

        toast('El perfil del usuario ha sido actualizado.');

        return $this->redirectRoute('users.index', navigate: true);
    }

    public function render()
    {
        return view('core::livewire.users.edit-user', [
            'available_roles' => Role::all(),
        ]);
    }
}
