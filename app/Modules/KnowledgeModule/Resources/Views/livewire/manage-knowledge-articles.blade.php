<div class="space-y-6">
    <x-wfm.page-header title="Gestión de Base de Conocimiento" description="Administra el contenido, flujo editorial y las relaciones de prioridad de los artículos.">
        <x-slot:actions>
            <flux:button href="{{ route('knowledge.index') }}" wire:navigate variant="ghost" icon="arrow-left">Ir a Operación</flux:button>
            <flux:button href="{{ route('knowledge.create') }}" wire:navigate variant="primary" icon="plus">Nuevo Artículo</flux:button>
        </x-slot:actions>
        <x-slot:filters>
            <x-wfm.filter-bar>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por título o contenido..." class="!w-64" />
                <flux:select wire:model.live="selectedStatus" placeholder="Estado" class="!w-40">
                    <option value="">Todos los Estados</option>
                    <option value="draft">Borrador</option>
                    <option value="review">En Revisión</option>
                    <option value="published">Publicado</option>
                    <option value="archived">Archivado</option>
                </flux:select>
                <flux:select wire:model.live="selectedCategory" placeholder="Categoría" class="!w-44">
                    <option value="">Todas</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </flux:select>
            </x-wfm.filter-bar>
        </x-slot:filters>
    </x-wfm.page-header>

    <x-wfm.table :headers="['Artículo', 'Categoría', 'Colas Relacionadas', 'Estado', 'Versión', 'Creador', 'Acciones']" compact>
        @forelse($articles as $article)
            <flux:table.row :key="$article->id">
                <flux:table.cell class="max-w-xs truncate font-medium">
                    <p class="text-sm font-semibold text-wfm-navy-800 dark:text-white">{{ $article->title }}</p>
                    @if($article->summary)
                        <p class="text-xs text-wfm-surface-muted line-clamp-1">{{ $article->summary }}</p>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    <flux:badge size="sm" color="zinc">{{ $article->category ? $article->category->name : 'Sin Categoría' }}</flux:badge>
                </flux:table.cell>
                <flux:table.cell class="max-w-[200px]">
                    <div class="flex flex-wrap gap-1">
                        @forelse($article->queues as $q)
                            <span class="text-[10px] bg-wfm-surface text-wfm-navy-700 px-1.5 py-0.5 rounded font-medium">{{ $q->name }}</span>
                        @empty
                            <span class="text-xs text-wfm-surface-muted">Ninguna</span>
                        @endforelse
                    </div>
                </flux:table.cell>
                <flux:table.cell>
                    <x-wfm.agent-status :status="match($article->status) { 'published' => 'available', 'review' => 'break', 'draft' => 'offline', 'archived' => 'busy', default => 'offline' }" :label="match($article->status) { 'published' => 'Publicado', 'review' => 'Revisión', 'draft' => 'Borrador', 'archived' => 'Archivado', default => $article->status }" size="xs" />
                </flux:table.cell>
                <flux:table.cell>
                    <span class="font-mono text-xs text-wfm-surface-muted">v{{ $article->version }}</span>
                </flux:table.cell>
                <flux:table.cell class="text-xs text-wfm-navy-700">{{ $article->creator ? $article->creator->name : 'N/A' }}</flux:table.cell>
                <flux:table.cell class="text-right">
                    <flux:button href="{{ route('knowledge.show', $article->slug) }}" target="_blank" variant="ghost" icon="eye" size="sm" />
                    <flux:button href="{{ route('knowledge.edit', $article->id) }}" wire:navigate variant="ghost" icon="pencil-square" size="sm" />
                    <flux:button wire:click="deleteArticle({{ $article->id }})" wire:confirm="¿Eliminar permanentemente este artículo?" variant="ghost" size="sm" icon="trash" />
                </flux:table.cell>
            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="7">
                    <x-wfm.empty icon="document-text" message="No se encontraron artículos registrados." />
                </flux:table.cell>
            </flux:table.row>
        @endforelse
    </x-wfm.table>

    <div class="mt-4">{{ $articles->links() }}</div>
</div>
