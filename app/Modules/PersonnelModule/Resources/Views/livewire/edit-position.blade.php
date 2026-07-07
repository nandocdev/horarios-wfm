<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="bg-white rounded-md shadow-sm border border-slate-200">
        <div class="p-4 border-b border-slate-200">
            <div class="flex items-center space-x-4">
                <flux:link href="{{ route('organization.positions.show', $position) }}" variant="ghost">
                    ← Volver
                </flux:link>
                <h1 class="text-3xl font-bold text-slate-900">Editar Posición</h1>
            </div>
        </div>

        <form wire:submit="save" class="p-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:input wire:model="name" label="Nombre *" placeholder="Ingresa el nombre de la posición"
                        required />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:input wire:model="position_code" label="Código de Posición *" placeholder="Ej. OP-001"
                        required />
                    <flux:error name="position_code" />
                </flux:field>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <flux:field>
                    <label for="department_id"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Departamento *</label>
                    <select wire:model="department_id" id="department_id" required
                        class="block w-full px-4 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                        <option value="">Selecciona un departamento</option>
                        @foreach($this->departments as $department)
                            <option value="{{ $department->id }}">
                                {{ $department->name }} ({{ $department->directorate->name }})
                            </option>
                        @endforeach
                    </select>
                    <flux:error name="department_id" />
                </flux:field>

                <flux:field>
                    <flux:textarea wire:model="description" label="Descripción"
                        placeholder="Describe las responsabilidades de la posición" rows="4" />
                    <flux:error name="description" />
                </flux:field>

                <flux:field>
                    <flux:checkbox wire:model="is_active" label="Posición activa" />
                    <flux:error name="is_active" />
                </flux:field>
            </div>

            <div class="flex justify-end space-x-4 pt-8 border-t border-slate-200">
                <flux:link href="{{ route('organization.positions.show', $position) }}" variant="ghost">
                    Cancelar
                </flux:link>
                <flux:button type="submit" variant="primary">
                    Actualizar Posición
                </flux:button>
            </div>
        </form>
    </div>
</div>
