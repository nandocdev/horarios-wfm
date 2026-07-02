<?php

declare(strict_types=1);

namespace App\Src\Identity\Presentation\Livewire;

use App\Modules\CoreModule\Models\User as LegacyUser;
use App\Src\Identity\Application\DTOs\CreateUserDTO;
use App\Src\Identity\Application\Handlers\CreateUserHandler;
use App\Src\Identity\Infrastructure\Persistence\EloquentUser;
use App\Src\Shared\Domain\ValueObjects\Email;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Title('Gestión de Usuarios')]
class ManageUsers extends Component
{
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';
    public ?int $editingUserId = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public bool $isActive = true;
    public bool $forcePasswordChange = false;
    public array $selectedRoles = [];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingUserId),
            ],
            'password' => [$this->editingUserId ? 'nullable' : 'required', 'min:8'],
            'isActive' => ['boolean'],
            'forcePasswordChange' => ['boolean'],
            'selectedRoles' => ['array'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        if (! auth()->user()?->can('users.create')) {
            abort(403);
        }

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
    }

    public function edit(int $userId): void
    {
        if (! auth()->user()?->can('users.edit')) {
            abort(403);
        }

        $user = EloquentUser::with('roles')->findOrFail($userId);
        $authUser = auth()->user();

        if ($authUser && $authUser instanceof LegacyUser) {
            $targetLegacy = LegacyUser::find($userId);
            if ($targetLegacy && ! $authUser->can('update', $targetLegacy)) {
                abort(403);
            }
        }

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->isActive = (bool) $user->is_active;
        $this->forcePasswordChange = (bool) $user->force_password_change;
        $this->selectedRoles = $user->roles->pluck('name')->toArray();
        $this->password = '';
    }

    public function update(): void
    {
        if (! auth()->user()?->can('users.edit')) {
            abort(403);
        }

        $user = EloquentUser::findOrFail($this->editingUserId);

        $this->validate();

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

        $this->cancelEdit();
        toast('Usuario actualizado correctamente.');
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingUserId', 'name', 'email', 'password', 'isActive', 'forcePasswordChange', 'selectedRoles']);
    }

    public function toggleStatus(int $userId): void
    {
        if (! auth()->user()?->can('users.edit')) {
            abort(403);
        }

        $user = EloquentUser::findOrFail($userId);

        $newStatus = ! $user->is_active;
        $user->update(['is_active' => $newStatus]);

        if (! $newStatus) {
            LegacyUser::find($userId)?->tokens()->delete();
        }

        toast($newStatus ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.');
    }

    public function delete(int $userId): void
    {
        if (! auth()->user()?->can('users.delete')) {
            abort(403);
        }

        $user = EloquentUser::findOrFail($userId);
        $user->delete();

        toast('Usuario eliminado correctamente.');
    }

    public function render()
    {
        $query = EloquentUser::with('roles')
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'ilike', "%{$this->search}%")
                    ->orWhere('email', 'ilike', "%{$this->search}%");
            }))
            ->when($this->roleFilter, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $this->roleFilter)));

        return view('identity::livewire.manage-users', [
            'users' => $query->latest()->paginate(10),
            'allRoles' => Role::all(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'email', 'password', 'isActive', 'forcePasswordChange', 'selectedRoles']);
    }
}
