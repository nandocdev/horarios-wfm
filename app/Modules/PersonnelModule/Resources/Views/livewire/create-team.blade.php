<div class="max-w-2xl mx-auto space-y-8">
    <div class="flex items-center gap-4">
        <flux:button icon="chevron-left" variant="ghost" href="{{ route('organization.teams.index') }}" :inset="true" />
        <div>
            <flux:heading size="xl" level="1">Crear Equipo</flux:heading>
            <flux:subheading>Define un nuevo grupo de trabajo operativo.</flux:subheading>
        </div>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 gap-4">
                <flux:input 
                    wire:model="form.name" 
                    label="Nombre *" 
                    placeholder="Ej. Soporte Nivel 1"
                    required 
                />

                <flux:textarea 
                    wire:model="form.description" 
                    label="Descripción"
                    placeholder="Describe las funciones y objetivos del equipo" 
                    rows="3" 
                />

                <flux:select wire:model="form.supervisor_id" label="Supervisor" placeholder="Selecciona un responsable">
                    <flux:select.option value="">Sin supervisor asignado</flux:select.option>
                    @foreach($this->availableSupervisors as $employee)
                        <flux:select.option value="{{ $employee->id }}">
                            {{ $employee->full_name }} ({{ $employee->employee_number }})
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="form.cisco_team_id"
                    label="ID Cisco Finesse"
                    placeholder="Ej. team_12345"
                />

                <flux:checkbox wire:model="form.is_active" label="Equipo habilitado para operaciones" />
            </div>

            <div class="flex justify-end gap-4 pt-8 border-t border-slate-200 dark:border-slate-700">
                <flux:button href="{{ route('organization.teams.index') }}" variant="ghost">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Guardar Equipo
                </flux:button>
            </div>
        </form>
    </flux:card>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('teamCreated', (event) => {
                    window.location.href = '{{ route("organization.teams.index") }}';
                });
            });
        </script>
    @endpush
</div>
