<div class="max-w-7xl mx-auto px-4 sm:px-4 lg:px-8 py-8 space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Gestión de Base de Conocimiento</flux:heading>
            <flux:subheading>Administra el contenido, flujo editorial y las relaciones de prioridad de los artículos.</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button href="{{ route('knowledge.index') }}" wire:navigate variant="ghost" icon="arrow-left" size="sm">
                Ir a Operación
            </flux:button>
            <flux:button href="{{ route('knowledge.create') }}" wire:navigate variant="primary" icon="plus" size="sm">
                Nuevo Artículo
            </flux:button>
        </div>
    </div>

    {{-- Filtros y Búsqueda --}}
    <flux:card class="p-4 bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="md:col-span-2">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por título o contenido..." icon="magnifying-glass" clearable />
            </div>
            <div>
                <select wire:model.live="selectedStatus" class="w-full text-sm rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white p-2">
                    <option value="">Todos los Estados</option>
                    <option value="draft">Borrador</option>
                    <option value="review">En Revisión</option>
                    <option value="published">Publicado</option>
                    <option value="archived">Archivado</option>
                </select>
            </div>
            <div>
                <select wire:model.live="selectedCategory" class="w-full text-sm rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white p-2">
                    <option value="">Todas las Categorías</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </flux:card>

    {{-- Tabla de Artículos --}}
    <flux:card class="bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <flux:table :paginate="$articles">
            <flux:table.columns>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Artículo</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Categoría</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Colas Relacionadas</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Estado</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Versión</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Creador</flux:table.column>
                <flux:table.column align="end" class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($articles as $article)
                    <flux:table.row :key="$article->id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 py-2">
                        <flux:table.cell class="py-2 max-w-xs truncate font-medium">
                            <div>
                                <flux:text class="font-semibold text-slate-900 dark:text-white">{{ $article->title }}</flux:text>
                                @if($article->summary)
                                    <flux:text size="xs" class="text-slate-400 dark:text-slate-500 line-clamp-1">{{ $article->summary }}</flux:text>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="py-2">
                            <flux:badge size="sm" color="zinc" variant="subtle">
                                {{ $article->category ? $article->category->name : 'Sin Categoría' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="py-2 max-w-[200px]">
                            <div class="flex flex-wrap gap-1">
                                @forelse($article->queues as $q)
                                    <span class="text-[10px] bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-1.5 py-0.5 rounded font-medium">
                                        {{ $q->name }}
                                    </span>
                                @empty
                                    <span class="text-xs text-slate-400">Ninguna</span>
                                @endforelse
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="py-2">
                            @php
                                $statusColors = [
                                    'draft' => 'zinc',
                                    'review' => 'amber',
                                    'published' => 'green',
                                    'archived' => 'red',
                                ];
                                $statusLabels = [
                                    'draft' => 'Borrador',
                                    'review' => 'Revisión',
                                    'published' => 'Publicado',
                                    'archived' => 'Archivado',
                                ];
                            @endphp
                            <flux:badge :color="$statusColors[$article->status] ?? 'zinc'" size="sm" variant="subtle">
                                {{ $statusLabels[$article->status] ?? $article->status }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="py-2">
                            <flux:text size="sm" class="font-mono text-slate-500 dark:text-slate-400">v{{ $article->version }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell class="py-2">
                            <flux:text size="sm" class="text-slate-600 dark:text-slate-300">{{ $article->creator ? $article->creator->name : 'N/A' }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="py-2">
                            <div class="flex justify-end gap-1">
                                <flux:button href="{{ route('knowledge.show', $article->slug) }}" target="_blank" variant="ghost" icon="eye" size="sm" />
                                <flux:button href="{{ route('knowledge.edit', $article->id) }}" wire:navigate variant="ghost" icon="pencil-square" size="sm" />
                                <flux:button wire:click="deleteArticle({{ $article->id }})" 
                                    wire:confirm="¿Estás seguro de que deseas eliminar permanentemente este artículo?"
                                    variant="ghost" color="red" icon="trash" size="sm" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 py-2">
                        <flux:table.cell class="py-2" colspan="7" align="center" class="py-12">
                            <flux:icon name="document-text" size="lg" class="opacity-20 mb-2" />
                            <flux:text class="text-slate-500">No se encontraron artículos registrados.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
            {{ $articles->links() }}
        </div>
    </flux:card>
</div>
