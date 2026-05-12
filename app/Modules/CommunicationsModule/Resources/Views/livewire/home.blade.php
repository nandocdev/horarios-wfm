<div class="flex w-full flex-col">
    <!-- Hero Section: Empleado del Mes (Dinamizar pronto con un modelo Shoutout/Recognition) -->
    <section class="relative overflow-hidden border-b border-zinc-200 dark:border-zinc-800">
        <div class="absolute inset-x-0 top-0 h-48 bg-gradient-to-r from-cyan-800 to-cyan-500 to-transparent"></div>

        <div class="relative mx-auto max-w-[85rem] px-4 py-8 sm:px-6 lg:px-8 sm:py-10 lg:py-12">
            <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
                <flux:card class="shadow-sm">
                    <flux:badge color="blue" size="sm" inset="top bottom" class="uppercase tracking-[0.18em]">
                        Reconocimiento destacado
                    </flux:badge>

                    <flux:heading size="xl" level="2" class="mt-4">
                        Reconocimientos Operativos
                    </flux:heading>

                    <flux:text class="mt-4 max-w-2xl text-base">
                        Celebramos el compromiso de nuestros equipos. Revisa los shoutouts destacados del mes.
                    </flux:text>

                    <div class="mt-6 flex flex-wrap gap-3">
                        @if($isAuthenticated)
                            <flux:button variant="primary" href="#">
                                Ver publicación completa
                            </flux:button>
                            <flux:button variant="outline" href="#">
                                Explorar reconocimientos
                            </flux:button>
                        @else
                            <flux:button variant="primary" href="{{ route('login') }}" wire:navigate>
                                Iniciar sesión para interactuar
                            </flux:button>
                            <flux:button variant="outline" href="#">
                                Explorar reconocimientos
                            </flux:button>
                        @endif
                    </div>
                </flux:card>

                <flux:card class="p-2 shadow-sm">
                    <img src="{{ asset('img/user-placeholder.png') }}" alt="Reconocimiento"
                        class="h-full min-h-[18rem] w-full rounded-xl object-cover">
                </flux:card>
            </div>
        </div>
    </section>

    <!-- Contenido Principal -->
    <div class="mx-auto w-full max-w-[85rem] px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">

            <!-- COLUMNA IZQUIERDA: Noticias Dinámicas -->
            <div class="space-y-10 lg:col-span-2">
                <section class="space-y-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <flux:heading size="xl" level="2">Noticias Internas</flux:heading>
                            <flux:subheading>Actualizaciones y novedades del Centro de Contactos.</flux:subheading>
                        </div>

                        <flux:button href="#" variant="ghost" icon-trailing="chevron-right" size="sm" wire:navigate>
                            Ver todas
                        </flux:button>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        @forelse ($newsItems as $news)
                            <flux:card
                                class="group flex flex-col overflow-hidden p-0 transition hover:-translate-y-0.5 hover:shadow-md">
                                <div class="h-48 w-full overflow-hidden border-b border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 relative">
                                    <button wire:click="viewNews({{ $news->id }})" class="block h-full w-full text-left">
                                        <img src="{{ $news->getFirstMediaUrl('featured_image') ?: asset('img/news-placeholder.png') }}"
                                            alt="{{ $news->title }}"
                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]">
                                    </button>
                                </div>
                                <div class="flex flex-1 flex-col p-5">
                                    <div class="mb-3 flex items-center justify-between">
                                        <flux:badge color="blue" size="sm" inset="top bottom">Módulo Operativo</flux:badge>
                                        <flux:button wire:click="viewNews({{ $news->id }})" variant="ghost" size="xs" icon-trailing="chevron-right">
                                            Leer noticia
                                        </flux:button>
                                    </div>
                                    <button wire:click="viewNews({{ $news->id }})" class="hover:underline decoration-cyan-500 underline-offset-4 text-left">
                                        <flux:heading size="lg">{{ $news->title }}</flux:heading>
                                    </button>
                                    <flux:text class="mt-2 line-clamp-2 flex-1">{{ $news->excerpt ?: str($news->content)->limit(100) }}</flux:text>
                                    <div class="mt-5 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <flux:button
                                                wire:click="toggleComments({{ $news->id }})"
                                                variant="ghost"
                                                size="sm"
                                                icon="chat-bubble-left"
                                                class="-ml-3">
                                                {{ $news->comments_count }} comentarios
                                            </flux:button>
                                            @if($isAuthenticated)
                                                <flux:button
                                                    wire:click="selectNewsForComment({{ $news->id }})"
                                                    variant="ghost"
                                                    size="sm"
                                                    icon="plus">
                                                    Comentar
                                                </flux:button>
                                            @else
                                                <flux:button href="{{ route('login') }}" wire:navigate variant="ghost" size="sm" icon="lock-closed">
                                                    Inicia sesión
                                                </flux:button>
                                            @endif
                                        </div>
                                        <flux:text class="text-xs">{{ $news->published_at->diffForHumans() }}</flux:text>
                                    </div>

                                    <!-- Formulario de comentario -->
                                    @if($isAuthenticated && $commentForm['news_id'] === $news->id)
                                        <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                                            <form wire:submit="submitComment" class="space-y-3">
                                                <flux:textarea
                                                    wire:model="commentForm.content"
                                                    placeholder="Escribe tu comentario..."
                                                    rows="3"
                                                    required />
                                                <div class="flex gap-2">
                                                    <flux:button type="submit" variant="primary" size="sm">
                                                        Publicar
                                                    </flux:button>
                                                    <flux:button
                                                        wire:click="$set('commentForm.news_id', null)"
                                                        variant="ghost"
                                                        size="sm">
                                                        Cancelar
                                                    </flux:button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    <!-- Comentarios -->
                                    @if($showComments && $selectedNewsId === $news->id && $selectedNews)
                                        <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                                            <flux:heading size="sm" class="mb-3">Comentarios</flux:heading>
                                            @unless($isAuthenticated)
                                                <flux:text class="mb-3 text-xs text-zinc-500">
                                                    Modo lectura activa. Inicia sesión para participar en la conversación.
                                                </flux:text>
                                            @endunless
                                            <div class="space-y-3">
                                                @forelse($selectedNews->comments->where('is_active', true) as $comment)
                                                    <div class="flex gap-3">
                                                        <div class="flex-shrink-0">
                                                            <div class="h-8 w-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                                                <span class="text-xs font-medium">{{ substr($comment->user->name, 0, 2) }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2">
                                                                <flux:text class="font-medium text-sm">{{ $comment->user->name }}</flux:text>
                                                                <flux:text class="text-xs text-zinc-500">{{ $comment->created_at->diffForHumans() }}</flux:text>
                                                            </div>
                                                            <flux:text class="text-sm mt-1">{{ $comment->content }}</flux:text>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <flux:text class="text-sm text-zinc-500 italic">No hay comentarios aún.</flux:text>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </flux:card>
                        @empty
                            <flux:card class="col-span-full flex flex-col items-center justify-center border-dashed py-12 text-center">
                                <flux:heading size="md">No hay noticias recientes</flux:heading>
                                <flux:subheading class="mt-1">Las novedades operativas aparecerán aquí.</flux:subheading>
                            </flux:card>
                        @endforelse
                    </div>
                </section>
            </div>

            <!-- COLUMNA DERECHA: Sidebar -->
            <aside class="space-y-6">
                <!-- Accesos Rápidos -->
                <flux:card class="shadow-sm">
                    <flux:heading size="lg">Accesos rápidos</flux:heading>
                    <flux:subheading class="mb-4">Atajos frecuentes operativos.</flux:subheading>

                    <div class="flex flex-col gap-1">
                        @foreach ([['icon' => 'users', 'label' => 'Proyectos'],
                                  ['icon' => 'map', 'label' => 'Sistemas Internos'],
                                  ['icon' => 'lifebuoy', 'label' => 'Soporte IT'],
                                  ['icon' => 'chart-bar', 'label' => 'Reportes WFM'],
                                  ['icon' => 'folder', 'label' => 'Directorio'],
                                  ['icon' => 'calendar', 'label' => 'Gestión de Turnos'],
                              ] as $item)
                            <flux:button variant="subtle" icon="{{ $item['icon'] }}" class="w-full justify-start" href="#">
                                {{ $item['label'] }}
                            </flux:button>
                        @endforeach
                    </div>
                </flux:card>

                <!-- Encuesta Dinámica -->
                @if($activePoll)
                    <flux:card>
                        <flux:heading size="lg">Encuesta Rápida</flux:heading>
                        <flux:subheading class="mb-4">{{ $activePoll->question }}</flux:subheading>

                        @if($isAuthenticated)
                            @if($hasVotedInActivePoll)
                                <div class="space-y-3 py-2">
                                    <flux:badge color="green" class="w-full justify-center">Ya has participado</flux:badge>
                                    
                                    <div class="space-y-3 mt-4">
                                        @php
                                            $totalVotes = collect($activePoll->options)->sum(fn($opt) => $opt['votes'] ?? 0);
                                        @endphp
                                        @foreach($activePoll->options as $option)
                                            @php
                                                $votes = $option['votes'] ?? 0;
                                                $percentage = $totalVotes > 0 ? round(($votes / $totalVotes) * 100) : 0;
                                            @endphp
                                            <div class="space-y-1">
                                                <div class="flex justify-between text-xs font-medium">
                                                    <span>{{ $option['text'] }}</span>
                                                    <span>{{ $percentage }}% ({{ $option['votes'] ?? 0 }})</span>
                                                </div>
                                                <div class="w-full bg-zinc-100 rounded-full h-2 dark:bg-zinc-800">
                                                    <div class="bg-{{ $option['color'] ?? 'blue' }}-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <flux:text class="text-xs text-center mt-4 italic">Gracias por tu participación.</flux:text>
                                </div>
                            @else
                                <form wire:submit="submitPoll" class="space-y-4">
                                    <flux:radio.group wire:model="pollForm.answer" variant="cards" class="flex-col">
                                        @foreach($activePoll->options as $option)
                                            <flux:radio value="{{ $option['text'] }}" label="{{ $option['text'] }}" />
                                        @endforeach
                                    </flux:radio.group>

                                    <flux:button type="submit" variant="primary" class="w-full">
                                        Votar
                                    </flux:button>
                                </form>
                            @endif
                        @else
                            <div class="space-y-3">
                                <div class="space-y-2">
                                    @foreach($activePoll->options as $option)
                                        <div class="rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                                            {{ $option['text'] }}
                                        </div>
                                    @endforeach
                                </div>
                                <flux:text class="text-xs text-zinc-500">Inicia sesión para votar y ver tu participación.</flux:text>
                                <flux:button href="{{ route('login') }}" wire:navigate variant="outline" class="w-full">
                                    Iniciar sesión
                                </flux:button>
                            </div>
                        @endif
                    </flux:card>
                @endif

                <!-- Notificaciones Recientes -->
                @if($isAuthenticated && $recentNotifications->count() > 0)
                    <flux:card class="shadow-sm">
                        <flux:heading size="lg">Notificaciones</flux:heading>
                        <flux:subheading class="mb-4">Actualizaciones recientes.</flux:subheading>

                        <div class="space-y-3">
                            @foreach($recentNotifications as $notification)
                                <div class="flex gap-3 p-2 rounded-lg bg-zinc-50 dark:bg-zinc-800/50">
                                    <div class="flex-shrink-0 mt-0.5">
                                        @switch($notification->type)
                                            @case('comment')
                                                <span class="text-blue-500">💬</span>
                                                @break
                                            @case('reaction')
                                                <span class="text-red-500">❤️</span>
                                                @break
                                            @case('mention')
                                                <span class="text-green-500">@</span>
                                                @break
                                            @default
                                                <span class="text-gray-500">🔔</span>
                                        @endswitch
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <flux:text class="text-sm line-clamp-2">{{ $notification->message }}</flux:text>
                                        <flux:text class="text-xs text-zinc-500 mt-1">{{ $notification->created_at->diffForHumans() }}</flux:text>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                            <flux:button href="#" variant="ghost" size="sm" class="w-full" wire:navigate>
                                Ver todas las notificaciones
                            </flux:button>
                        </div>
                    </flux:card>
                @elseif(!$isAuthenticated)
                    <flux:card class="shadow-sm">
                        <flux:heading size="lg">Participa en Comunicaciones</flux:heading>
                        <flux:subheading class="mb-4">Con sesión iniciada podrás comentar, reaccionar y recibir notificaciones.</flux:subheading>

                        <flux:button href="{{ route('login') }}" wire:navigate variant="primary" class="w-full">
                            Iniciar sesión
                        </flux:button>
                    </flux:card>
                @endif
            </aside>
        </div>
    </div>

    <!-- Abajo: Shoutouts -->
    <section class="mx-auto w-full max-w-[85rem] px-4 pb-16 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <flux:heading size="lg">Shout-outs</flux:heading>
                <flux:subheading>Mensajes rápidos de reconocimiento entre equipos operativos.</flux:subheading>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($shoutoutItems as $shoutout)
                <flux:card class="transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="mb-4 flex items-center gap-3">
                        <flux:avatar src="{{ $shoutout->employee->user?->avatar_url }}" name="{{ $shoutout->employee->name }}" />
                        <div>
                            <flux:heading size="sm">{{ $shoutout->employee->name }}</flux:heading>
                            <flux:text class="text-xs uppercase tracking-[0.16em]">Reconocimiento</flux:text>
                        </div>
                    </div>
                    <flux:text class="line-clamp-3 mb-4">{{ $shoutout->message }}</flux:text>

                    <!-- Reacciones -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1">
                            @php
                                $reactionTypes = ['like' => '👍', 'love' => '❤️', 'celebrate' => '🎉', 'support' => '🤝'];
                            @endphp
                            @foreach($reactionTypes as $type => $emoji)
                                @php
                                    $count = $shoutout->reactions->where('type', $type)->count();
                                    $userReacted = in_array($type, $shoutout->user_reactions ?? []);
                                @endphp
                                @if($isAuthenticated)
                                    <flux:button
                                        wire:click="toggleReaction({{ $shoutout->id }}, '{{ $type }}')"
                                        variant="{{ $userReacted ? 'primary' : 'ghost' }}"
                                        size="sm"
                                        class="h-8 px-2 text-xs">
                                        {{ $emoji }} {{ $count }}
                                    </flux:button>
                                @else
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        class="h-8 px-2 text-xs"
                                        disabled>
                                        {{ $emoji }} {{ $count }}
                                    </flux:button>
                                @endif
                            @endforeach
                        </div>
                        <flux:text class="text-xs text-zinc-500">{{ $shoutout->created_at->diffForHumans() }}</flux:text>
                    </div>
                </flux:card>
            @empty
                <flux:card class="col-span-full border-dashed bg-zinc-50/50 py-8 text-center dark:bg-zinc-800/50">
                    <flux:heading size="sm">Todavía no hay shout-outs publicados.</flux:heading>
                    <flux:subheading class="mt-1">Cuando alguien reconozca a un compañero, aparecerá aquí.</flux:subheading>
                </flux:card>
            @endforelse
        </div>
    </section>

    <flux:modal name="news-detail-modal" wire:model="showNewsModal" class="py-4 md:min-w-[50rem] space-y-6">
        @if ($viewingNews)
            <div class="space-y-6 px-4 py-2">
                <!-- Imagen Destacada -->
                <div class="-mx-6 -mt-6 overflow-hidden aspect-[21/9] bg-zinc-100 dark:bg-zinc-800">
                    <img
                        src="{{ $viewingNews->getFirstMediaUrl('featured_image') ?: asset('img/news-placeholder.png') }}"
                        alt="{{ $viewingNews->title }}"
                        class="w-full h-full object-cover"
                    />
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <flux:badge color="blue" size="sm">Novedades</flux:badge>
                        <flux:text class="text-xs">{{ $viewingNews->published_at->format('d M, Y') }}</flux:text>
                    </div>

                    <flux:heading size="xl">{{ $viewingNews->title }}</flux:heading>

                    <div class="flex items-center gap-3">
                        <flux:avatar src="{{ $viewingNews->author?->avatar_url }}" name="{{ $viewingNews->author?->name }}" size="sm" />
                        <flux:text class="text-sm font-medium">{{ $viewingNews->author?->name }}</flux:text>
                    </div>
                </div>

                <flux:separator />

                <!-- Contenido Principal -->
                <div class="prose prose-zinc dark:prose-invert max-w-none prose-sm sm:prose-base">
                    {!! Str::markdown($viewingNews->content) !!}
                </div>

                <!-- Espacio Dinámico para Multimedia y Documentos -->
                @if ($viewingNews->hasMedia('attachments'))
                    <flux:separator />
                    <div class="space-y-4">
                        <flux:heading size="sm">Archivos y Multimedia</flux:heading>
                        <div class="grid gap-4">
                            @foreach ($viewingNews->getMedia('attachments') as $media)
                                @php
                                    $extension = strtolower($media->extension);
                                    $isVideo = in_array($extension, ['mp4', 'webm', 'ogg']);
                                    $isPdf = $extension === 'pdf';
                                    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                                @endphp

                                <flux:card class="p-4 flex flex-col gap-3">
                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <flux:icon name="{{ $isPdf ? 'document-text' : ($isImage ? 'photo' : ($isVideo ? 'play-circle' : 'paper-clip')) }}" class="text-zinc-400" />
                                        <flux:text class="truncate text-sm font-medium">{{ $media->file_name }}</flux:text>
                                    </div>

                                    @if ($isImage)
                                        <img src="{{ $media->getUrl() }}" class="w-full h-32 object-cover rounded shadow-sm" />
                                    @elseif ($isVideo)
                                        <video controls class="w-full rounded shadow-sm">
                                            <source src="{{ $media->getUrl() }}" type="{{ $media->mime_type }}">
                                            Tu navegador no soporta el video.
                                        </video>
                                    @endif

                                    <div class="mt-auto gap-2">
                                        @if ($isPdf)
                                            <flux:embed src="{{ $media->getUrl() }}" type="application/pdf" width="100%" title="{{ $media->file_name }}" />
                                        @endif
                                    </div>
                                    <flux:button href="{{ $media->getUrl() }}" download variant="ghost" size="xs" icon="arrow-down-tray" class="flex-1">
                                            Descargar
                                        </flux:button>
                                </flux:card>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="flex items-center justify-center py-20">
                <flux:icon name="loading" class="animate-spin h-8 w-8 text-zinc-400" />
            </div>
        @endif

        <div class="flex justify-end pt-4">
            <flux:button wire:click="closeNewsModal" variant="ghost">Cerrar</flux:button>
        </div>
    </flux:modal>
</div>
