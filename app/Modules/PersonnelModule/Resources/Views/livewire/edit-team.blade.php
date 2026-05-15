<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
        <flux:button icon="chevron-left" variant="ghost" href="{{ route('organization.teams.show', $team) }}" :inset="true" />
        <div>
            <flux:heading size="xl" level="1">Editar Equipo</flux:heading>
            <flux:subheading>Modifica los detalles del equipo operativo: {{ $team->name }}.</flux:subheading>
        </div>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 gap-6">
                <flux:input 
                    wire:model="name" 
                    label="Nombre *" 
                    placeholder="Nombre del equipo"
                    required 
                />

                <flux:textarea 
                    wire:model="description" 
                    label="Descripción"
                    placeholder="Propósito del equipo" 
                    rows="3" 
                />

                <flux:select wire:model="supervisor_id" label="Supervisor" placeholder="Selecciona un responsable">
                    <flux:select.option value="">Sin supervisor asignado</flux:select.option>
                    @foreach($this->availableSupervisors as $employee)
                        <flux:select.option value="{{ $employee->id }}">
                            {{ $employee->full_name }} ({{ $employee->employee_number }})
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:checkbox wire:model="is_active" label="Equipo activo y operativo" />
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button href="{{ route('organization.teams.show', $team) }}" variant="ghost">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Actualizar Equipo
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
