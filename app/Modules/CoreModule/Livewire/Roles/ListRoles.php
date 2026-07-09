<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Livewire\Roles;

use App\Modules\CoreModule\Actions\CreateRoleAction;
use App\Modules\CoreModule\Actions\SyncRolePermissionsAction;
use App\Modules\CoreModule\DTOs\RoleDTO;
use App\Modules\CoreModule\Models\Permission;
use App\Modules\CoreModule\Models\Role;
use Flux\Flux;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Gestión de Roles')]
class ListRoles extends Component
{
    #[Rule(['required', 'string', 'max:255'])]
    public string $name = '';

    #[Rule(['required', 'string', 'max:50', 'alpha_dash'])]
    public string $code = '';

    #[Rule(['required', 'integer', 'min:1', 'max:100'])]
    public int $hierarchy_level = 10;

    public ?Role $editingRole = null;

    public array $selectedPermissions = [];

    /**
     * Muestra el modal para editar permisos de un rol.
     */
    public function editPermissions(Role $role): void
    {
        $this->editingRole = $role;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();

        Flux::modal('role-permissions')->show();
    }

    /**
     * Sincroniza permisos mediante la Action.
     */
    public function savePermissions(SyncRolePermissionsAction $action): void
    {
        $this->authorize('update', $this->editingRole);

        $action->execute($this->editingRole, $this->selectedPermissions);

        Flux::modal('role-permissions')->close();
        toast('Permisos del rol actualizados correctamente.');
    }

    /**
     * Crea un nuevo rol institucional delegando en la Action.
     */
    public function createRole(CreateRoleAction $action): void
    {
        $this->authorize('create', Role::class);

        $this->validate();

        $action->execute(RoleDTO::fromArray([
            'name' => $this->name,
            'code' => $this->code,
            'hierarchy_level' => $this->hierarchy_level,
        ]));

        $this->reset(['name', 'code', 'hierarchy_level']);
        toast('Nuevo rol institucional registrado.');
    }

    public function render()
    {
        return view('core::livewire.roles.list-roles', [
            'roles' => Role::with('permissions')->orderBy('hierarchy_level')->get(),
            'available_permissions' => Permission::all()->groupBy('module'), // Agrupación lógica
        ]);
    }
}
