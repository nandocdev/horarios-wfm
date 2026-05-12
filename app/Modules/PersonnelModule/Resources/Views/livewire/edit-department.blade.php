<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center space-x-4">
                <flux:link href="{{ route('organization.departments.show', $department) }}" variant="ghost">
                    ← Volver
                </flux:link>
                <h1 class="text-2xl font-bold text-gray-900">Editar Departamento</h1>
            </div>
        </div>

        <form wire:submit="save" class="p-6 space-y-6">
            <div class="grid grid-cols-1 gap-6">
                <flux:field>
                    <label for="directorate_id"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dirección *</label>
                    <select wire:model="directorate_id" id="directorate_id" required
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Selecciona una dirección</option>
                        @foreach($this->directorates as $directorate)
                            <option value="{{ $directorate->id }}">{{ $directorate->name }}</option>
                        @endforeach
                    </select>
                    <flux:error name="directorate_id" />
                </flux:field>

                <flux:field>
                    <flux:input wire:model="name" label="Nombre *" placeholder="Ingresa el nombre del departamento"
                        required />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:textarea wire:model="description" label="Descripción"
                        placeholder="Describe las funciones del departamento" rows="4" />
                    <flux:error name="description" />
                </flux:field>
            </div>

            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                <flux:link href="{{ route('organization.departments.show', $department) }}" variant="ghost">
                    Cancelar
                </flux:link>
                <flux:button type="submit" variant="primary">
                    Actualizar Departamento
                </flux:button>
            </div>
        </form>
    </div>
</div>
