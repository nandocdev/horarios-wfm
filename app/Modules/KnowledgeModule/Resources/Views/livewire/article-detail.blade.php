<div class="max-w-7xl mx-auto px-4 sm:px-4 lg:px-8 py-8 space-y-8">
    
    {{-- Breadcrumbs de Navegación --}}
    <div class="text-xs text-zinc-400 dark:text-zinc-500 flex items-center gap-1.5 font-medium">
        <a href="{{ route('knowledge.index') }}" wire:navigate class="hover:text-indigo-600 transition-opacity">Base de Conocimiento</a>
        <span>/</span>
        <span class="text-zinc-500 dark:text-zinc-400">{{ $article->category ? $article->category->name : 'General' }}</span>
        <span>/</span>
        <span class="text-zinc-650 dark:text-zinc-300 truncate max-w-xs font-semibold">{{ $article->title }}</span>
    </div>

    {{-- Encabezado de Página --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-4">
        <div class="space-y-1.5">
            <div class="flex flex-wrap items-center gap-2">
                <flux:badge size="sm" color="zinc" variant="subtle" class="font-semibold text-xs">
                    {{ $article->category ? $article->category->name : 'General' }}
                </flux:badge>
                @php
                    $statusColors = [
                        'draft' => 'zinc',
                        'review' => 'orange',
                        'published' => 'green',
                        'archived' => 'rose',
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
                <flux:badge size="xs" color="rose" class="font-mono">Prioridad Operativa: {{ $article->priority }}</flux:badge>
            </div>
            <flux:heading size="2xl" class="text-zinc-900 dark:text-white font-extrabold tracking-tight leading-tight">{{ $article->title }}</flux:heading>
        </div>

        <div class="flex gap-2 shrink-0">
            <flux:button href="{{ route('knowledge.index') }}" wire:navigate variant="ghost" icon="arrow-left" size="sm">
                Regresar a Operación
            </flux:button>
            @can('update', $article)
                <flux:button href="{{ route('knowledge.edit', $article->id) }}" wire:navigate variant="ghost" icon="pencil-square" size="sm">
                    Editar Artículo
                </flux:button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- Bloque Principal: Documento y Auditoría (2/3) --}}
        <div class="lg:col-span-2 space-y-4">
            
            {{-- Documento con Botón de Copiado Rápido --}}
            <flux:card x-data="{ copied: false }" class="p-4 bg-white dark:bg-zinc-800 shadow-sm border border-zinc-200 dark:border-zinc-700 relative group">
                
                {{-- Botón interactivo de copiado rápido (Excelente UX para operadores de chat) --}}
                <div class="absolute right-4 top-4 opacity-70 group-hover:opacity-100 transition-opacity">
                    <button 
                        @click="navigator.clipboard.writeText($refs.articleContent.innerText); copied = true; setTimeout(() => copied = false, 2000)" 
                        type="button"
                        class="flex items-center gap-1 text-xs bg-zinc-50 dark:bg-zinc-700 hover:bg-indigo-50 dark:hover:bg-zinc-650 text-zinc-600 dark:text-zinc-300 border border-zinc-250 dark:border-zinc-600 px-2.5 py-1.5 rounded-md transition-opacity font-semibold"
                    >
                        <flux:icon name="document-duplicate" size="xs" class="size-3.5" />
                        <span x-text="copied ? '¡Copiado!' : 'Copiar Contenido'"></span>
                    </button>
                </div>

                @if($article->summary)
                    <div class="mb-6 p-4 bg-zinc-50/70 dark:bg-zinc-900/40 rounded-md border-l-4 border-zinc-400 text-zinc-600 dark:text-zinc-300 italic text-sm leading-relaxed">
                        <strong class="block not-italic text-xs text-zinc-450 dark:text-zinc-500 uppercase tracking-wider font-bold mb-1">Resumen Operativo:</strong>
                        {{ $article->summary }}
                    </div>
                @endif

                {{-- Cuerpo del documento --}}
                <div x-ref="articleContent" class="prose dark:prose-invert max-w-none text-zinc-800 dark:text-zinc-200 leading-relaxed space-y-4 pt-6 md:pt-4">
                    {!! $article->content !!}
                </div>
            </flux:card>

            {{-- Historial de Versiones (Auditoría Lineal) --}}
            <flux:card class="p-4 bg-white dark:bg-zinc-800 shadow-sm border border-zinc-200 dark:border-zinc-700">
                <flux:heading size="lg" class="mb-6 text-zinc-900 dark:text-white font-bold pb-2 border-b border-zinc-50 dark:border-zinc-700">Historial y Auditoría de Cambios</flux:heading>
                
                <div class="relative border-l-2 border-zinc-100 dark:border-zinc-700 ml-4 space-y-4">
                    @foreach($article->versions->sortByDesc('version') as $ver)
                        <div class="relative pl-6">
                            <span class="absolute -left-[7px] top-1.5 flex h-3 w-3 items-center justify-center rounded-full bg-indigo-50 dark:bg-zinc-850">
                                <span class="h-1.5 w-1.5 rounded-full {{ $ver->version == $article->version ? 'bg-green-500' : 'bg-zinc-400 dark:bg-zinc-600' }}"></span>
                            </span>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                <span class="font-mono text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                                    Versión v{{ $ver->version }} 
                                    @if($ver->version == $article->version)
                                        <span class="ml-2 text-[10px] bg-green-50 dark:bg-green-950/30 text-green-700 dark:text-green-400 px-2 py-0.5 rounded border border-green-200 dark:border-green-800/30 font-semibold font-sans">Activa en Operación</span>
                                    @endif
                                </span>
                                <span class="text-xs text-zinc-400 dark:text-zinc-500 font-mono">
                                    {{ $ver->created_at->format('d/m/Y H:i:s') }}
                                </span>
                            </div>
                            <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400 mt-1">
                                Autorizado por: <strong class="text-zinc-650 dark:text-zinc-300">{{ $ver->creator ? $ver->creator->name : 'N/A' }}</strong>
                            </flux:text>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        </div>

        {{-- Barra lateral derecha: Metadatos y Clasificación (1/3) --}}
        <div class="lg:col-span-1 space-y-4">
            
            {{-- Detalles técnicos --}}
            <flux:card class="p-5 bg-white dark:bg-zinc-800 shadow-sm border border-zinc-200 dark:border-zinc-700 space-y-4">
                <flux:heading size="md" class="border-b border-zinc-100 dark:border-zinc-700 pb-2 text-zinc-800 dark:text-zinc-200 font-bold">Detalles de Publicación</flux:heading>

                <div class="space-y-4">
                    <div class="flex justify-between items-center pb-2 border-b border-zinc-50 dark:border-zinc-750/30">
                        <flux:text size="xs" class="text-zinc-400 dark:text-zinc-500 uppercase font-semibold">Versión</flux:text>
                        <flux:text size="sm" class="font-mono font-bold text-zinc-800 dark:text-zinc-200">v{{ $article->version }}</flux:text>
                    </div>

                    <div class="flex justify-between items-center pb-2 border-b border-zinc-50 dark:border-zinc-750/30">
                        <flux:text size="xs" class="text-zinc-400 dark:text-zinc-500 uppercase font-semibold">Creado Por</flux:text>
                        <flux:text size="sm" class="text-zinc-800 dark:text-zinc-200 font-medium">{{ $article->creator ? $article->creator->name : 'N/A' }}</flux:text>
                    </div>

                    @if($article->updater)
                        <div class="flex justify-between items-center pb-2 border-b border-zinc-50 dark:border-zinc-750/30">
                            <flux:text size="xs" class="text-zinc-400 dark:text-zinc-500 uppercase font-semibold">Última Edición</flux:text>
                            <flux:text size="sm" class="text-zinc-800 dark:text-zinc-200 font-medium">{{ $article->updater->name }}</flux:text>
                        </div>
                    @endif

                    <div class="flex justify-between items-center pb-2 border-b border-zinc-50 dark:border-zinc-750/30">
                        <flux:text size="xs" class="text-zinc-400 dark:text-zinc-500 uppercase font-semibold">Publicación</flux:text>
                        <flux:text size="sm" class="text-zinc-850 dark:text-zinc-200">
                            {{ $article->published_at ? $article->published_at->format('d/m/Y H:i') : 'Inmediata' }}
                        </flux:text>
                    </div>

                    @if($article->expires_at)
                        <div class="flex justify-between items-center pb-2 border-b border-zinc-50 dark:border-zinc-750/30">
                            <flux:text size="xs" class="text-zinc-400 dark:text-zinc-550 uppercase font-semibold">Expiración</flux:text>
                            <flux:text size="sm" class="text-rose-600 dark:text-rose-400 font-medium">
                                {{ $article->expires_at->format('d/m/Y H:i') }}
                            </flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- Colas asociadas --}}
            <flux:card class="p-5 bg-white dark:bg-zinc-800 shadow-sm border border-zinc-200 dark:border-zinc-700 space-y-3">
                <flux:heading size="md" class="border-b border-zinc-100 dark:border-zinc-700 pb-2 text-zinc-800 dark:text-zinc-200 font-bold">Colas Asignadas</flux:heading>
                <div class="flex flex-col gap-2">
                    @forelse($article->queues as $q)
                        <div class="flex items-center justify-between text-xs bg-zinc-50 dark:bg-zinc-750 p-2.5 rounded-md border border-zinc-200/50 dark:border-zinc-700/50">
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">📞 {{ $q->name }}</span>
                            <span class="text-[10px] bg-zinc-200 dark:bg-zinc-600 text-zinc-600 dark:text-zinc-200 px-1.5 py-0.5 rounded font-mono">P:{{ $q->priority }}</span>
                        </div>
                    @empty
                        <flux:text size="sm" class="text-zinc-400">Sin colas de atención asignadas.</flux:text>
                    @endforelse
                </div>
            </flux:card>

            {{-- Etiquetas --}}
            <flux:card class="p-5 bg-white dark:bg-zinc-800 shadow-sm border border-zinc-200 dark:border-zinc-700 space-y-3">
                <flux:heading size="md" class="border-b border-zinc-100 dark:border-zinc-700 pb-2 text-zinc-800 dark:text-zinc-200 font-bold">Etiquetas de Búsqueda</flux:heading>
                <div class="flex flex-wrap gap-1.5">
                    @forelse($article->tags as $t)
                        <span class="text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-650 dark:text-zinc-300 px-2.5 py-1 rounded font-mono">
                            #{{ $t->name }}
                        </span>
                    @empty
                        <flux:text size="sm" class="text-zinc-400">Sin etiquetas cargadas.</flux:text>
                    @endforelse
                </div>
            </flux:card>
        </div>
    </div>
</div>
