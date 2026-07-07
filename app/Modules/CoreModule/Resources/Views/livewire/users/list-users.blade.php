<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Gestión de Usuarios</flux:heading>
            <flux:subheading>Administración de identidades institucionales y roles de acceso.</flux:subheading>
        </div>

        @can('create', \App\Modules\CoreModule\Models\User::class)
            <flux:button href="{{ route('users.create') }}" variant="primary" icon="plus" wire:navigate>
                Nuevo Usuario
            </flux:button>
        @endcan
    </div>

    <flux:card class="space-y-4">
        <div class="flex flex-col md:flex-row gap-4 items-end">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o email..."
                class="flex-1" icon="magnifying-glass" clearable />

            <flux:select wire:model.live="role" :label="__('Filtrar por Rol')" placeholder="Todos los roles"
                class="w-full md:w-64">
                @foreach($all_roles as $roleItem)
                    <flux:select.option value="{{ $roleItem->name }}">{{ $roleItem->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:table :paginate="$users">
            <flux:table.columns>
                <flux:table.column>Usuario</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Roles</flux:table.column>
                <flux:table.column>Último Acceso</flux:table.column>
                <flux:table.column align="end"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar size="sm" :name="$user->name" />
                                <div class="flex flex-col">
                                    <flux:text variant="strong">{{ $user->name }}</flux:text>
                                    <flux:text size="sm" class="text-zinc-500">{{ $user->email }}</flux:text>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge :variant="$user->is_active ? 'success' : 'danger'" size="sm" inset="top bottom">
                                {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @foreach($user->roles as $roleItem)
                                    <flux:badge size="xs" variant="ghost" inset="top bottom">{{ $roleItem->name }}</flux:badge>
                                @endforeach
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:text size="sm">
                                {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Nunca' }}
                            </flux:text>
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            <flux:button.group>
                                <flux:button :href="route('users.edit', $user)" variant="ghost" size="sm"
                                    title="Editar usuario" wire:navigate>
                                    <flux:icon.pencil-square class="w-4 h-4" />
                                </flux:button>

                                @can('update', $user)
                                    <flux:button wire:click="toggleStatus({{ $user->id }})" variant="ghost" size="sm"
                                        :title="$user->is_active ? 'Desactivar usuario' : 'Activar usuario'"
                                        wire:loading.attr="disabled">
                                        <flux:icon :name="$user->is_active ? 'lock-closed' : 'lock-open'" class="w-4 h-4" 
                                            wire:loading.remove wire:target="toggleStatus({{ $user->id }})" />
                                        <flux:icon name="arrow-path" class="w-4 h-4 animate-spin" 
                                            wire:loading wire:target="toggleStatus({{ $user->id }})" />
                                    </flux:button>
                                @endcan

                                @can('delete', $user)
                                    <flux:button wire:click="delete({{ $user->id }})" variant="ghost" size="sm"
                                        title="Eliminar usuario" wire:loading.attr="disabled"
                                        class="text-red-500 hover:text-red-600">
                                        <flux:icon.trash class="w-4 h-4" wire:loading.remove wire:target="delete({{ $user->id }})" />
                                        <flux:icon name="arrow-path" class="w-4 h-4 animate-spin" 
                                            wire:loading wire:target="delete({{ $user->id }})" />
                                    </flux:button>
                                @endcan
                            </flux:button.group>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8">
                            <flux:text size="sm" class="text-zinc-500 italic">No se encontraron usuarios con esos criterios.
                            </flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
