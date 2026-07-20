<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-md shadow-sm border border-slate-200">
        <div class="p-4 border-b border-slate-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <flux:link href="{{ route('organization.departments.index') }}" variant="ghost">
                        ← Volver
                    </flux:link>
                    <h1 class="text-3xl font-bold text-slate-900">{{ $department->name }}</h1>
                </div>
                <div class="flex space-x-2">
                    <flux:link href="{{ route('organization.departments.edit', $department) }}" variant="outline" size="sm">
                        Editar
                    </flux:link>
                    <flux:button wire:click="toggleStatus"
                        variant="{{ $department->is_active ? 'destructive' : 'primary' }}" size="sm">
                        {{ $department->is_active ? 'Desactivar' : 'Activar' }}
                    </flux:button>
                </div>
            </div>
        </div>

        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900 mb-4">Información General</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Nombre</dt>
                            <dd class="text-sm text-slate-900">{{ $department->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Dirección</dt>
                            <dd class="text-sm text-slate-900">
                                <flux:link href="{{ route('organization.directorates.show', $department->directorate) }}"
                                    variant="link" class="text-blue-600 hover:text-blue-800">
                                    {{ $department->directorate->name }}
                                </flux:link>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Descripción</dt>
                            <dd class="text-sm text-slate-900">{{ $department->description ?: 'Sin descripción' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Estado</dt>
                            <dd class="text-sm">
                                @if($department->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-green-50 border border-green-200 text-green-600">
                                        Activa
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-red-50 border border-red-200 text-red-600">
                                        Inactiva
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Fecha de creación</dt>
                            <dd class="text-sm text-slate-900">{{ $department->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Última actualización</dt>
                            <dd class="text-sm text-slate-900">{{ $department->updated_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h3 class="text-xl font-semibold text-slate-900 mb-4">Posiciones ({{ $department->positions->count() }})</h3>
                    @if($department->positions->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($department->positions as $position)
                                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-md">
                                    <div>
                                        <div class="font-medium text-slate-900">{{ $position->name }}</div>
                                        <div class="text-sm text-slate-500">{{ $position->position_code }}</div>
                                    </div>
                                    <flux:link href="{{ route('organization.positions.show', $position) }}" variant="ghost" size="sm">
                                        Ver
                                    </flux:link>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-500">No hay posiciones asociadas.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
