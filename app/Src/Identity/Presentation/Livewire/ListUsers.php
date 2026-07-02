<?php

declare(strict_types=1);

namespace App\Src\Identity\Presentation\Livewire;

use App\Src\Identity\Infrastructure\Persistence\EloquentUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Title('Gestión de Usuarios')]
class ListUsers extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';
    public string $roleFilter = '';
    public ?string $statusFilter = null;

    protected $queryString = ['search', 'roleFilter', 'statusFilter'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $userId): void
    {
        $this->authorize('users.edit');

        $user = EloquentUser::findOrFail($userId);
        $user->update(['is_active' => ! $user->is_active]);

        toast($user->is_active ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.');
    }

    public function delete(int $userId): void
    {
        $this->authorize('users.delete');

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
            ->when($this->roleFilter, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $this->roleFilter)))
            ->when($this->statusFilter !== null, fn ($q) => $q->where('is_active', filter_var($this->statusFilter, FILTER_VALIDATE_BOOLEAN)));

        return view('identity::livewire.list-users', [
            'users' => $query->latest()->paginate(10),
            'allRoles' => Role::all(),
        ]);
    }
}
