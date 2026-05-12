<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <flux:link href="{{ route('organization.directorates.index') }}" variant="ghost">
                        ← Volver
                    </flux:link>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $directorate->name }}</h1>
                </div>
                <div class="flex space-x-2">
                    <flux:link href="{{ route('organization.directorates.edit', $directorate) }}" variant="outline"
                        size="sm">
                        Editar
                    </flux:link>
                    <flux:button wire:click="toggleStatus"
                        variant="{{ $directorate->is_active ? 'destructive' : 'primary' }}" size="sm">
                        {{ $directorate->is_active ? 'Desactivar' : 'Activar' }}
                    </flux:button>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Información General</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nombre</dt>
                            <dd class="text-sm text-gray-900">{{ $directorate->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Descripción</dt>
                            <dd class="text-sm text-gray-900">{{ $directorate->description ?: 'Sin descripción' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Estado</dt>
                            <dd class="text-sm">
                                @if($directorate->is_active)
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Activa
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Inactiva
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Fecha de creación</dt>
                            <dd class="text-sm text-gray-900">{{ $directorate->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Última actualización</dt>
                            <dd class="text-sm text-gray-900">{{ $directorate->updated_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Departamentos
                        ({{ $directorate->departments->count() }})</h3>
                    @if($directorate->departments->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($directorate->departments as $department)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $department->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $department->description ?: 'Sin descripción' }}
                                        </div>
                                    </div>
                                    <flux:link href="{{ route('organization.departments.show', $department) }}" variant="ghost"
                                        size="sm">
                                        Ver
                                    </flux:link>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No hay departamentos asociados.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
