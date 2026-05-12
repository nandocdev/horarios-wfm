<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Roles y Permisos</flux:heading>
            <flux:subheading>Define la jerarquía de acceso institucional y asigna permisos granulares.</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Listado de Roles -->
        <div class="lg:col-span-2 space-y-6">
            <flux:card>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Nivel</flux:table.column>
                        <flux:table.column>Rol</flux:table.column>
                        <flux:table.column>Código</flux:table.column>
                        <flux:table.column>Permisos</flux:table.column>
                        <flux:table.column align="end"></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($roles as $role)
                            <flux:table.row :key="$role->id">
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="match(true) {
                                                                                                    $role->hierarchy_level <= 10 => 'red',
                                                                                                    $role->hierarchy_level <= 30 => 'orange',
                                                                                                    $role->hierarchy_level <= 50 => 'amber',
                                                                                                    default => 'zinc',
                                                                                                }" variant="subtle"
                                        inset="top bottom">
                                        Lvl {{ $role->hierarchy_level }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell variant="strong">{{ $role->name }}</flux:table.cell>
                                <flux:table.cell class="font-mono text-xs uppercase text-zinc-500">{{ $role->code }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text size="sm" class="text-zinc-500">{{ $role->permissions->count() }} asignados
                                    </flux:text>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button wire:click="editPermissions({{ $role->id }})" variant="filled" size="xs"
                                        icon="shield-check">
                                        Gestionar
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center py-12">
                                    <flux:text size="sm" class="italic text-zinc-400">No se han registrado roles
                                        institucionales todavía.</flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        </div>

        <!-- Formulario de Creación -->
        @can('create', \App\Modules\CoreModule\Models\Role::class)
            <div class="space-y-6">
                <flux:card>
                    <flux:heading size="lg">Nuevo Rol</flux:heading>
                    <flux:subheading>Define un nuevo perfil de acceso.</flux:subheading>

                    <form wire:submit="createRole" class="mt-4 space-y-4">
                        <flux:input wire:model="name" :label="__('Nombre del Rol')" placeholder="Ej. Analista WFM"
                            required />
                        <flux:input wire:model="code" :label="__('Código Institucional')" placeholder="WFM_ANALYST"
                            required />
                        <flux:input wire:model="hierarchy_level" type="number" :label="__('Nivel de Jerarquía (1-100)')"
                            description="Nivel menor = más poder de administración." required />

                        <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="createRole">Registrar Rol</span>
                            <span wire:loading wire:target="createRole">Procesando...</span>
                        </flux:button>
                    </form>
                </flux:card>
            </div>
        @endcan
    </div>

    <!-- Modal de Permisos -->
    <flux:modal name="role-permissions" class="md:max-w-4xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Gestionar Permisos: <span
                        class="text-primary-600">{{ $editingRole?->name }}</span></flux:heading>
                <flux:subheading>Asigna los permisos granulares para este perfil de usuario.</flux:subheading>
            </div>

            <form wire:submit="savePermissions" class="space-y-6">
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-h-[60vh] overflow-y-auto px-1 scrollbar-thin scrollbar-thumb-zinc-200">
                    @foreach($available_permissions as $module => $perms)
                        <div
                            class="bg-zinc-50 dark:bg-white/5 p-4 rounded-xl border border-zinc-100 dark:border-white/10 space-y-3">
                            <flux:text
                                class="font-bold text-zinc-900 dark:text-zinc-100 uppercase text-[10px] tracking-widest opacity-70">
                                Módulo: {{ $module ?: 'Sistema' }}
                            </flux:text>
                            <div class="space-y-2">
                                @foreach($perms as $perm)
                                    <flux:checkbox wire:model="selectedPermissions" :value="$perm->name" :label="$perm->name"
                                        size="sm" class="text-zinc-600" />
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-white/10">
                    <flux:button x-on:click="$flux.modal('role-permissions').close()" variant="ghost">Cancelar
                    </flux:button>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="savePermissions">Guardar Permisos</span>
                        <flux:icon icon="arrow-path" class="w-4 h-4 animate-spin" wire:loading
                            wire:target="savePermissions" />
                        <span wire:loading wire:target="savePermissions" class="ml-2">Guardando...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>