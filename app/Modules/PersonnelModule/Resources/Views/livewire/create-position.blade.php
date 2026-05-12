@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center space-x-4">
                    <flux:link href="{{ route('organization.positions.index') }}" variant="ghost">
                        ← Volver
                    </flux:link>
                    <h1 class="text-2xl font-bold text-gray-900">Crear Posición</h1>
                </div>
            </div>

            <form wire:submit="save" class="p-6 space-y-6">
                <div class="grid grid-cols-1 gap-6">
                    <flux:field>
                        <flux:input wire:model="name" label="Nombre *" placeholder="Ingresa el nombre de la posición"
                            required />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <label for="department_id"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Departamento *</label>
                        <select wire:model="department_id" id="department_id" required
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
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

                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <flux:link href="{{ route('organization.positions.index') }}" variant="ghost">
                        Cancelar
                    </flux:link>
                    <flux:button type="submit" variant="primary">
                        Crear Posición
                    </flux:button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            $wire.on('positionCreated', (event) => {
                // Redirigir a la lista o mostrar mensaje
                window.location.href = '{{ route("organization.positions.index") }}';
            });
        </script>
    @endpush
@endsection
