<?php

declare(strict_types=1);

namespace App\Src\Identity\Presentation\Livewire;

use App\Src\Identity\Application\DTOs\CreateUserDTO;
use App\Src\Identity\Application\Handlers\CreateUserHandler;
use App\Src\Shared\Domain\ValueObjects\Email;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Title('Nuevo Usuario')]
class CreateUserForm extends Component
{
    use AuthorizesRequests;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $isActive = true;
    public bool $forcePasswordChange = false;
    public array $selectedRoles = [];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'isActive' => ['boolean'],
            'forcePasswordChange' => ['boolean'],
            'selectedRoles' => ['array'],
            'selectedRoles.*' => ['string', 'exists:roles,name'],
        ];
    }

    public function save(): void
    {
        $this->authorize('users.create');
        $this->validate();

        $dto = new CreateUserDTO(
            name: $this->name,
            email: new Email($this->email),
            password: $this->password,
            isActive: $this->isActive,
            forcePasswordChange: $this->forcePasswordChange,
            roles: $this->selectedRoles,
        );

        app(CreateUserHandler::class)->handle($dto);

        $this->resetForm();
        toast('Usuario registrado correctamente.');
        $this->redirectRoute('identity.users.index', navigate: true);
    }

    public function render()
    {
        return view('identity::livewire.create-user-form', [
            'allRoles' => Role::all(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'email', 'password', 'password_confirmation', 'isActive', 'forcePasswordChange', 'selectedRoles']);
    }
}
