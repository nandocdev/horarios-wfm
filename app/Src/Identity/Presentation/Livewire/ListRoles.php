<?php

declare(strict_types=1);

namespace App\Src\Identity\Presentation\Livewire;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Title('Roles y Permisos')]
class ListRoles extends Component
{
    use AuthorizesRequests;

    public string $name = '';
    public string $code = '';
    public int $hierarchyLevel = 50;
    public ?int $editingRoleId = null;
    public array $selectedPermissions = [];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'code' => ['required', 'string', 'max:50', 'unique:roles,code'],
            'hierarchyLevel' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function editPermissions(int $roleId): void
    {
        $this->authorize('roles.edit');

        $role = Role::with('permissions')->findOrFail($roleId);
        $this->editingRoleId = $role->id;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();

        $this->dispatch('open-modal', id: 'role-permissions');
    }

    public function savePermissions(): void
    {
        $this->authorize('roles.edit');

        $role = Role::findOrFail($this->editingRoleId);
        $permissions = Permission::whereIn('name', $this->selectedPermissions)->pluck('id');
        $role->permissions()->sync($permissions);

        $this->reset(['editingRoleId', 'selectedPermissions']);
        $this->dispatch('close-modal', id: 'role-permissions');

        toast('Permisos actualizados correctamente.');
    }

    public function delete(int $roleId): void
    {
        $this->authorize('roles.delete');

        $role = Role::withCount('users')->findOrFail($roleId);

        if ($role->users_count > 0) {
            $this->addError('delete', 'No se puede eliminar un rol con usuarios asignados.');
            return;
        }

        $role->delete();

        toast('Rol eliminado correctamente.');
    }

    public function render()
    {
        $roles = Role::withCount('permissions', 'users')
            ->orderBy('hierarchy_level')
            ->get();

        $permissions = Permission::all()
            ->groupBy(fn ($perm) => explode('.', $perm->name)[0] ?? 'system');

        return view('identity::livewire.list-roles', [
            'roles' => $roles,
            'availablePermissions' => $permissions,
        ]);
    }
}
