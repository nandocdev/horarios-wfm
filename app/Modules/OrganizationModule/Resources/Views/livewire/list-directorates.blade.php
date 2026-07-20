<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-md shadow-sm border border-slate-200">
        <div class="p-4 border-b border-slate-200">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-slate-900">Direcciones</h1>
                <flux:link href="{{ route('organization.directorates.create') }}" variant="primary">
                    Nueva Dirección
                </flux:link>
            </div>
        </div>

        <div class="p-4">
            <div class="mb-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o descripción..."
                        label="Buscar" />
                </div>
                <div>
                    <flux:select wire:model.live="activeFilter" label="Estado">
                        <option value="">Todos los estados</option>
                        <option value="1">Activas</option>
                        <option value="0">Inactivas</option>
                    </flux:select>
                </div>
                <div>
                    <flux:select wire:model.live="perPage" label="Por página">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </flux:select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <flux:table :paginate="$directorates">
                    <flux:table.columns>
                        <flux:table.column>Nombre</flux:table.column>
                        <flux:table.column>Descripción</flux:table.column>
                        <flux:table.column>Estado</flux:table.column>
                        <flux:table.column>Departamentos</flux:table.column>
                        <flux:table.column>Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($directorates as $directorate)
                            <flux:table.row :key="$directorate->id">
                                <flux:table.cell>
                                    <div class="font-medium text-slate-900">{{ $directorate->name }}</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="max-w-xs truncate">{{ $directorate->description }}</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($directorate->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-green-50 border border-green-200 text-green-600">
                                            Activa
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-red-50 border border-red-200 text-red-600">
                                            Inactiva
                                        </span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>{{ $directorate->departments_count ?? 0 }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:button.group>
                                        <flux:button href="{{ route('organization.directorates.show', $directorate) }}"
                                            variant="ghost" size="sm" title="Ver dirección">
                                            <flux:icon.eye class="w-4 h-4" />
                                        </flux:button>
                                        <flux:button href="{{ route('organization.directorates.edit', $directorate) }}"
                                            variant="ghost" size="sm" title="Editar dirección">
                                            <flux:icon.pencil-square class="w-4 h-4" />
                                        </flux:button>
                                    </flux:button.group>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center py-8 text-slate-500">
                                    No se encontraron direcciones.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            @if($directorates->hasPages())
                <div class="mt-8">
                    {{ $directorates->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
