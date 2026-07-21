<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $config['title'] }}</flux:heading>
            <flux:subheading>{{ $config['description'] }}</flux:subheading>
        </div>
        <flux:button href="{{ route($config['create_route']) }}" variant="primary" icon="plus" wire:navigate>
            {{ $config['create_label'] }}
        </flux:button>
    </div>

    <flux:card>
        <div class="mb-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar..." icon="magnifying-glass" />
        </div>

        @php $type = $contentType; @endphp

        <flux:table :paginate="$items">
            @if($type === 'news')
                <flux:table.columns>
                    <flux:table.column>ID</flux:table.column>
                    <flux:table.column>Título</flux:table.column>
                    <flux:table.column>Autor</flux:table.column>
                    <flux:table.column>Publicación</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column align="end">Acciones</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($items as $item)
                        <flux:table.row :key="$item->id">
                            <flux:table.cell>{{ $item->id }}</flux:table.cell>
                            <flux:table.cell class="max-w-xs truncate">{{ $item->title }}</flux:table.cell>
                            <flux:table.cell>{{ $item->author?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $item->published_at?->format('d/m/Y H:i') ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $statusColor = match($item->status) {
                                        'draft' => 'slate',
                                        'pending_review' => 'amber',
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
                                <div class="flex flex-col gap-2">
                                    <flux:badge :color="$statusColor" size="sm" variant="subtle">{{ $statusLabel }}</flux:badge>
                                    @if(!$item->is_active)
                                        <flux:badge color="red" size="xs" variant="outline">Inactiva</flux:badge>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-2">
                                    <flux:button href="{{ route('communications.news.edit', $item) }}" variant="ghost" icon="pencil-square" size="sm" wire:navigate />
                                    <flux:button wire:click="delete({{ $item->id }})"
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

            @elseif($type === 'polls')
                <flux:table.columns>
                    <flux:table.column>ID</flux:table.column>
                    <flux:table.column>Pregunta</flux:table.column>
                    <flux:table.column>Participación</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column align="end">Acciones</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($items as $item)
                        <flux:table.row :key="$item->id">
                            <flux:table.cell>{{ $item->id }}</flux:table.cell>
                            <flux:table.cell class="max-w-xs truncate">{{ $item->question }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:text size="sm" class="font-medium">{{ $item->responses_count }} votos</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $statusColor = match($item->status) {
                                        'draft' => 'slate',
                                        'pending_review' => 'amber',
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
                                <div class="flex flex-col gap-2">
                                    <flux:badge :color="$statusColor" size="sm" variant="subtle">{{ $statusLabel }}</flux:badge>
                                    @if(!$item->is_active)
                                        <flux:badge color="red" size="xs" variant="outline">Inactiva</flux:badge>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-2">
                                    <flux:modal.trigger name="results-poll-{{ $item->id }}">
                                        <flux:button variant="ghost" icon="chart-bar" size="sm" tooltip="Ver Resultados" />
                                    </flux:modal.trigger>

                                    <flux:modal name="results-poll-{{ $item->id }}" class="md:max-w-lg">
                                        <flux:modal.header>
                                            <flux:heading size="lg">Resultados: {{ $item->question }}</flux:heading>
                                        </flux:modal.header>
                                        <flux:modal.body class="space-y-4">
                                            @foreach($item->options as $option)
                                                @php
                                                    $total = $item->responses_count ?: 1;
                                                    $votes = $option['votes'] ?? 0;
                                                    $pct = round(($votes / $total) * 100);
                                                @endphp
                                                <div>
                                                    <div class="flex justify-between text-sm mb-1">
                                                        <span>{{ $option['text'] }}</span>
                                                        <span class="font-mono text-wfm-surface-muted">{{ $votes }} votos ({{ $pct }}%)</span>
                                                    </div>
                                                    <div class="w-full bg-wfm-surface rounded-full h-2">
                                                        <div class="h-2 rounded-full" style="width:{{ $pct }}%; background-color: {{ $option['color'] ?? '#3b82f6' }}"></div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            <flux:text class="text-xs text-wfm-surface-muted">Total: {{ $item->responses_count }} votos</flux:text>
                                        </flux:modal.body>
                                        <flux:modal.footer>
                                            <flux:modal.close>
                                                <flux:button variant="ghost">Cerrar</flux:button>
                                            </flux:modal.close>
                                        </flux:modal.footer>
                                    </flux:modal>

                                    @if($item->status === 'published')
                                        <flux:button wire:click="archive({{ $item->id }})"
                                            wire:confirm="¿Estás seguro de cerrar esta encuesta? No se recibirán más respuestas."
                                            variant="ghost" icon="archive-box" size="sm" tooltip="Cerrar Encuesta" />
                                    @endif
                                    <flux:button wire:click="delete({{ $item->id }})"
                                        wire:confirm="¿Estás seguro de eliminar esta encuesta?"
                                        variant="ghost" color="red" icon="trash" size="sm" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" align="center" class="py-12">
                                <flux:text>No hay encuestas registradas.</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>

            @elseif($type === 'shoutouts')
                <flux:table.columns>
                    <flux:table.column>ID</flux:table.column>
                    <flux:table.column>Empleado</flux:table.column>
                    <flux:table.column>Contenido</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column align="end">Acciones</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($items as $item)
                        <flux:table.row :key="$item->id">
                            <flux:table.cell>{{ $item->id }}</flux:table.cell>
                            <flux:table.cell>{{ $item->employee?->full_name ?? 'N/A' }}</flux:table.cell>
                            <flux:table.cell class="max-w-xs truncate">{{ $item->message }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $statusColor = match($item->status) {
                                        'draft' => 'slate',
                                        'pending_review' => 'amber',
                                        'published' => 'green',
                                        'archived' => 'red',
                                        default => 'slate',
                                    };
                                    $statusLabel = match($item->status) {
                                        'draft' => 'Borrador',
                                        'pending_review' => 'En Revisión',
                                        'published' => 'Publicado',
                                        'archived' => 'Archivado',
                                        default => $item->status,
                                    };
                                @endphp
                                <flux:badge :color="$statusColor" size="sm" variant="subtle">{{ $statusLabel }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-2">
                                    @if($item->canBeEdited())
                                        <flux:button href="{{ route('communications.shoutouts.edit', $item) }}"
                                            variant="ghost" icon="pencil-square" size="sm" wire:navigate />
                                    @endif
                                    <flux:button wire:click="delete({{ $item->id }})"
                                        wire:confirm="¿Estás seguro de eliminar este reconocimiento?"
                                        variant="ghost" color="red" icon="trash" size="sm" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" align="center" class="py-12">
                                <flux:text>No hay reconocimientos registrados.</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            @endif
        </flux:table>

        <div class="mt-4">{{ $items->links() }}</div>
    </flux:card>
</div>
