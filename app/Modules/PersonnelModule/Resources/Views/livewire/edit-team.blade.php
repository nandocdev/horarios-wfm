<div class="max-w-2xl mx-auto space-y-8">
    <div class="flex items-center gap-4">
        <flux:button icon="chevron-left" variant="ghost" href="{{ route('organization.teams.show', $team) }}" :inset="true" />
        <div>
            <flux:heading size="xl" level="1">Editar Equipo</flux:heading>
            <flux:subheading>Modifica los detalles del equipo operativo: {{ $team->name }}.</flux:subheading>
        </div>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 gap-4">
                <flux:input 
                    wire:model="form.name" 
                    label="Nombre *" 
                    placeholder="Nombre del equipo"
                    required 
                />

                <flux:textarea 
                    wire:model="form.description" 
                    label="Descripción"
                    placeholder="Propósito del equipo" 
                    rows="3" 
                />

                <flux:select wire:model="form.supervisor_id" label="Supervisor" placeholder="Selecciona un responsable">
                    <flux:select.option value="">Sin supervisor asignado</flux:select.option>
                    @foreach($this->availableSupervisors as $supervisor)
                        <flux:select.option value="{{ $supervisor->id }}">
                            {{ $supervisor->label }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="form.cisco_team_id"
                    label="ID Cisco Finesse"
                    placeholder="Ej. team_12345"
                />

                <flux:checkbox wire:model="form.is_active" label="Equipo activo y operativo" />
            </div>

            <div class="flex justify-end gap-4 pt-8 border-t border-slate-200 dark:border-slate-700">
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
