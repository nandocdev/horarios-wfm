<div class="flex w-full flex-col">
    <!-- Hero Section: Dinamizada con el mejor Shoutout o Mensaje de Bienvenida -->
    <section data-tour="comms-hero" class="relative overflow-hidden border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <div class="absolute inset-x-0 top-0 h-64 bg-slate-100 dark:bg-slate-900"></div>

        <div class="relative mx-auto max-w-[85rem] px-4 py-8 sm:px-4 lg:px-8 lg:py-16">
            <div class="absolute top-4 right-4 z-10">
                <x-wfm.tour-button :tour="'communications.home'" />
            </div>
            <div class="grid gap-8 lg:grid-cols-[1.4fr_0.6fr] lg:items-center">
                <div class="space-y-8">
                    @if($featuredShoutout)
                        <div
                            class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-black uppercase tracking-widest animate-pulse">
                            <flux:icon name="sparkles" class="w-4 h-4" />
                            Reconocimiento del Mes
                        </div>

                        <div class="space-y-4">
                            <flux:text class="text-base">
                                "{{ $featuredShoutout->message }}"
                            </flux:text>

                            <div class="flex items-center gap-4 py-2">
                                <flux:avatar src="{{ $featuredShoutout->employee->user?->avatar_url }}"
                                    name="{{ $featuredShoutout->employee->full_name }}" size="lg" />
                                <div>
                                    <flux:text class="text-lg font-bold text-slate-900 dark:text-white">
                                        {{ $featuredShoutout->employee->full_name }}</flux:text>
                                    <flux:text class="text-sm text-slate-500">
                                        {{ $featuredShoutout->employee->position->name ?? 'Colaborador' }}</flux:text>
                                </div>
                            </div>
                        </div>
                    @else
                        <div
                            class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-black uppercase tracking-widest">
                            <flux:icon name="megaphone" class="w-4 h-4" />
                            Canal Oficial de Comunicaciones
                        </div>

                        <div class="space-y-4">
                            <flux:heading size="xl" class="text-4xl lg:text-6xl font-black leading-tight">
                                Conecta con tu <span
                                    class="text-slate-900 dark:text-white">Comunidad
                                    Operativa</span>
                            </flux:heading>
                            <flux:text class="text-lg max-w-2xl text-slate-600 dark:text-slate-400">
                                Un espacio diseñado para mantenerte al tanto de las novedades, reconocer el talento de tus
                                compañeros y participar en la toma de decisiones.
                            </flux:text>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-4 pt-4">
                        <flux:button wire:click="openShoutoutModal" variant="primary" size="lg" icon="plus"
                            class="shadow-md shadow-slate-900/10">
                            Publicar Reconocimiento
                        </flux:button>
                        <flux:button variant="outline" size="lg" href="{{ route('filesystem.download-center') }}" icon="folder-arrow-down" wire:navigate>
                            Centro de Descargas
                        </flux:button>
                        <flux:button variant="ghost" size="lg" href="#noticias">
                            Explorar Novedades
                        </flux:button>
                    </div>
                </div>

                <div class="hidden lg:block relative">
                    <div
                        class="absolute -inset-4 rounded-md bg-slate-200 dark:bg-slate-800 opacity-20">
                    </div>
                    <flux:card
                        class="relative overflow-hidden p-2 shadow-md border-white dark:border-slate-800 rotate-2 hover:rotate-0 transition-transform duration-150">
                        <img src="{{ ($featuredShoutout && $featuredShoutout->hasMedia('banner')) ? $featuredShoutout->getFirstMediaUrl('banner') : (($featuredShoutout && $featuredShoutout->employee->user?->avatar_url) ? $featuredShoutout->employee->user->avatar_url : asset('img/comms-hero.jpg')) }}"
                            alt="Comunicaciones"
                            class="aspect-square w-full rounded-md object-cover grayscale-[0.2] hover:grayscale-0 transition-opacity duration-150">
                        <div class="absolute inset-0 bg-black/40"></div>
                        @if($featuredShoutout)
                            <div class="absolute bottom-6 left-6 right-6 text-center">
                                <flux:text class="text-white font-bold italic">#OrgulloCSS</flux:text>
                            </div>
                        @endif
                    </flux:card>
                </div>
            </div>
        </div>
    </section>

    <!-- Contenido Principal -->
    <div class="mx-auto w-full max-w-[85rem] px-4 py-12 sm:px-4 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-3">
            <div id="noticias" data-tour="comms-news" class="space-y-8 lg:col-span-2">
                <section class="space-y-8">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between border-b border-slate-100 dark:border-slate-800 pb-6">
                        <div>
                            <flux:heading size="xl" level="2" class="font-black">Noticias Internas</flux:heading>
                            <flux:subheading>Actualizaciones y novedades del Centro de Contactos.</flux:subheading>
                        </div>

                        <flux:button href="{{ route('communications.news.index') }}" variant="ghost"
                            icon-trailing="chevron-right" size="sm" wire:navigate>
                            Archivo Histórico
                        </flux:button>
                    </div>

                    <div class="grid gap-8 md:grid-cols-2">
                        @forelse ($newsItems as $news)
                            <flux:card
                                class="group flex flex-col overflow-hidden p-0 transition-opacity duration-150 hover:shadow-md hover:shadow-slate-200/50 border-none bg-slate-50/50 dark:bg-slate-800/30">
                                <div class="relative h-56 w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                                    <img src="{{ $news->getFirstMediaUrl('featured_image') ?: asset('img/news-placeholder.png') }}"
                                        alt="{{ $news->title }}"
                                        class="h-full w-full object-cover transition duration-150 group-hover:scale-110">
                                    <div
                                        class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-150 flex flex-col justify-end p-4">
                                        <flux:button wire:click="viewNews({{ $news->id }})" variant="primary" size="sm"
                                            class="w-full">
                                            Leer Artículo Completo
                                        </flux:button>
                                    </div>
                                    <div class="absolute top-4 left-4">
                                        <flux:badge color="slate" size="sm"
                                            class="bg-white/90 dark:bg-slate-900/90">
                                            {{ $news->categories->first()->name ?? 'General' }}
                                        </flux:badge>
                                    </div>
                                </div>
                                <div class="flex flex-1 flex-col p-4">
                                    <flux:text class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                                        {{ $news->published_at->translatedFormat('d M, Y') }}</flux:text>

                                    <button wire:click="viewNews({{ $news->id }})"
                                        class="text-left group-hover:text-blue-600 transition-opacity">
                                        <flux:heading size="lg" class="line-clamp-2 leading-tight font-black">
                                            {{ $news->title }}</flux:heading>
                                    </button>

                                    <flux:text
                                        class="mt-3 line-clamp-3 text-sm leading-relaxed flex-1 text-slate-500 dark:text-slate-400">
                                        {{ $news->excerpt ?: str($news->content)->limit(120) }}
                                    </flux:text>

                                    <div
                                        class="mt-6 flex items-center justify-between border-t border-slate-100 dark:border-slate-700/50 pt-4">
                                        <div class="flex items-center gap-2">
                                            <flux:button wire:click="toggleComments({{ $news->id }})" variant="subtle"
                                                size="xs" icon="chat-bubble-left">
                                                {{ $news->comments_count }}
                                            </flux:button>
                                            <flux:button wire:click="selectNewsForComment({{ $news->id }})" variant="ghost"
                                                size="xs" icon="plus">
                                                Comentar
                                            </flux:button>
                                        </div>

                                        <div class="flex -space-x-2">
                                            @foreach($news->comments->take(3) as $comment)
                                                <flux:avatar src="{{ $comment->user->avatar_url }}"
                                                    name="{{ $comment->user->name }}" size="xs"
                                                    class="border-2 border-white dark:border-slate-800" />
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Formulario de comentario simplificado -->
                                    @if($isAuthenticated && $commentForm['news_id'] === $news->id)
                                        <div class="mt-4 animate-fade-in">
                                            <form wire:submit="submitComment" class="space-y-3">
                                                <flux:textarea wire:model="commentForm.content"
                                                    placeholder="Escribe tu opinión..." rows="2" size="sm" required />
                                                <div class="flex gap-2">
                                                    <flux:button type="submit" variant="primary" size="xs">Enviar</flux:button>
                                                    <flux:button wire:click="$set('commentForm.news_id', null)" variant="ghost"
                                                        size="xs">Cerrar</flux:button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    <!-- Comentarios -->
                                    @if($showComments && $selectedNewsId === $news->id && $selectedNews)
                                        <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-700">
                                            <flux:heading size="sm" class="mb-3">Comentarios</flux:heading>
                                            @unless($isAuthenticated)
                                                <flux:text class="mb-3 text-xs text-slate-500">
                                                    Modo lectura activa. Inicia sesión para participar en la conversación.
                                                </flux:text>
                                            @endunless
                                            <div class="space-y-3">
                                                @forelse($selectedNews->comments->where('is_active', true) as $comment)
                                                    <div class="flex gap-4">
                                                        <div class="flex-shrink-0">
                                                            <div
                                                                class="h-8 w-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                                                                <span
                                                                    class="text-xs font-medium">{{ substr($comment->user->name, 0, 2) }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2">
                                                                <flux:text class="font-medium text-sm">{{ $comment->user->name }}
                                                                </flux:text>
                                                                <flux:text class="text-xs text-slate-500">
                                                                    {{ $comment->created_at->diffForHumans() }}</flux:text>
                                                            </div>
                                                            <flux:text class="text-sm mt-1">{{ $comment->content }}</flux:text>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <flux:text class="text-sm text-slate-500 italic">No hay comentarios aún.
                                                    </flux:text>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </flux:card>
                        @empty
                            <flux:card
                                class="col-span-full flex flex-col items-center justify-center border-dashed py-12 text-center">
                                <flux:heading size="md">No hay noticias recientes</flux:heading>
                                <flux:subheading class="mt-1">Las novedades operativas aparecerán aquí.</flux:subheading>
                            </flux:card>
                        @endforelse
                    </div>
                </section>
            </div>

            <!-- COLUMNA DERECHA: Sidebar -->
            <aside class="space-y-8">
                <!-- Accesos Rápidos Premium -->
                <flux:card class="bg-slate-900 text-white border-none shadow-md">
                    <flux:heading size="lg" class="text-white">Accesos Rápidos</flux:heading>
                    <flux:subheading class="text-slate-400 mb-8">Herramientas esenciales.</flux:subheading>

                    <div class="grid grid-cols-2 gap-4">
                        @php
                            $quickLinks = [
                                ['icon' => 'calendar', 'label' => 'Horarios', 'color' => 'slate', 'route' => 'schedules.my-schedule'],
                                ['icon' => 'chart-pie', 'label' => 'Métricas', 'color' => 'blue', 'route' => 'dashboard'],
                                ['icon' => 'lifebuoy', 'label' => 'Soporte', 'color' => 'slate', 'route' => 'helpdesk.my-tickets'],
                                ['icon' => 'folder-arrow-down', 'label' => 'Descargas', 'color' => 'amber', 'route' => 'filesystem.download-center'],
                            ];
                        @endphp
                        @foreach ($quickLinks as $item)
                            <flux:button href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}" wire:navigate
                                class="flex flex-col items-center gap-4 p-4 h-auto rounded-md bg-white/5 hover:bg-white/10 transition-opacity group border-none">
                                <div
                                    class="w-10 h-10 rounded-md bg-{{ $item['color'] }}-500/20 flex items-center justify-center text-{{ $item['color'] }}-400 group-hover:scale-110 transition-transform mb-1">
                                    <flux:icon name="{{ $item['icon'] }}" class="w-5 h-5" />
                                </div>
                                <span
                                    class="text-[10px] font-black uppercase tracking-widest text-slate-300">{{ $item['label'] }}</span>
                            </flux:button>
                        @endforeach
                    </div>
                </flux:card>

                <!-- Encuesta Dinámica -->
                @if($activePoll)
                    <flux:card data-tour="comms-encuesta" class="overflow-hidden relative border-none shadow-md">
                        <div class="absolute top-0 right-0 p-4 opacity-5">
                            <flux:icon name="presentation-chart-line" class="w-20 h-20" />
                        </div>
                        <flux:heading size="lg" class="font-black">Voz del Operador</flux:heading>
                        <flux:subheading class="mb-8 leading-relaxed">{{ $activePoll->question }}</flux:subheading>

                        @if($isAuthenticated)
                            @if($hasVotedInActivePoll)
                                <div class="space-y-4">
                                    @php
                                        $totalVotes = collect($activePoll->options)->sum(fn($opt) => $opt['votes'] ?? 0);
                                    @endphp
                                    @foreach($activePoll->options as $option)
                                        @php
                                            $votes = $option['votes'] ?? 0;
                                            $percentage = $totalVotes > 0 ? round(($votes / $totalVotes) * 100) : 0;
                                        @endphp
                                        <div class="space-y-2">
                                            <div class="flex justify-between text-xs font-bold text-slate-600 dark:text-slate-400">
                                                <span>{{ $option['text'] }}</span>
                                                <span>{{ $percentage }}%</span>
                                            </div>
                                            <div class="w-full bg-slate-100 rounded-full h-1.5 dark:bg-slate-800 overflow-hidden">
                                                <div class="bg-blue-600 h-full rounded-full transition-opacity duration-150"
                                                    style="width: {{ $percentage }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-center">
                                        <flux:badge color="green" size="sm">¡Voto registrado!</flux:badge>
                                    </div>
                                </div>
                            @else
                                <form wire:submit="submitPoll" class="space-y-4">
                                    <flux:radio.group wire:model="pollForm.answer" variant="cards" class="flex-col gap-2">
                                        @foreach($activePoll->options as $option)
                                            <flux:radio value="{{ $option['text'] }}" label="{{ $option['text'] }}" class="text-sm" />
                                        @endforeach
                                    </flux:radio.group>

                                    <flux:button type="submit" variant="primary" class="w-full shadow-md shadow-slate-900/10">
                                        Enviar Voto
                                    </flux:button>
                                </form>
                            @endif
                        @else
                            <div class="space-y-4 text-center py-4">
                                <flux:text class="text-sm text-slate-500">Inicia sesión para participar en esta encuesta y ver
                                    los resultados reales.</flux:text>
                                <flux:button href="{{ route('login') }}" wire:navigate variant="outline" class="w-full">
                                    Iniciar Sesión
                                </flux:button>
                            </div>
                        @endif
                    </flux:card>
                @endif
            </aside>
        </div>
    </div>

    <!-- Sección de Shoutouts: Estilo Feed Social -->
    <section class="bg-slate-50 dark:bg-slate-900/50 border-y border-slate-200 dark:border-slate-800">
        <div class="mx-auto w-full max-w-[85rem] px-4 py-16 sm:px-4 lg:px-8">
            <div class="mb-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <flux:heading size="xl" class="font-black italic">Shout-outs ⚡</flux:heading>
                    <flux:subheading>Reconocimientos rápidos que impulsan la cultura del equipo.</flux:subheading>
                </div>

                <flux:button wire:click="openShoutoutModal" icon="plus" variant="primary" class="rounded-md px-8">
                    Dar un Reconocimiento
                </flux:button>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($shoutoutItems as $shoutout)
                    @php
                        $hasBanner = $shoutout->hasMedia('banner');
                    @endphp
                    <flux:card
                        class="relative flex flex-col p-4 transition-opacity duration-150 hover:shadow-md hover:-translate-y-1 overflow-hidden border-none {{ $hasBanner ? 'text-white' : 'bg-white dark:bg-slate-800' }}">
                        
                        @if($hasBanner)
                            <img src="{{ $shoutout->getFirstMediaUrl('banner') }}" class="absolute inset-0 w-full h-full object-cover z-0">
                            <div class="absolute inset-0 bg-black/70 z-10"></div>
                        @endif

                        <div class="relative z-20 flex flex-col h-full">
                            <div class="mb-8 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <flux:avatar src="{{ $shoutout->employee->user?->avatar_url }}"
                                        name="{{ $shoutout->employee->full_name }}" 
                                        class="ring-2 {{ $hasBanner ? 'ring-white/20' : 'ring-slate-200' }}" />
                                    <div>
                                        <flux:heading size="sm" class="font-black {{ $hasBanner ? 'text-white' : '' }}">
                                            {{ $shoutout->employee->full_name }}
                                        </flux:heading>
                                        <flux:text class="text-[9px] font-black uppercase tracking-widest {{ $hasBanner ? 'text-slate-300' : 'text-slate-600' }}">
                                            {{ $shoutout->employee->team->name ?? 'Equipo' }}
                                        </flux:text>
                                    </div>
                                </div>
                                <flux:icon name="sparkles" class="w-5 h-5 text-amber-400 opacity-60" />
                            </div>

                            <div class="flex-1">
                                <flux:text class="text-base leading-relaxed italic {{ $hasBanner ? 'text-white font-medium drop-shadow-md' : 'text-slate-700 dark:text-slate-300' }}">
                                    "{{ $shoutout->message }}"
                                </flux:text>
                            </div>

                            <!-- Interacciones Premium -->
                            <div class="mt-8 pt-4 border-t {{ $hasBanner ? 'border-white/10' : 'border-slate-100 dark:border-slate-700' }} flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    @php
                                        $reactionTypes = ['like' => '👍', 'love' => '❤️', 'celebrate' => '🎉', 'support' => '🤝'];
                                    @endphp
                                    @foreach($reactionTypes as $type => $emoji)
                                        @php
                                            $count = $shoutout->reactions->where('type', $type)->count();
                                            $userReacted = in_array($type, $shoutout->user_reactions ?? []);
                                        @endphp
                                        <button wire:click="toggleReaction({{ $shoutout->id }}, '{{ $type }}')"
                                            @disabled(!$isAuthenticated)
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-bold transition-opacity {{ $userReacted ? 'bg-blue-600 text-white' : ($hasBanner ? 'bg-white/10 text-white hover:bg-white/20' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 hover:bg-slate-200') }}">
                                            {{ $emoji }} <span>{{ $count }}</span>
                                        </button>
                                    @endforeach
                                </div>
                                <flux:text class="text-[10px] font-bold uppercase tracking-widest {{ $hasBanner ? 'text-slate-300' : 'text-slate-400' }}">
                                    {{ $shoutout->created_at->diffForHumans(null, true) }}
                                </flux:text>
                            </div>
                        </div>
                    </flux:card>
                @empty
                    <div
                        class="col-span-full py-20 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-md">
                        <flux:icon name="users" class="w-12 h-12 mx-auto text-slate-300 mb-4" />
                        <flux:heading size="md">Cultura de equipo</flux:heading>
                        <flux:text class="mt-1">Sé el primero en reconocer el buen trabajo de un compañero.</flux:text>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Modal Detalle Noticia con Comentarios Integrados -->
    <flux:modal name="news-detail-modal" wire:model="showNewsModal" class="md:min-w-[55rem] p-0 overflow-hidden">
        @if ($viewingNews)
            <div class="grid lg:grid-cols-[1fr_350px] h-[85vh]">
                <!-- Lado Izquierdo: Contenido -->
                <div class="overflow-y-auto p-8 lg:p-12 space-y-8 custom-scrollbar">
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <flux:badge color="slate" size="sm" class="uppercase tracking-widest">
                                {{ $viewingNews->categories->first()->name ?? 'Novedades' }}</flux:badge>
                            <flux:text class="text-xs font-bold text-slate-400">
                                {{ $viewingNews->published_at->translatedFormat('d M, Y') }}</flux:text>
                        </div>
                        <flux:heading size="xl" class="text-3xl lg:text-4xl font-black leading-tight">
                            {{ $viewingNews->title }}</flux:heading>

                        <div class="flex items-center gap-4 pt-2">
                            <flux:avatar src="{{ $viewingNews->author?->avatar_url }}"
                                name="{{ $viewingNews->author?->name }}" size="sm" />
                            <div>
                                <flux:text class="text-sm font-bold">{{ $viewingNews->author?->name }}</flux:text>
                                <flux:text class="text-[10px] uppercase text-slate-500 font-black tracking-tighter">Autor del
                                    Artículo</flux:text>
                            </div>
                        </div>
                    </div>

                    @if($viewingNews->getFirstMediaUrl('featured_image'))
                        <div class="rounded-md overflow-hidden shadow-md">
                            <img src="{{ $viewingNews->getFirstMediaUrl('featured_image') }}"
                                class="w-full object-cover max-h-[400px]" />
                        </div>
                    @endif

                    <div class="prose prose-slate dark:prose-invert max-w-none">
                        {!! Str::markdown($viewingNews->content) !!}
                    </div>

                    @if ($viewingNews->hasMedia('attachments'))
                        <div class="pt-8 border-t border-slate-100 dark:border-slate-800 space-y-4">
                            <flux:heading size="sm" class="font-black uppercase tracking-widest text-slate-400">Archivos Adjuntos
                            </flux:heading>
                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach ($viewingNews->getMedia('attachments') as $media)
                                    <a href="{{ $media->getUrl() }}" download
                                        class="flex items-center gap-4 p-4 rounded-md bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 transition-opacity border border-slate-100 dark:border-slate-800 group">
                                        <flux:icon name="paper-clip" class="w-5 h-5 text-slate-400 group-hover:text-blue-600" />
                                        <div class="flex-1 min-w-0">
                                            <flux:text class="text-xs font-bold truncate">{{ $media->file_name }}</flux:text>
                                            <flux:text class="text-[10px] text-slate-500">{{ $media->human_readable_size }}
                                            </flux:text>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Lado Derecho: Conversación -->
                <div
                    class="bg-slate-50 dark:bg-slate-900/50 border-l border-slate-200 dark:border-slate-800 flex flex-col h-full">
                    <div
                        class="p-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between">
                        <flux:heading size="md" class="font-black">Conversación</flux:heading>
                        <flux:button wire:click="closeNewsModal" variant="ghost" icon="x-mark" size="xs" />
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar">
                        @forelse($viewingNews->comments as $comment)
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <flux:avatar src="{{ $comment->user->avatar_url }}" name="{{ $comment->user->name }}"
                                        size="xs" />
                                    <flux:text class="text-xs font-bold">{{ $comment->user->name }}</flux:text>
                                    <flux:text class="text-[10px] text-slate-400">
                                        {{ $comment->created_at->diffForHumans(null, true) }}</flux:text>
                                </div>
                                <div
                                    class="bg-white dark:bg-slate-800 p-3 rounded-md rounded-tl-none shadow-sm border border-slate-100 dark:border-slate-700">
                                    <flux:text class="text-sm leading-relaxed">{{ $comment->content }}</flux:text>
                                </div>
                            </div>
                        @empty
                            <div
                                class="h-full flex flex-col items-center justify-center text-center opacity-40 grayscale py-12">
                                <flux:icon name="chat-bubble-left-right" class="w-12 h-12 mb-4" />
                                <flux:text class="text-xs font-bold uppercase tracking-widest">Sin comentarios aún</flux:text>
                            </div>
                        @endforelse
                    </div>

                    @if($isAuthenticated)
                        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                            <form wire:submit="submitComment" class="space-y-3">
                                <input type="hidden" wire:model="commentForm.news_id">
                                <flux:textarea wire:model="commentForm.content" placeholder="Escribe tu opinión..." rows="3"
                                    variant="subtle" class="text-sm rounded-md" />
                                <flux:button type="submit" variant="primary" class="w-full rounded-md">Publicar Comentario
                                </flux:button>
                            </form>
                        </div>
                    @else
                        <div class="p-4 bg-slate-100 dark:bg-slate-800/50 text-center">
                            <flux:text class="text-xs text-slate-500 mb-4">Inicia sesión para participar.</flux:text>
                            <flux:button href="{{ route('login') }}" wire:navigate variant="outline" size="sm" class="w-full">
                                Entrar</flux:button>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </flux:modal>

    <!-- Modal para Publicar Reconocimiento -->
    <flux:modal name="shoutout-create-modal" wire:model="showShoutoutModal" class="md:min-w-[35rem]">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg" class="font-black italic">Publicar un Shout-out ⚡</flux:heading>
                <flux:subheading>Reconoce el excelente desempeño o actitud de un compañero.</flux:subheading>
            </div>

            <form wire:submit="submitShoutout" class="space-y-4">
                <flux:select wire:model="shoutoutForm.employee_id" label="¿A quién quieres reconocer?"
                    placeholder="Selecciona un compañero..." searchable>
                    @foreach($employees as $employee)
                        <flux:select.option value="{{ $employee->id }}">{{ $employee->full_name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="shoutoutForm.message" label="Tu Mensaje"
                    placeholder="Ej. Gracias por tu apoyo extra hoy en la campaña, ¡excelente trabajo en equipo!"
                    rows="4" maxlength="200" description="Máximo 200 caracteres." />

                <div class="flex gap-2 justify-end pt-4">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" class="px-8 shadow-md shadow-slate-900/10">
                        Enviar Reconocimiento
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>