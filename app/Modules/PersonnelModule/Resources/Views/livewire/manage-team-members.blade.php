<div class="space-y-8">
    <x-wfm.page-header title="Gestionar Miembros - {{ $team->name }}" description="Asignación y remoción de miembros del equipo.">
        <x-slot:actions>
            <flux:button icon="chevron-left" variant="ghost" href="{{ route('organization.teams.show', $team) }}" wire:navigate>
                Volver al equipo
            </flux:button>
            <flux:button wire:click="openAssignModal" variant="primary" icon="plus">
                Agregar Miembro
            </flux:button>
        </x-slot:actions>
    </x-wfm.page-header>

    <x-wfm-section title="Miembros Actuales ({{ $team->users->count() }})">
        @if($team->users->isNotEmpty())
            <div class="space-y-2">
                @foreach($team->users as $employee)
                    <div class="flex items-center justify-between p-3 bg-wfm-surface rounded-md">
                        <div class="flex items-center gap-3">
                            <flux:avatar :name="$employee->name" :initials="$employee->initials" size="sm" />
                            <div>
                                <p class="text-sm font-medium text-wfm-navy-800 dark:text-white">{{ $employee->name }}</p>
                                <p class="text-xs text-wfm-surface-muted">{{ $employee->email }} · {{ $employee->employee_number }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:button href="{{ route('employees.show', $employee) }}" variant="ghost" size="sm" icon="eye" wire:navigate />
                            <flux:button wire:click="openRemoveModal({{ $employee->id }})" variant="ghost" size="sm" icon="trash" />
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <x-wfm-empty icon="users" message="No hay miembros en este equipo" description="Haz clic en 'Agregar Miembro' para asignar empleados." />
        @endif
    </x-wfm-section>

    <x-wfm-section title="Historial de Asignaciones">
        @if($team->members->isNotEmpty())
            <x-wfm.table :headers="['Empleado', 'Fecha Inicio', 'Fecha Fin', 'Estado']" compact>
                @foreach($team->members->sortByDesc('start_date') as $member)
                    <flux:table.row :key="$member->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:avatar :name="$member->employee->name" :initials="$member->employee->initials" size="xs" />
                                <div>
                                    <p class="text-sm font-medium text-wfm-navy-800">{{ $member->employee->name }}</p>
                                    <p class="text-xs text-wfm-surface-muted">{{ $member->employee->email }}</p>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $member->start_date->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $member->end_date?->format('d/m/Y') ?? 'Activo' }}</flux:table.cell>
                        <flux:table.cell>
                            @if($member->is_active)
                                <x-wfm.adherence-badge :value="100" target="50" size="xs" />
                            @else
                                <span class="text-xs text-wfm-surface-muted">Inactivo</span>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </x-wfm.table>
        @else
            <x-wfm-empty icon="clock" message="No hay historial de asignaciones" />
        @endif
    </x-wfm-section>

    <flux:modal wire:model="showAssignModal" class="md:w-96">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Asignar Empleado</flux:heading>
                <flux:subheading>Selecciona un empleado y la fecha de inicio.</flux:subheading>
            </div>

            <form wire:submit="assignEmployee" class="space-y-4">
                <flux:field>
                    <flux:label>Empleado</flux:label>
                    <flux:select wire:model="form.employee_id" placeholder="Selecciona un empleado">
                        @foreach($availableEmployees as $employee)
                            <flux:select.option value="{{ $employee->id }}">
                                {{ $employee->name }} ({{ $employee->employee_number }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="form.employee_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha de Inicio</flux:label>
                    <flux:input wire:model="form.start_date" type="date" />
                    <flux:error name="form.start_date" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha de Fin (opcional)</flux:label>
                    <flux:input wire:model="form.end_date" type="date" />
                    <flux:error name="form.end_date" />
                </flux:field>

                <div class="flex justify-end gap-2">
                    <flux:button wire:click="closeAssignModal" variant="ghost">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">Asignar</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <flux:modal wire:model="showRemoveModal" class="md:w-96">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Remover Empleado</flux:heading>
                <flux:subheading>Confirma la fecha de fin de la asignación.</flux:subheading>
            </div>

            @if($selectedEmployeeId)
                <div class="p-3 bg-wfm-surface rounded-md text-sm">
                    Empleado: <strong>{{ $team->users->find($selectedEmployeeId)?->name }}</strong>
                </div>
            @endif

            <form wire:submit="removeEmployee" class="space-y-4">
                <flux:field>
                    <flux:label>Fecha de Fin</flux:label>
                    <flux:input wire:model="form.remove_end_date" type="date" />
                    <flux:error name="form.remove_end_date" />
                </flux:field>

                <div class="flex justify-end gap-2">
                    <flux:button wire:click="closeRemoveModal" variant="ghost">Cancelar</flux:button>
                    <flux:button type="submit" variant="danger">Remover</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
