<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="bg-white rounded-md shadow-sm border border-slate-200">
        <div class="p-4 border-b border-slate-200">
            <div class="flex items-center space-x-4">
                <flux:link href="{{ route('organization.positions.index') }}" variant="ghost">
                    ← Volver
                </flux:link>
                <h1 class="text-3xl font-bold text-slate-900">Crear Posición</h1>
            </div>
        </div>

        <form wire:submit="save" class="p-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:input wire:model="form.name" label="Nombre *" placeholder="Ingresa el nombre de la posición" required />
                    <flux:error name="form.name" />
                </flux:field>

                <flux:field>
                    <flux:input wire:model="form.position_code" label="Código de Posición *" placeholder="Ej. OP-001" required />
                    <flux:error name="form.position_code" />
                </flux:field>

                <flux:field>
                    <flux:select wire:model="form.department_id" label="Departamento *" required>
                        <option value="">Selecciona un departamento</option>
                        @foreach($this->departments as $department)
                            <option value="{{ $department->id }}">
                                {{ $department->name }} ({{ $department->directorate->name }})
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="form.department_id" />
                </flux:field>

                <flux:field>
                    <flux:input wire:model="form.salary" label="Salario" placeholder="Ej. 1500.00" type="number" step="0.01" min="0" />
                    <flux:error name="form.salary" />
                </flux:field>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <flux:field>
                    <flux:textarea wire:model="form.description" label="Descripción"
                        placeholder="Describe las responsabilidades de la posición" rows="4" />
                    <flux:error name="form.description" />
                </flux:field>

                <flux:field>
                    <flux:checkbox wire:model="form.is_active" label="Posición activa" />
                    <flux:error name="form.is_active" />
                </flux:field>
            </div>

            <div class="flex justify-end space-x-4 pt-8 border-t border-slate-200">
                <flux:link href="{{ route('organization.positions.index') }}" variant="ghost">
                    Cancelar
                </flux:link>
                <flux:button type="submit" variant="primary">
                    Crear Posición
                </flux:button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('positionCreated', (event) => {
                    window.location.href = '{{ route("organization.positions.index") }}';
                });
            });
        </script>
    @endpush
</div>
