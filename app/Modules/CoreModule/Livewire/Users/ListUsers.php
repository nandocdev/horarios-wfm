<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Livewire\Users;

use App\Modules\CoreModule\Actions\DeleteUserAction;
use App\Modules\CoreModule\Actions\ToggleUserStatusAction;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Gestión de Usuarios')]
class ListUsers extends Component
{
    use WithPagination;

    public string $search = '';

    public string $role = '';

    /**
     * Resetea la paginación al buscar.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Renderiza el listado de usuarios con filtros institucionales.
     */
    public function render()
    {
        $query = User::with('roles')
            ->when($this->search, fn ($q) => $q->where('name', 'ilike', '%'.$this->search.'%')
                ->orWhere('email', 'ilike', '%'.$this->search.'%')
            )
            ->when($this->role, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $this->role))
            );

        return view('core::livewire.users.list-users', [
            'users' => $query->latest()->paginate(10),
            'all_roles' => Role::all(),
        ]);
    }

    /**
     * Alterna el estado de activación de un usuario.
     */
    public function toggleStatus(User $user, ToggleUserStatusAction $action): void
    {
        $this->authorize('update', $user);

        $action->execute($user, ! $user->is_active);

        toast(
            $user->is_active
            ? 'Usuario activado correctamente.'
            : 'Usuario desactivado correctamente.'
        );
    }

    public function delete(int $userId, DeleteUserAction $action): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('delete', $user);

        $action->execute($user);

        toast('Usuario eliminado (soft delete).');
        $this->resetPage();
    }
}
