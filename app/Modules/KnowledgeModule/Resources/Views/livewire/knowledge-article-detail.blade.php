<div class="space-y-6">
    <x-wfm.page-header :title="$article->title" description="Base de Conocimiento">
        <x-slot:actions>
            <flux:button href="{{ route('knowledge.index') }}" wire:navigate variant="ghost" icon="arrow-left">Regresar a Operación</flux:button>
            @can('update', $article)
                <flux:button href="{{ route('knowledge.edit', $article->id) }}" wire:navigate variant="ghost" icon="pencil-square">Editar Artículo</flux:button>
            @endcan
        </x-slot:actions>
    </x-wfm.page-header>

    <div class="flex items-center gap-2 text-xs text-wfm-surface-muted">
        <a href="{{ route('knowledge.index') }}" wire:navigate class="hover:text-wfm-navy-700">Base de Conocimiento</a>
        <span>/</span>
        <span>{{ $article->category ? $article->category->name : 'General' }}</span>
        <span>/</span>
        <span class="text-wfm-navy-700 font-semibold truncate max-w-xs">{{ $article->title }}</span>
        <x-wfm.agent-status :status="match($article->status) { 'published' => 'available', 'review' => 'break', 'draft' => 'offline', 'archived' => 'busy', default => 'offline' }" :label="match($article->status) { 'published' => 'Publicado', 'review' => 'Revisión', 'draft' => 'Borrador', 'archived' => 'Archivado', default => $article->status }" size="xs" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <x-wfm.section>
                <div x-data="{ copied: false }" class="relative">
                    <div class="absolute right-0 top-0 opacity-70 hover:opacity-100 transition-opacity">
                        <button @click="navigator.clipboard.writeText($refs.content.innerText); copied = true; setTimeout(() => copied = false, 2000)"
                            class="flex items-center gap-1 text-xs bg-wfm-surface hover:bg-wfm-surface-hover text-wfm-navy-700 border border-wfm-surface-border px-2 py-1 rounded transition-colors font-medium">
                            <flux:icon.document-duplicate class="w-3.5 h-3.5" />
                            <span x-text="copied ? '¡Copiado!' : 'Copiar'"></span>
                        </button>
                    </div>

                    @if($article->summary)
                        <div class="mb-4 p-3 bg-wfm-surface/70 rounded border-l-4 border-wfm-navy-400 text-xs text-wfm-navy-700 italic leading-relaxed">
                            <strong class="block not-italic text-[10px] uppercase tracking-wider font-bold mb-1">Resumen:</strong>
                            {{ $article->summary }}
                        </div>
                    @endif

                    <div x-ref="content" class="prose dark:prose-invert max-w-none text-sm leading-relaxed pt-6">
                        {!! $article->content !!}
                    </div>
                </div>
            </x-wfm.section>

            @if($article->versions->isNotEmpty())
                <x-wfm.section title="Historial de Versiones">
                    <div class="relative border-l-2 border-wfm-surface-border ml-3 space-y-3">
                        @foreach($article->versions->sortByDesc('version') as $ver)
                            <div class="relative pl-5">
                                <span class="absolute -left-[6px] top-1.5 flex h-3 w-3 items-center justify-center rounded-full bg-wfm-surface">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $ver->version == $article->version ? 'bg-wfm-success' : 'bg-wfm-surface-muted' }}"></span>
                                </span>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                    <span class="font-mono text-xs font-semibold text-wfm-navy-800 dark:text-white">
                                        v{{ $ver->version }}
                                        @if($ver->version == $article->version)
                                            <span class="ml-1 text-[10px] bg-wfm-success/10 text-wfm-success px-1.5 py-0.5 rounded border border-wfm-success/20 font-semibold font-sans">Activa</span>
                                        @endif
                                    </span>
                                    <span class="text-[10px] text-wfm-surface-muted font-mono">{{ $ver->created_at->format('d/m/Y H:i:s') }}</span>
                                </div>
                                <p class="text-[10px] text-wfm-surface-muted mt-0.5">Por: <strong class="text-wfm-navy-700">{{ $ver->creator ? $ver->creator->name : 'N/A' }}</strong></p>
                            </div>
                        @endforeach
                    </div>
                </x-wfm.section>
            @endif
        </div>

        <div class="space-y-4">
            <x-wfm.section title="Detalles">
                <div class="space-y-2">
                    <div class="flex justify-between text-xs pb-1.5 border-b border-wfm-surface-border">
                        <span class="kpi-label">Versión</span>
                        <span class="font-mono font-semibold text-wfm-navy-800">v{{ $article->version }}</span>
                    </div>
                    <div class="flex justify-between text-xs pb-1.5 border-b border-wfm-surface-border">
                        <span class="kpi-label">Creado Por</span>
                        <span class="font-medium text-wfm-navy-800">{{ $article->creator ? $article->creator->name : 'N/A' }}</span>
                    </div>
                    @if($article->updater)
                        <div class="flex justify-between text-xs pb-1.5 border-b border-wfm-surface-border">
                            <span class="kpi-label">Última Edición</span>
                            <span class="font-medium text-wfm-navy-800">{{ $article->updater->name }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-xs pb-1.5 border-b border-wfm-surface-border">
                        <span class="kpi-label">Publicación</span>
                        <span class="text-wfm-navy-800">{{ $article->published_at ? $article->published_at->format('d/m/Y H:i') : 'Inmediata' }}</span>
                    </div>
                    @if($article->expires_at)
                        <div class="flex justify-between text-xs pb-1.5 border-b border-wfm-surface-border">
                            <span class="kpi-label">Expiración</span>
                            <span class="text-wfm-danger font-medium">{{ $article->expires_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </x-wfm.section>

            <x-wfm.section title="Colas Asignadas">
                @forelse($article->queues as $q)
                    <div class="flex items-center justify-between text-xs bg-wfm-surface p-2 rounded border border-wfm-surface-border mb-1.5 last:mb-0">
                        <span class="font-medium text-wfm-navy-700">
                            <flux:icon.queue-list class="w-3 h-3 inline mr-1" />
                            {{ $q->name }}
                        </span>
                        <span class="text-[10px] bg-wfm-surface-hover text-wfm-navy-500 px-1.5 py-0.5 rounded font-mono">P:{{ $q->priority }}</span>
                    </div>
                @empty
                    <p class="text-xs text-wfm-surface-muted">Sin colas asignadas.</p>
                @endforelse
            </x-wfm.section>

            <x-wfm.section title="Etiquetas">
                <div class="flex flex-wrap gap-1.5">
                    @forelse($article->tags as $t)
                        <span class="text-xs bg-wfm-surface text-wfm-navy-700 px-2 py-0.5 rounded font-mono">#{{ $t->name }}</span>
                    @empty
                        <p class="text-xs text-wfm-surface-muted">Sin etiquetas.</p>
                    @endforelse
                </div>
            </x-wfm.section>
        </div>
    </div>
</div>
