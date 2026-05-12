<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Panel de Noticias</flux:heading>
            <flux:subheading>Gestiona el contenido informativo de la plataforma.</flux:subheading>
        </div>
        <flux:button href="{{ route('communications.news.create') }}" variant="primary" icon="plus" wire:navigate>
            Nueva Noticia
        </flux:button>
    </div>

    <flux:card>
        <div class="mb-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar noticia..." icon="magnifying-glass" />
        </div>

        <flux:table :paginate="$news">
            <flux:table.columns>
                <flux:table.column>ID</flux:table.column>
                <flux:table.column>Título</flux:table.column>
                <flux:table.column>Autor</flux:table.column>
                <flux:table.column>Publicación</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($news as $item)
                    <flux:table.row :key="$item->id">
                        <flux:table.cell>{{ $item->id }}</flux:table.cell>
                        <flux:table.cell class="max-w-xs truncate">{{ $item->title }}</flux:table.cell>
                        <flux:table.cell>{{ $item->author->name }}</flux:table.cell>
                        <flux:table.cell>{{ $item->published_at->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-col gap-1">
                                @php
                                    $statusColor = match($item->status) {
                                        'draft' => 'slate',
                                        'pending_review' => 'orange',
                                        'published' => 'green',
                                        'archived' => 'red',
                                        default => 'slate',
                                    };
                                    $statusLabel = match($item->status) {
                                        'draft' => 'Borrador',
                                        'pending_review' => 'En Revisión',
                                        'published' => 'Publicada',
                                        'archived' => 'Archivada',
                                        default => $item->status,
                                    };
                                @endphp
                                <flux:badge :color="$statusColor" size="sm" variant="subtle">
                                    {{ $statusLabel }}
                                </flux:badge>
                                
                                @if(!$item->is_active)
                                    <flux:badge color="red" size="xs" variant="outline">Inactiva</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-2">
                                <flux:button href="{{ route('communications.news.edit', $item) }}" variant="ghost" icon="pencil-square" size="sm" wire:navigate />
                                <flux:button wire:click="deleteNews({{ $item->id }})" 
                                    wire:confirm="¿Estás seguro de eliminar esta noticia? Esta acción no se puede deshacer."
                                    variant="ghost" color="red" icon="trash" size="sm" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" align="center" class="py-12">
                            <flux:text>No hay noticias registradas que coincidan con la búsqueda.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $news->links() }}
        </div>
    </flux:card>
</div>
