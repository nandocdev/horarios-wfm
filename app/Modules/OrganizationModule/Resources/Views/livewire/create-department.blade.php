<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="bg-white rounded-md shadow-sm border border-slate-200">
        <div class="p-4 border-b border-slate-200">
            <div class="flex items-center space-x-4">
                <flux:link href="{{ route('organization.departments.index') }}" variant="ghost">
                    ← Volver
                </flux:link>
                <h1 class="text-3xl font-bold text-slate-900">Crear Departamento</h1>
            </div>
        </div>

        <form wire:submit="save" class="p-4 space-y-4">
            <div class="grid grid-cols-1 gap-4">
                <flux:field>
                    <flux:select wire:model="form.directorate_id" label="Dirección *" required>
                        <option value="">Selecciona una dirección</option>
                        @foreach($this->directorates as $directorate)
                            <option value="{{ $directorate->id }}">{{ $directorate->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="form.directorate_id" />
                </flux:field>

                <flux:field>
                    <flux:input wire:model="form.name" label="Nombre *" placeholder="Ingresa el nombre del departamento" required />
                    <flux:error name="form.name" />
                </flux:field>

                <flux:field>
                    <flux:textarea wire:model="form.description" label="Descripción"
                        placeholder="Describe las funciones del departamento" rows="4" />
                    <flux:error name="form.description" />
                </flux:field>
            </div>

            <div class="flex justify-end space-x-4 pt-8 border-t border-slate-200">
                <flux:link href="{{ route('organization.departments.index') }}" variant="ghost">
                    Cancelar
                </flux:link>
                <flux:button type="submit" variant="primary">
                    Crear Departamento
                </flux:button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('departmentCreated', (event) => {
                    window.location.href = '{{ route("organization.departments.index") }}';
                });
            });
        </script>
    @endpush
</div>
