<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center space-x-4">
                <flux:link href="{{ route('organization.teams.index') }}" variant="ghost">
                    ← Volver
                </flux:link>
                <h1 class="text-2xl font-bold text-gray-900">Crear Equipo</h1>
            </div>
        </div>

        <form wire:submit="save" class="p-6 space-y-6">
            <div class="grid grid-cols-1 gap-6">
                <flux:field>
                    <flux:input wire:model="name" label="Nombre *" placeholder="Ingresa el nombre del equipo"
                        required />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:textarea wire:model="description" label="Descripción"
                        placeholder="Describe las funciones del equipo" rows="4" />
                    <flux:error name="description" />
                </flux:field>

                <flux:field>
                    <flux:select wire:model="supervisor_id" label="Supervisor" placeholder="Selecciona un supervisor">
                        <option value="">Sin supervisor</option>
                        @foreach($this->availableSupervisors as $employee)
                            <option value="{{ $employee->id }}">
                                {{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_number }})
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="supervisor_id" />
                </flux:field>

                <flux:field>
                    <flux:checkbox wire:model="is_active" label="Equipo activo" />
                    <flux:error name="is_active" />
                </flux:field>
            </div>

            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                <flux:link href="{{ route('organization.teams.index') }}" variant="ghost">
                    Cancelar
                </flux:link>
                <flux:button type="submit" variant="primary">
                    Crear Equipo
                </flux:button>
            </div>
        </form>
    </div>
</div>

<script>
    $wire.on('teamCreated', (event) => {
        // Redirigir a la lista o mostrar mensaje
        window.location.href = '{{ route("organization.teams.index") }}';
    });
</script>
