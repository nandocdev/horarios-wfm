<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Categorías</flux:heading>
            <flux:subheading>Gestiona las categorías para clasificar el contenido.</flux:subheading>
        </div>
        <flux:button href="{{ route('platform.communications.admin.categories.create') }}" variant="primary" icon="plus" wire:navigate>
            Nueva Categoría
        </flux:button>
    </div>

    @if(session('success'))
        <flux:card class="bg-green-50 border-green-200">
            <flux:text color="green">{{ session('success') }}</flux:text>
        </flux:card>
    @endif

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>Slug</flux:table.column>
                <flux:table.column>Descripción</flux:table.column>
                <flux:table.column>Color</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($categories as $category)
                    <flux:table.row :key="$category->id">
                        <flux:table.cell class="font-medium">{{ $category->name }}</flux:table.cell>
                        <flux:table.cell><code class="text-xs">{{ $category->slug }}</code></flux:table.cell>
                        <flux:table.cell class="max-w-xs truncate">{{ $category->description }}</flux:table.cell>
                        <flux:table.cell>
                            <span class="inline-block w-6 h-6 rounded-full border" style="background-color: {{ $category->color }}"></span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$category->is_active ? 'green' : 'red'" size="sm" variant="subtle">
                                {{ $category->is_active ? 'Activa' : 'Inactiva' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-2">
                                <flux:button href="{{ route('platform.communications.admin.categories.edit', $category) }}" variant="ghost" icon="pencil-square" size="sm" wire:navigate />
                                <form method="POST" action="{{ route('platform.communications.admin.categories.destroy', $category) }}" onsubmit="return confirm('¿Eliminar esta categoría?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button type="submit" variant="ghost" color="red" icon="trash" size="sm" />
                                </form>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" align="center" class="py-12">
                            <flux:text>No hay categorías registradas.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
