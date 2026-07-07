<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Panel de Encuestas</flux:heading>
            <flux:subheading>Gestiona las encuestas operativas para el personal.</flux:subheading>
        </div>
        <flux:button href="{{ route('communications.polls.create') }}" variant="primary" icon="plus" wire:navigate>
            Nueva Encuesta
        </flux:button>
    </div>

    <flux:card>
        <div class="mb-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar encuesta..." icon="magnifying-glass" />
        </div>

        <flux:table :paginate="$polls">
            <flux:table.columns>
                <flux:table.column>ID</flux:table.column>
                <flux:table.column>Pregunta</flux:table.column>
                <flux:table.column>Participación</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($polls as $item)
                    <flux:table.row :key="$item->id">
                        <flux:table.cell>{{ $item->id }}</flux:table.cell>
                        <flux:table.cell class="max-w-xs truncate">{{ $item->question }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:text size="sm" class="font-medium">{{ $item->responses_count }} votos</flux:text>
                        </flux:table.cell>
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
                                <flux:modal.trigger name="results-poll-{{ $item->id }}">
                                    <flux:button variant="ghost" icon="chart-bar" size="sm" tooltip="Ver Resultados" />
                                </flux:modal.trigger>

                                <flux:modal name="results-poll-{{ $item->id }}" class="md:w-[500px]">
                                    <div class="space-y-4">
                                        <div>
                                            <flux:heading size="lg">{{ $item->question }}</flux:heading>
                                            <flux:subheading>Resultados acumulados de la encuesta operativa.</flux:subheading>
                                        </div>

                                        <div class="space-y-4">
                                            @php
                                                $totalVotes = collect($item->options)->sum(fn($opt) => $opt['votes'] ?? 0);
                                            @endphp
                                            
                                            @foreach($item->options as $option)
                                                @php
                                                    $votes = $option['votes'] ?? 0;
                                                    $percentage = $totalVotes > 0 ? round(($votes / $totalVotes) * 100) : 0;
                                                @endphp
                                                <div class="space-y-1">
                                                    <div class="flex justify-between text-sm font-medium">
                                                        <span>{{ $option['text'] }}</span>
                                                        <span>{{ $percentage }}% ({{ $votes }})</span>
                                                    </div>
                                                    <div class="w-full bg-zinc-100 rounded-full h-2.5 dark:bg-zinc-800">
                                                        <div class="bg-{{ $option['color'] ?? 'blue' }}-500 h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                                                    </div>
                                                </div>
                                            @endforeach

                                            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700 flex justify-between items-center">
                                                <flux:text size="sm" class="font-medium">Total de participaciones:</flux:text>
                                                <flux:badge size="sm" color="zinc" variant="solid">{{ $totalVotes }} votos</flux:badge>
                                            </div>
                                        </div>

                                        <div class="flex">
                                            <flux:spacer />
                                            <flux:modal.close>
                                                <flux:button variant="ghost">Cerrar</flux:button>
                                            </flux:modal.close>
                                        </div>
                                    </div>
                                </flux:modal>

                                <flux:button href="#" variant="ghost" icon="pencil-square" size="sm" disabled />
                                
                                @if($item->status === 'published')
                                    <flux:button wire:click="archivePoll({{ $item->id }})" 
                                        wire:confirm="¿Estás seguro de cerrar esta encuesta? Ya no se podrán recibir más votos."
                                        variant="ghost" icon="archive-box" size="sm" tooltip="Cerrar Encuesta" />
                                @endif

                                <flux:button wire:click="deletePoll({{ $item->id }})" 
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
        </flux:table>

        <div class="mt-4">
            {{ $polls->links() }}
        </div>
    </flux:card>
</div>
