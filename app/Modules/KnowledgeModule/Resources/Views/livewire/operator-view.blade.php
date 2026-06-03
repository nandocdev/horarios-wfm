<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    {{-- Encabezado Principal --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-zinc-50 dark:bg-zinc-800/50 p-6 rounded-xl border border-zinc-250/60 dark:border-zinc-700">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <flux:badge size="sm" color="indigo" class="font-bold">OPERACIÓN</flux:badge>
                <flux:text size="xs" class="text-zinc-400 dark:text-zinc-550">Actualizado en tiempo real</flux:text>
            </div>
            <flux:heading size="2xl" class="text-zinc-900 dark:text-white font-bold">Base de Conocimiento</flux:heading>
            <flux:subheading class="text-zinc-500 dark:text-zinc-400 text-sm">Resuelve dudas de pacientes con procedimientos y políticas al instante.</flux:subheading>
        </div>
        
        @can('create', App\Modules\KnowledgeModule\Models\Article::class)
            <flux:button href="{{ route('knowledge.admin') }}" wire:navigate icon="cog-6-tooth" size="sm" variant="ghost">
                Administración Editorial
            </flux:button>
        @endcan
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        {{-- Panel Izquierdo: Selector de Colas y Filtros --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Colas de Atención --}}
            <flux:card class="p-5 bg-white dark:bg-zinc-800 shadow-sm border border-zinc-200 dark:border-zinc-700 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-700">
                    <flux:text weight="semibold" class="text-zinc-800 dark:text-zinc-200 flex items-center gap-2 text-sm">
                        <flux:icon name="queue-list" size="sm" class="text-indigo-500" />
                        Canal / Cola Activa
                    </flux:text>
                    @if($selectedQueueId)
                        <button wire:click="selectQueue(null)" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 hover:underline">Todas</button>
                    @endif
                </div>

                <div class="space-y-1 max-h-[380px] overflow-y-auto pr-1">
                    <flux:navlist class="space-y-1">
                        <flux:navlist.item 
                            wire:click="selectQueue(null)" 
                            :current="!$selectedQueueId"
                            class="cursor-pointer transition-all hover:bg-zinc-55 px-3 py-2 rounded-lg text-sm"
                        >
                            📞 Mostrar Todo
                        </flux:navlist.item>

                        @foreach($queues as $q)
                            <flux:navlist.item 
                                wire:click="selectQueue({{ $q->id }})" 
                                :current="$selectedQueueId == $q->id"
                                class="cursor-pointer transition-all hover:bg-zinc-55 px-3 py-2 rounded-lg text-sm flex items-center justify-between group"
                            >
                                <span class="truncate flex items-center gap-1.5">
                                    📞 {{ $q->name }}
                                </span>                               
                            </flux:navlist.item>
                        @endforeach
                    </flux:navlist>
                </div>
            </flux:card>

            {{-- Filtrado por Categoría Rápido --}}
            <flux:card class="p-5 bg-white dark:bg-zinc-800 shadow-sm border border-zinc-200 dark:border-zinc-700 space-y-3">
                <flux:text weight="semibold" class="text-zinc-800 dark:text-zinc-200 text-sm block">Filtrar por Categoría</flux:text>
                <div class="grid grid-cols-1 gap-1">
                    <button wire:click="$set('selectedCategoryId', null)" 
                            class="text-left text-xs px-3 py-1.5 rounded transition-all {{ !$selectedCategoryId ? 'bg-zinc-100 dark:bg-zinc-700 font-bold text-zinc-900 dark:text-white' : 'text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-700/50' }}">
                        Cualquier categoría
                    </button>
                    @foreach($categories as $category)
                        <button wire:click="$set('selectedCategoryId', {{ $category->id }})" 
                                class="text-left text-xs px-3 py-1.5 rounded transition-all flex items-center justify-between {{ $selectedCategoryId == $category->id ? 'bg-zinc-100 dark:bg-zinc-700 font-bold text-zinc-900 dark:text-white' : 'text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-700/50' }}">
                            <span>{{ $category->name }}</span>
                        </button>
                    @endforeach
                </div>
            </flux:card>
        </div>

        {{-- Panel Derecho: Resultados de Búsqueda --}}
        <div class="lg:col-span-3 space-y-6">
            {{-- Barra de búsqueda superior --}}
            <flux:card class="p-4 bg-white dark:bg-zinc-800 shadow-sm border border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row gap-4 items-center justify-between">
                <div class="w-full flex-1">
                    <flux:input 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Busca por palabra clave, código o tag (ej: cancelar cita laboratorio)..." 
                        icon="magnifying-glass"
                        clearable
                        class="w-full"
                    />
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @if($selectedQueueId || $selectedCategoryId || $selectedTag || $search)
                        <flux:button wire:click="resetFilters" size="sm" variant="ghost" class="text-zinc-500 hover:text-zinc-800">
                            Restaurar Todo
                        </flux:button>
                    @endif
                    <div class="text-xs text-zinc-400 dark:text-zinc-500 font-mono">
                        {{ $articles->count() }} resultados
                    </div>
                </div>
            </flux:card>

            {{-- Historial de Etiquetas Activas --}}
            @if($tags->count() > 0)
                <div class="flex flex-wrap items-center gap-2 bg-zinc-50/50 dark:bg-zinc-800/30 p-3 rounded-lg border border-zinc-100 dark:border-zinc-700/50">
                    <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400 font-semibold uppercase tracking-wider mr-1">Tags Rápidos:</flux:text>
                    @foreach($tags as $t)
                        <button 
                            wire:click="$set('selectedTag', '{{ $t->name }}')" 
                            class="text-xs font-mono transition-all px-2.5 py-1 rounded {{ $selectedTag === $t->name ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-white dark:bg-zinc-800 border border-zinc-250 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-zinc-750' }}"
                        >
                            #{{ $t->name }}
                        </button>
                    @endforeach
                </div>
            @endif

            @if($selectedQueue && empty($search))
                {{-- Vista Especial Agrupada para la Cola Seleccionada --}}
                <div class="space-y-6">
                    <div class="bg-indigo-50/80 dark:bg-zinc-800/80 p-5 rounded-xl flex items-center justify-between gap-4 border border-indigo-100 dark:border-zinc-700 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">📞</span>
                            <div>
                                <flux:heading size="lg" class="text-indigo-950 dark:text-indigo-200 font-bold">{{ $selectedQueue->name }}</flux:heading>
                                <flux:subheading class="text-indigo-750 dark:text-zinc-400 text-xs">Procedimientos e información obligatoria asignados a esta cola operativa.</flux:subheading>
                            </div>
                        </div>
                        <flux:badge size="md" color="indigo" class="font-mono font-bold">Prioridad: {{ $selectedQueue->priority }}</flux:badge>
                    </div>

                    @if($groupedArticles && $groupedArticles->count() > 0)
                        <div class="space-y-8">
                            @foreach($groupedArticles as $categoryName => $catArticles)
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-700 pb-2">
                                        <flux:heading size="md" class="text-zinc-900 dark:text-zinc-150 font-bold">{{ $categoryName }}</flux:heading>
                                        <span class="text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-500 px-2 py-0.5 rounded-full font-mono">{{ $catArticles->count() }}</span>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($catArticles as $article)
                                            <flux:card class="p-5 hover:shadow-md transition-all duration-300 flex flex-col justify-between border-l-4 border-l-indigo-500 bg-white dark:bg-zinc-800 hover:-translate-y-0.5 shadow-sm">
                                                <div class="space-y-2">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[10px] text-zinc-400 font-mono">v{{ $article->version }}</span>
                                                        <span class="text-[10px] text-zinc-400 dark:text-zinc-500">Publicado: {{ $article->published_at ? $article->published_at->format('d/m/Y') : 'N/A' }}</span>
                                                    </div>
                                                    <flux:heading size="lg" class="font-bold text-zinc-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                                                        <a href="{{ route('knowledge.show', $article->slug) }}" wire:navigate>
                                                            {{ $article->title }}
                                                        </a>
                                                    </flux:heading>
                                                    <flux:text size="sm" class="text-zinc-600 dark:text-zinc-300 line-clamp-3">
                                                        {{ $article->summary ?: Str::limit(strip_tags($article->content), 120) }}
                                                    </flux:text>
                                                </div>

                                                <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($article->tags->take(3) as $t)
                                                            <span wire:click="$set('selectedTag', '{{ $t->name }}')" class="cursor-pointer text-[10px] bg-zinc-50 dark:bg-zinc-700/50 text-zinc-500 hover:text-indigo-600 px-2 py-0.5 rounded font-mono">
                                                                #{{ $t->name }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                    <flux:button href="{{ route('knowledge.show', $article->slug) }}" size="xs" variant="ghost" icon-trailing="arrow-right" wire:navigate class="text-indigo-600">Leer</flux:button>
                                                </div>
                                            </flux:card>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-16 flex flex-col items-center justify-center text-zinc-500 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl bg-zinc-50/20">
                            <flux:icon name="document-text" size="xl" class="mb-3 opacity-20" />
                            <flux:text class="font-medium text-zinc-700 dark:text-zinc-300">Sin publicaciones asociadas</flux:text>
                            <flux:text class="text-xs text-zinc-400 mt-1">No hay artículos vigentes cargados para la cola seleccionada.</flux:text>
                        </div>
                    @endif
                </div>
            @else
                {{-- Listado de Resultados General / Búsqueda --}}
                <div class="space-y-4">
                    @forelse($articles as $article)
                        <flux:card class="p-6 bg-white dark:bg-zinc-800 shadow-sm border border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row md:items-center justify-between gap-6 hover:shadow-md transition-all duration-300">
                            <div class="space-y-2.5 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:badge size="sm" color="zinc" variant="subtle" class="font-semibold text-xs">
                                        {{ $article->category ? $article->category->name : 'General' }}
                                    </flux:badge>
                                    
                                    @foreach($article->queues as $q)
                                        <span class="text-[10px] bg-indigo-50 dark:bg-zinc-700 text-indigo-600 dark:text-indigo-300 px-2 py-0.5 rounded-full font-medium">
                                            📞 {{ $q->name }}
                                        </span>
                                    @endforeach

                                    <flux:badge size="xs" color="rose" class="font-mono">
                                        Prio: {{ $article->priority }}
                                    </flux:badge>
                                </div>

                                <flux:heading size="lg" class="text-zinc-900 dark:text-white font-bold">
                                    <a href="{{ route('knowledge.show', $article->slug) }}" wire:navigate class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        {{ $article->title }}
                                    </a>
                                </flux:heading>

                                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-300 leading-relaxed">
                                    {{ $article->summary ?: Str::limit(strip_tags($article->content), 160) }}
                                </flux:text>

                                <div class="flex flex-wrap gap-2 pt-1 border-t border-zinc-50 dark:border-zinc-700/30">
                                    @foreach($article->tags as $t)
                                        <button wire:click="$set('selectedTag', '{{ $t->name }}')" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-mono">
                                            #{{ $t->name }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex md:flex-col items-end justify-between md:justify-center border-t md:border-t-0 md:border-l border-zinc-100 dark:border-zinc-700 pt-4 md:pt-0 md:pl-6 min-w-[130px] gap-2 shrink-0">
                                <flux:text size="xs" class="text-zinc-400 dark:text-zinc-550 font-mono">Versión v{{ $article->version }}</flux:text>
                                <flux:button href="{{ route('knowledge.show', $article->slug) }}" size="sm" variant="primary" icon-trailing="arrow-right" wire:navigate>
                                    Ver Detalle
                                </flux:button>
                            </div>
                        </flux:card>
                    @empty
                        <div class="py-20 flex flex-col items-center justify-center text-zinc-500 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl bg-zinc-50/20">
                            <flux:icon name="magnifying-glass" size="xl" class="mb-3 opacity-20 text-zinc-400" />
                            <flux:text class="font-bold text-zinc-700 dark:text-zinc-300 text-lg">No se encontraron artículos</flux:text>
                            <flux:text class="text-xs text-zinc-400 mt-1">Intenta ajustando los filtros laterales o escribe otra palabra clave.</flux:text>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</div>
