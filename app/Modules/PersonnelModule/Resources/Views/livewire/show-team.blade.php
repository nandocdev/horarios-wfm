<div class="container mx-auto px-4 py-8">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <flux:button icon="chevron-left" variant="ghost" href="{{ route('organization.teams.index') }}" :inset="true" wire:navigate />
                <div>
                    <flux:heading size="xl" level="1">{{ $team->name }}</flux:heading>
                    <flux:subheading>Detalle y gestión de miembros del equipo operativo.</flux:subheading>
                </div>
            </div>

            <div class="flex gap-2">
                <flux:button href="{{ route('organization.teams.transfer', $team) }}" icon="arrows-right-left" wire:navigate>Gestionar Miembros</flux:button>
                <flux:button href="{{ route('organization.teams.edit', $team) }}" icon="pencil-square" variant="primary" wire:navigate>Editar</flux:button>
                <flux:button wire:click="toggleStatus" variant="ghost" icon="{{ $team->is_active ? 'eye-slash' : 'eye' }}" title="{{ $team->is_active ? 'Desactivar' : 'Activar' }}" />
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Información General -->
            <div class="lg:col-span-1 space-y-6">
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Información General</flux:heading>
                    
                    <div class="space-y-4">
                        <flux:field label="Nombre">
                            <div class="text-zinc-900 dark:text-white font-medium">{{ $team->name }}</div>
                        </flux:field>

                        <flux:field label="Supervisor">
                            @if($team->supervisor)
                                <div class="flex items-center gap-3 py-1">
                                    <flux:avatar initials="{{ $team->supervisor->initials }}" size="sm" />
                                    <div class="flex flex-col">
                                        <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $team->supervisor->full_name }}</div>
                                        <div class="text-xs text-zinc-500">{{ $team->supervisor->employee_number }}</div>
                                    </div>
                                </div>
                            @else
                                <div class="text-zinc-400 text-sm italic py-1">Sin supervisor asignado</div>
                            @endif
                        </flux:field>

                        <flux:field label="Descripción">
                            <div class="text-zinc-500 text-sm">{{ $team->description ?: 'Sin descripción' }}</div>
                        </flux:field>

                        <flux:field label="Estado">
                            @if($team->is_active)
                                <flux:badge color="green" size="sm">Activo</flux:badge>
                            @else
                                <flux:badge color="red" size="sm">Inactivo</flux:badge>
                            @endif
                        </flux:field>

                        <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 space-y-2">
                            <div class="flex justify-between text-xs">
                                <span class="text-zinc-400">Creado:</span>
                                <span class="text-zinc-600 dark:text-zinc-300">{{ $team->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-zinc-400">Actualizado:</span>
                                <span class="text-zinc-600 dark:text-zinc-300">{{ $team->updated_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </flux:card>
            </div>

            <!-- Miembros del Equipo -->
            <div class="lg:col-span-2">
                <flux:card>
                    <div class="flex items-center justify-between mb-6">
                        <flux:heading size="lg">Miembros del Equipo ({{ $team->users->count() }})</flux:heading>
                        <div class="flex gap-2">
                            <flux:modal.trigger name="add-member-modal">
                                <flux:button wire:click="loadAvailableEmployees" variant="ghost" size="sm" icon="plus">Añadir</flux:button>
                            </flux:modal.trigger>
                            <flux:button href="{{ route('organization.teams.transfer', $team) }}" variant="ghost" size="sm" icon="arrows-right-left" wire:navigate title="Transferencia masiva" />
                        </div>
                    </div>

                    @if($team->users->isNotEmpty())
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($team->users as $user)
                                <div class="py-4 flex items-center justify-between first:pt-0 last:pb-0">
                                    <div class="flex items-center gap-3">
                                        <flux:avatar initials="{{ $user->initials }}" size="sm" />
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $user->full_name }}</div>
                                            <div class="text-xs text-zinc-500">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex gap-2">
                                        <flux:button href="{{ route('employees.show', $user) }}" variant="ghost" size="sm" icon="eye" :inset="true" wire:navigate />
                                        
                                        <flux:modal.trigger name="remove-member-{{ $user->id }}">
                                            <flux:button variant="ghost" size="sm" icon="trash" color="red" :inset="true" />
                                        </flux:modal.trigger>

                                        <flux:modal name="remove-member-{{ $user->id }}" class="min-w-[22rem]">
                                            <div class="space-y-6">
                                                <div>
                                                    <flux:heading size="lg">¿Remover miembro?</flux:heading>
                                                    <flux:subheading>Estás a punto de desvincular a <strong>{{ $user->full_name }}</strong> de este equipo. Esta acción quedará registrada en el historial.</flux:subheading>
                                                </div>

                                                <div class="flex gap-2">
                                                    <flux:spacer />
                                                    <flux:modal.close>
                                                        <flux:button variant="ghost">Cancelar</flux:button>
                                                    </flux:modal.close>
                                                    <flux:button wire:click="removeMember({{ $user->id }})" variant="primary" color="red">Remover</flux:button>
                                                </div>
                                            </div>
                                        </flux:modal>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center py-20 text-zinc-400 gap-3">
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-full">
                                <flux:icon.users class="size-10 text-zinc-300" />
                            </div>
                            <span>No hay miembros asignados a este equipo.</span>
                            <flux:modal.trigger name="add-member-modal">
                                <flux:button wire:click="loadAvailableEmployees" variant="subtle" size="sm">Añadir el primero</flux:button>
                            </flux:modal.trigger>
                        </div>
                    @endif
                </flux:card>
            </div>
        </div>
    </div>

    <!-- Modal Añadir Miembro -->
    <flux:modal name="add-member-modal" class="md:min-w-[30rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Añadir Miembro al Equipo</flux:heading>
                <flux:subheading>Selecciona un empleado activo que no pertenezca a otro equipo.</flux:subheading>
            </div>

            <flux:select wire:model="selectedEmployeeId" label="Empleado" placeholder="Buscar empleado...">
                @foreach($availableEmployees as $id => $label)
                    <flux:select.option value="{{ $id }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button wire:click="addMember" variant="primary">Añadir al Equipo</flux:button>
            </div>
        </div>
    </flux:modal>
</div>