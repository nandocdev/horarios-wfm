<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="bg-white rounded-md shadow-sm border border-slate-200">
        <div class="p-4 border-b border-slate-200">
            <div class="flex items-center space-x-4">
                <flux:link href="{{ route('organization.departments.show', $department) }}" variant="ghost">
                    ← Volver
                </flux:link>
                <h1 class="text-3xl font-bold text-slate-900">Editar Departamento</h1>
            </div>
        </div>

        <form wire:submit="save" class="p-4 space-y-4">
            <div class="grid grid-cols-1 gap-4">
                <flux:field>
                    <label for="directorate_id"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Dirección *</label>
                    <select wire:model="directorate_id" id="directorate_id" required
                        class="block w-full px-4 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-700 dark:border-slate-600 dark:text-white">
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

            <div class="flex justify-end space-x-4 pt-8 border-t border-slate-200">
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
