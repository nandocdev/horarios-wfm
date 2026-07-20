<div class="space-y-6">
    <x-wfm.page-header title="Base de Conocimiento" description="Resuelve dudas de pacientes con procedimientos y políticas al instante.">
        <x-slot:actions>
            <x-wfm.live-indicator label="Operativo" color="success" />
            @can('create', App\Modules\KnowledgeModule\Models\KnowledgeArticle::class)
                <flux:button href="{{ route('knowledge.admin') }}" wire:navigate variant="ghost" icon="cog-6-tooth">Administración Editorial</flux:button>
            @endcan
        </x-slot:actions>
    </x-wfm.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="lg:col-span-1 space-y-4">
            <x-wfm.section title="Canal / Cola Activa">
                <x-slot:actions>
                    @if($selectedQueueId)
                        <button wire:click="selectQueue(null)" class="text-[10px] text-wfm-surface-muted hover:text-wfm-navy-700 hover:underline">Todas</button>
                    @endif
                </x-slot:actions>
                <div class="space-y-0.5 max-h-[380px] overflow-y-auto">
                    <button wire:click="selectQueue(null)" class="wfm-sidebar-item w-full text-left {{ !$selectedQueueId ? 'wfm-sidebar-item-active' : 'wfm-sidebar-item-inactive' }}">
                        <flux:icon.inbox class="w-3.5 h-3.5" />
                        <span>Mostrar Todo</span>
                    </button>
                    @foreach($queues as $q)
                        <button wire:click="selectQueue({{ $q->id }})" class="wfm-sidebar-item w-full text-left {{ $selectedQueueId == $q->id ? 'wfm-sidebar-item-active' : 'wfm-sidebar-item-inactive' }}">
                            <flux:icon.queue-list class="w-3.5 h-3.5" />
                            <span class="truncate">{{ $q->name }}</span>
                        </button>
                    @endforeach
                </div>
            </x-wfm.section>

            <x-wfm.section title="Filtrar por Categoría">
                <div class="space-y-0.5">
                    <button wire:click="$set('selectedCategoryId', null)" class="wfm-sidebar-item w-full text-left {{ !$selectedCategoryId ? 'wfm-sidebar-item-active' : 'wfm-sidebar-item-inactive' }}">
                        Cualquier categoría
                    </button>
                    @foreach($categories as $category)
                        <button wire:click="$set('selectedCategoryId', {{ $category->id }})" class="wfm-sidebar-item w-full text-left {{ $selectedCategoryId == $category->id ? 'wfm-sidebar-item-active' : 'wfm-sidebar-item-inactive' }}">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </x-wfm.section>
        </div>

        <div class="lg:col-span-3 space-y-4">
            <x-wfm.section>
                <div class="flex flex-col md:flex-row gap-3 items-start md:items-center justify-between">
                    <div class="w-full flex-1">
                        <flux:input wire:model.live.debounce.300ms="search" placeholder="Busca por palabra clave, código o tag..." class="w-full" />
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if($selectedQueueId || $selectedCategoryId || $selectedTag || $search)
                            <flux:button wire:click="resetFilters" size="sm" variant="ghost">Restaurar Todo</flux:button>
                        @endif
                        <span class="text-xs text-wfm-surface-muted font-mono">{{ $articles->count() }} resultados</span>
                    </div>
                </div>
            </x-wfm.section>

            @if($tags->isNotEmpty())
                <div class="flex flex-wrap items-center gap-1.5 p-3 rounded border border-wfm-surface-border bg-wfm-surface/50">
                    <span class="text-[10px] kpi-label mr-1">Tags Rápidos:</span>
                    @foreach($tags as $t)
                        <button wire:click="$set('selectedTag', '{{ $t->name }}')"
                            class="text-[10px] font-mono transition-colors px-2 py-0.5 rounded {{ $selectedTag === $t->name ? 'bg-wfm-navy-800 text-white font-bold' : 'bg-white border border-wfm-surface-border text-wfm-surface-muted hover:bg-wfm-surface-hover' }}">
                            #{{ $t->name }}
                        </button>
                    @endforeach
                </div>
            @endif

            @if($selectedQueue && empty($search))
                <x-wfm.section :title="$selectedQueue->name" description="Procedimientos e información asignados a esta cola.">
                    <span class="float-right text-[10px] bg-wfm-surface px-2 py-0.5 rounded font-mono">Prioridad: {{ $selectedQueue->priority }}</span>

                    @if($groupedArticles && $groupedArticles->count() > 0)
                        <div class="space-y-6">
                            @foreach($groupedArticles as $categoryName => $catArticles)
                                <div>
                                    <div class="flex items-center gap-2 border-b border-wfm-surface-border pb-1.5 mb-3">
                                        <span class="text-sm font-semibold text-wfm-navy-800 dark:text-white">{{ $categoryName }}</span>
                                        <span class="text-[10px] bg-wfm-surface text-wfm-surface-muted px-2 py-0.5 rounded font-mono">{{ $catArticles->count() }}</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach($catArticles as $article)
                                            <div class="card-wfm p-3 hover:shadow-md transition-shadow border-l-4 border-l-wfm-navy-500">
                                                <div class="flex items-center justify-between text-[10px] text-wfm-surface-muted mb-1">
                                                    <span class="font-mono">v{{ $article->version }}</span>
                                                    <span>{{ $article->published_at ? $article->published_at->format('d/m/Y') : 'N/A' }}</span>
                                                </div>
                                                <a href="{{ route('knowledge.show', $article->slug) }}" wire:navigate class="text-sm font-semibold text-wfm-navy-800 dark:text-white hover:text-wfm-navy-500">{{ $article->title }}</a>
                                                <p class="text-xs text-wfm-surface-muted mt-1 line-clamp-3">{{ $article->summary ?: Str::limit(strip_tags($article->content), 120) }}</p>
                                                <div class="flex items-center justify-between mt-3 pt-2 border-t border-wfm-surface-border">
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($article->tags->take(3) as $t)
                                                            <button wire:click="$set('selectedTag', '{{ $t->name }}')" class="text-[10px] bg-wfm-surface text-wfm-surface-muted hover:text-wfm-navy-700 px-1.5 py-0.5 rounded font-mono">#{{ $t->name }}</button>
                                                        @endforeach
                                                    </div>
                                                    <flux:button href="{{ route('knowledge.show', $article->slug) }}" size="xs" variant="ghost" wire:navigate>Leer</flux:button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-wfm.empty icon="document-text" message="Sin publicaciones asociadas" description="No hay artículos para la cola seleccionada." />
                    @endif
                </x-wfm.section>
            @else
                <div class="space-y-3">
                    @forelse($articles as $article)
                        <div class="card-wfm p-3 flex flex-col md:flex-row md:items-center justify-between gap-3 hover:shadow-md transition-shadow">
                            <div class="flex-1 space-y-2">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <x-wfm.agent-status status="available" :label="$article->category ? $article->category->name : 'General'" size="xs" />
                                    @foreach($article->queues as $q)
                                        <span class="text-[10px] bg-wfm-info/10 text-wfm-info px-1.5 py-0.5 rounded font-medium">{{ $q->name }}</span>
                                    @endforeach
                                    <span class="text-[10px] bg-wfm-surface text-wfm-surface-muted px-1.5 py-0.5 rounded font-mono">Prio: {{ $article->priority }}</span>
                                </div>
                                <a href="{{ route('knowledge.show', $article->slug) }}" wire:navigate class="text-sm font-semibold text-wfm-navy-800 dark:text-white hover:text-wfm-navy-500">{{ $article->title }}</a>
                                <p class="text-xs text-wfm-surface-muted leading-relaxed">{{ $article->summary ?: Str::limit(strip_tags($article->content), 160) }}</p>
                                <div class="flex flex-wrap gap-1.5 pt-1 border-t border-wfm-surface-border/30">
                                    @foreach($article->tags as $t)
                                        <button wire:click="$set('selectedTag', '{{ $t->name }}')" class="text-[10px] text-wfm-surface-muted hover:text-wfm-navy-700 font-mono">#{{ $t->name }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="flex md:flex-col items-end justify-between md:justify-center border-t md:border-t-0 md:border-l border-wfm-surface-border pt-3 md:pt-0 md:pl-4 min-w-[110px] gap-2 shrink-0">
                                <span class="text-[10px] text-wfm-surface-muted font-mono">v{{ $article->version }}</span>
                                <flux:button href="{{ route('knowledge.show', $article->slug) }}" size="sm" variant="primary" wire:navigate>Ver Detalle</flux:button>
                            </div>
                        </div>
                    @empty
                        <x-wfm.empty icon="magnifying-glass" message="No se encontraron artículos" description="Intenta ajustando los filtros o escribe otra palabra clave." />
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</div>
