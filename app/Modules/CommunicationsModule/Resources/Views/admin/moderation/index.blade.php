@extends('layouts.app')

@section('title', 'Moderación de Contenido')

@section('content')
    <div class="space-y-4">
        <div>
            <flux:heading size="xl" level="1">Moderación de Contenido</flux:heading>
            <flux:subheading>Revisa y aprueba contenido pendiente de publicación en el sistema.</flux:subheading>
        </div>

        @if(session('success'))
            <flux:card class="bg-green-50 border-green-200">
                <flux:text color="green">{{ session('success') }}</flux:text>
            </flux:card>
        @endif

        <div x-data="{ tab: 'news' }" class="space-y-4">
            <div class="flex space-x-4 border-b border-slate-200 dark:border-slate-700 pb-px">
                <button type="button" 
                    @click="tab = 'news'"
                    :class="tab === 'news' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                    class="px-4 py-2 text-sm font-medium border-b-2 transition-opacity flex items-center gap-2">
                    <flux:icon name="newspaper" class="w-4 h-4" />
                    Noticias ({{ $pendingNews->total() }})
                </button>
                <button type="button" 
                    @click="tab = 'polls'"
                    :class="tab === 'polls' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                    class="px-4 py-2 text-sm font-medium border-b-2 transition-opacity flex items-center gap-2">
                    <flux:icon name="presentation-chart-line" class="w-4 h-4" />
                    Encuestas ({{ $pendingPolls->total() }})
                </button>
                <button type="button" 
                    @click="tab = 'shoutouts'"
                    :class="tab === 'shoutouts' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                    class="px-4 py-2 text-sm font-medium border-b-2 transition-opacity flex items-center gap-2">
                    <flux:icon name="sparkles" class="w-4 h-4" />
                    Reconocimientos ({{ $pendingShoutouts->total() }})
                </button>
            </div>

            <!-- Noticias -->
            <div x-show="tab === 'news'" class="space-y-4">
                @forelse($pendingNews as $news)
                    <flux:card>
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex-1">
                                <flux:heading size="lg">{{ $news->title }}</flux:heading>
                                <flux:text class="mt-1 line-clamp-2">{{ $news->excerpt }}</flux:text>
                                <div class="flex items-center gap-2 mt-2">
                                    <flux:badge size="sm" color="slate">Por: {{ $news->author->name }}</flux:badge>
                                    <flux:text size="xs">{{ $news->created_at->diffForHumans() }}</flux:text>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('communications.moderation.approve') }}">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="content_type" value="news">
                                    <input type="hidden" name="content_id" value="{{ $news->id }}">
                                    <flux:button type="submit" variant="primary" size="sm">Aprobar</flux:button>
                                </form>
                                
                                <flux:modal.trigger name="reject-news-{{ $news->id }}">
                                    <flux:button variant="ghost" color="red" size="sm">Rechazar</flux:button>
                                </flux:modal.trigger>

                                <flux:modal name="reject-news-{{ $news->id }}" class="md:max-w-md">
                                    <form method="POST" action="{{ route('communications.moderation.reject') }}" class="space-y-4">
                                        @csrf
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="content_type" value="news">
                                        <input type="hidden" name="content_id" value="{{ $news->id }}">
                                        
                                        <div>
                                            <flux:heading size="lg">Rechazar Noticia</flux:heading>
                                            <flux:subheading>Indica el motivo para que el autor pueda corregirlo.</flux:subheading>
                                        </div>

                                        <flux:textarea name="notes" label="Motivo de rechazo" placeholder="Ej. El título debe ser más descriptivo..." required />

                                        <div class="flex">
                                            <flux:spacer />
                                            <flux:button type="submit" variant="primary" color="red">Confirmar Rechazo</flux:button>
                                        </div>
                                    </form>
                                </flux:modal>
                            </div>
                        </div>
                    </flux:card>
                @empty
                    <flux:text align="center" class="py-12 text-slate-500">No hay noticias pendientes de revisión.</flux:text>
                @endforelse
                <div class="mt-4">
                    {{ $pendingNews->links() }}
                </div>
            </div>

            <!-- Encuestas -->
            <div x-show="tab === 'polls'" x-cloak class="space-y-4">
                @forelse($pendingPolls as $poll)
                    <flux:card>
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex-1">
                                <flux:heading size="lg">{{ $poll->question }}</flux:heading>
                                <div class="flex items-center gap-2 mt-2">
                                    <flux:badge size="sm" color="slate">Autor: Administración</flux:badge>
                                    <flux:text size="xs">{{ $poll->created_at->diffForHumans() }}</flux:text>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('communications.moderation.approve') }}">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="content_type" value="poll">
                                    <input type="hidden" name="content_id" value="{{ $poll->id }}">
                                    <flux:button type="submit" variant="primary" size="sm">Aprobar</flux:button>
                                </form>

                                <flux:modal.trigger name="reject-poll-{{ $poll->id }}">
                                    <flux:button variant="ghost" color="red" size="sm">Rechazar</flux:button>
                                </flux:modal.trigger>

                                <flux:modal name="reject-poll-{{ $poll->id }}" class="md:max-w-md">
                                    <form method="POST" action="{{ route('communications.moderation.reject') }}" class="space-y-4">
                                        @csrf
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="content_type" value="poll">
                                        <input type="hidden" name="content_id" value="{{ $poll->id }}">
                                        
                                        <div>
                                            <flux:heading size="lg">Rechazar Encuesta</flux:heading>
                                            <flux:subheading>Indica el motivo del rechazo.</flux:subheading>
                                        </div>

                                        <flux:textarea name="notes" label="Motivo de rechazo" required />

                                        <div class="flex">
                                            <flux:spacer />
                                            <flux:button type="submit" variant="primary" color="red">Confirmar Rechazo</flux:button>
                                        </div>
                                    </form>
                                </flux:modal>
                            </div>
                        </div>
                    </flux:card>
                @empty
                    <flux:text align="center" class="py-12 text-slate-500">No hay encuestas pendientes de revisión.</flux:text>
                @endforelse
                <div class="mt-4">
                    {{ $pendingPolls->links() }}
                </div>
            </div>

            <!-- Reconocimientos -->
            <div x-show="tab === 'shoutouts'" x-cloak class="space-y-4">
                @forelse($pendingShoutouts as $shoutout)
                    <flux:card>
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex-1">
                                <flux:text class="italic font-medium">"{{ $shoutout->message }}"</flux:text>
                                <div class="flex items-center gap-2 mt-3">
                                    <flux:badge size="sm" color="slate">Para: {{ $shoutout->employee->name }}</flux:badge>
                                    <flux:text size="xs">{{ $shoutout->created_at->diffForHumans() }}</flux:text>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('communications.moderation.approve') }}">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="content_type" value="shoutout">
                                    <input type="hidden" name="content_id" value="{{ $shoutout->id }}">
                                    <flux:button type="submit" variant="primary" size="sm">Aprobar</flux:button>
                                </form>

                                <flux:modal.trigger name="reject-shoutout-{{ $shoutout->id }}">
                                    <flux:button variant="ghost" color="red" size="sm">Rechazar</flux:button>
                                </flux:modal.trigger>

                                <flux:modal name="reject-shoutout-{{ $shoutout->id }}" class="md:max-w-md">
                                    <form method="POST" action="{{ route('communications.moderation.reject') }}" class="space-y-4">
                                        @csrf
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="content_type" value="shoutout">
                                        <input type="hidden" name="content_id" value="{{ $shoutout->id }}">
                                        
                                        <div>
                                            <flux:heading size="lg">Rechazar Reconocimiento</flux:heading>
                                            <flux:subheading>Indica el motivo del rechazo.</flux:subheading>
                                        </div>

                                        <flux:textarea name="notes" label="Motivo de rechazo" required />

                                        <div class="flex">
                                            <flux:spacer />
                                            <flux:button type="submit" variant="primary" color="red">Confirmar Rechazo</flux:button>
                                        </div>
                                    </form>
                                </flux:modal>
                            </div>
                        </div>
                    </flux:card>
                @empty
                    <flux:text align="center" class="py-12 text-slate-500">No hay reconocimientos pendientes de revisión.</flux:text>
                @endforelse
                <div class="mt-4">
                    {{ $pendingShoutouts->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
