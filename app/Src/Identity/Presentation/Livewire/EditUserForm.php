<?php

declare(strict_types=1);

namespace App\Src\Identity\Presentation\Livewire;

use App\Src\Identity\Infrastructure\Persistence\EloquentUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Title('Editar Usuario')]
class EditUserForm extends Component
{
    use AuthorizesRequests;

    public ?int $userId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $isActive = true;
    public bool $forcePasswordChange = false;
    public array $selectedRoles = [];

    public function mount(int $user): void
    {
        $this->authorize('users.edit');

        $userModel = EloquentUser::with('roles')->findOrFail($user);

        $this->userId = $userModel->id;
        $this->name = $userModel->name;
        $this->email = $userModel->email;
        $this->isActive = (bool) $userModel->is_active;
        $this->forcePasswordChange = (bool) $userModel->force_password_change;
        $this->selectedRoles = $userModel->roles->pluck('name')->toArray();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->userId),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'isActive' => ['boolean'],
            'forcePasswordChange' => ['boolean'],
            'selectedRoles' => ['array'],
            'selectedRoles.*' => ['string', 'exists:roles,name'],
        ];
    }

    public function save(): void
    {
        $this->authorize('users.edit');
        $this->validate();

        $user = EloquentUser::findOrFail($this->userId);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->isActive,
            'force_password_change' => $this->forcePasswordChange,
        ];

        if (! empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $user->update($data);

        if (! empty($this->selectedRoles)) {
            $roleIds = Role::whereIn('name', $this->selectedRoles)->pluck('id')->toArray();
            $user->roles()->sync($roleIds);
        }

        toast('Usuario actualizado correctamente.');
        $this->redirectRoute('identity.users.index', navigate: true);
    }

    public function render()
    {
        return view('identity::livewire.edit-user-form', [
            'allRoles' => Role::all(),
        ]);
    }
}
