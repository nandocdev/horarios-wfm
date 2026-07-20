<div class="space-y-8">
    <x-wfm.page-header :title="$team->name" description="Detalle y gestión de miembros del equipo operativo.">
        <x-slot:actions>
            <flux:button icon="arrows-right-left" href="{{ route('organization.teams.transfer', $team) }}" variant="ghost" wire:navigate>Gestionar Miembros</flux:button>
            <flux:button icon="pencil-square" href="{{ route('organization.teams.edit', $team) }}" variant="primary" wire:navigate>Editar</flux:button>
            <flux:button wire:click="toggleStatus" variant="ghost" icon="{{ $team->is_active ? 'eye-slash' : 'eye' }}" title="{{ $team->is_active ? 'Desactivar' : 'Activar' }}" />
        </x-slot:actions>
    </x-wfm.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-1 space-y-4">
            <x-wfm.section title="Información General">
                <div class="space-y-3">
                    <div>
                        <p class="kpi-label">Nombre</p>
                        <p class="text-sm font-medium text-wfm-navy-800 dark:text-white">{{ $team->name }}</p>
                    </div>

                    <div>
                        <p class="kpi-label">Supervisor</p>
                        @if($team->supervisor)
                            <div class="flex items-center gap-2 mt-1">
                                <flux:avatar :name="$team->supervisor->full_name" :initials="$team->supervisor->initials" size="xs" />
                                <div>
                                    <p class="text-sm font-medium text-wfm-navy-800 dark:text-white">{{ $team->supervisor->full_name }}</p>
                                    <p class="text-xs text-wfm-surface-muted">{{ $team->supervisor->employee_number }}</p>
                                </div>
                            </div>
                        @else
                            <p class="text-xs text-wfm-surface-muted italic mt-1">Sin supervisor asignado</p>
                        @endif
                    </div>

                    <div>
                        <p class="kpi-label">Descripción</p>
                        <p class="text-xs text-wfm-surface-muted">{{ $team->description ?: 'Sin descripción' }}</p>
                    </div>

                    <div>
                        <p class="kpi-label">Estado</p>
                        <x-wfm.agent-status :status="$team->is_active ? 'available' : 'offline'" :label="$team->is_active ? 'Activo' : 'Inactivo'" />
                    </div>

                    <div class="pt-3 border-t border-wfm-surface-border space-y-1">
                        <div class="flex justify-between text-[10px]">
                            <span class="text-wfm-surface-muted">Creado:</span>
                            <span class="text-wfm-navy-700 dark:text-white/70">{{ $team->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between text-[10px]">
                            <span class="text-wfm-surface-muted">Actualizado:</span>
                            <span class="text-wfm-navy-700 dark:text-white/70">{{ $team->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </x-wfm.section>
        </div>

        <div class="lg:col-span-2">
            <x-wfm.section :title="'Miembros del Equipo (' . $team->users->count() . ')'">
                <x-slot:actions>
                    <flux:modal.trigger name="add-member-modal">
                        <flux:button wire:click="loadAvailableEmployees" variant="ghost" size="sm" icon="plus">Añadir</flux:button>
                    </flux:modal.trigger>
                    <flux:button href="{{ route('organization.teams.transfer', $team) }}" variant="ghost" size="sm" icon="arrows-right-left" wire:navigate />
                </x-slot:actions>

                @if($team->users->isNotEmpty())
                    <div class="divide-y divide-wfm-surface-border">
                        @foreach($team->users as $user)
                            <div class="py-3 flex items-center justify-between first:pt-0 last:pb-0">
                                <div class="flex items-center gap-3">
                                    <flux:avatar :name="$user->full_name" :initials="$user->initials" size="sm" />
                                    <div>
                                        <p class="text-sm font-medium text-wfm-navy-800 dark:text-white">{{ $user->full_name }}</p>
                                        <p class="text-xs text-wfm-surface-muted">{{ $user->email }}</p>
                                    </div>
                                </div>

                                <div class="flex gap-1">
                                    <flux:button href="{{ route('employees.show', $user) }}" variant="ghost" size="sm" icon="eye" wire:navigate />
                                    <flux:modal.trigger :name="'remove-member-' . $user->id">
                                        <flux:button variant="ghost" size="sm" icon="trash" />
                                    </flux:modal.trigger>
                                </div>
                            </div>

                            <flux:modal :name="'remove-member-' . $user->id" class="min-w-[22rem]">
                                <div class="space-y-4">
                                    <div>
                                        <flux:heading size="lg">¿Remover miembro?</flux:heading>
                                        <flux:subheading>Estás a punto de desvincular a <strong>{{ $user->full_name }}</strong> de este equipo.</flux:subheading>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:spacer />
                                        <flux:modal.close>
                                            <flux:button variant="ghost">Cancelar</flux:button>
                                        </flux:modal.close>
                                        <flux:button wire:click="removeMember({{ $user->id }})" variant="danger">Remover</flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        @endforeach
                    </div>
                @else
                    <x-wfm-empty icon="users" message="No hay miembros asignados a este equipo.">
                        <x-slot:action>
                            <flux:modal.trigger name="add-member-modal">
                                <flux:button wire:click="loadAvailableEmployees" variant="subtle" size="sm">Añadir el primero</flux:button>
                            </flux:modal.trigger>
                        </x-slot:action>
                    </x-wfm-empty>
                @endif
            </x-wfm.section>
        </div>
    </div>

    <flux:modal name="add-member-modal" class="md:min-w-[30rem]">
        <div class="space-y-4">
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
                <flux:button wire:click="addMember" variant="primary">Añadir</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
