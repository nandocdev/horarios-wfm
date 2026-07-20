<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-md shadow-sm border border-slate-200">
        <div class="p-4 border-b border-slate-200">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-slate-900">Departamentos</h1>
                <flux:link href="{{ route('organization.departments.create') }}" variant="primary">
                    Nuevo Departamento
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
                    <flux:select wire:model.live="directorateFilter" label="Dirección">
                        <option value="">Todas las direcciones</option>
                        @foreach($this->directorates as $directorate)
                            <option value="{{ $directorate->id }}">{{ $directorate->name }}</option>
                        @endforeach
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
                <flux:table :paginate="$departments">
                    <flux:table.columns>
                        <flux:table.column>Nombre</flux:table.column>
                        <flux:table.column>Dirección</flux:table.column>
                        <flux:table.column>Descripción</flux:table.column>
                        <flux:table.column>Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($departments as $department)
                            <flux:table.row :key="$department->id">
                                <flux:table.cell>
                                    <div class="font-medium text-slate-900">{{ $department->name }}</div>
                                </flux:table.cell>
                                <flux:table.cell>{{ $department->directorate->name }}</flux:table.cell>
                                <flux:table.cell>
                                    <div class="max-w-xs truncate">{{ $department->description }}</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button.group>
                                        <flux:button href="{{ route('organization.departments.show', $department) }}"
                                            variant="ghost" size="sm" title="Ver departamento">
                                            <flux:icon.eye class="w-4 h-4" />
                                        </flux:button>
                                        <flux:button href="{{ route('organization.departments.edit', $department) }}"
                                            variant="ghost" size="sm" title="Editar departamento">
                                            <flux:icon.pencil-square class="w-4 h-4" />
                                        </flux:button>
                                    </flux:button.group>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="text-center py-8 text-slate-500">
                                    No se encontraron departamentos.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            @if($departments->hasPages())
                <div class="mt-8">
                    {{ $departments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
