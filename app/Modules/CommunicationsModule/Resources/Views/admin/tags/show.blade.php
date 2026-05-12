@extends('layouts.app')

@section('title', 'Ver Etiqueta - Comunicaciones')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <a href="{{ route('communications.admin.tags.index') }}" class="text-blue-600 hover:text-blue-800 mr-4">
                        ← Volver a Etiquetas
                    </a>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $tag->name }}</h1>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('communications.admin.tags.edit', $tag) }}"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                        Editar
                    </a>
                    <form method="POST" action="{{ route('communications.admin.tags.destroy', $tag) }}" class="inline"
                        onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta etiqueta?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Detalles de la Etiqueta</h2>
                </div>

                <div class="p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nombre</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $tag->name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Estado</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $tag->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $tag->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Color</dt>
                            <dd class="mt-1">
                                <div class="flex items-center">
                                    <div class="w-4 h-4 rounded mr-2" style="background-color: {{ $tag->color }}"></div>
                                    <span class="text-sm text-gray-900">{{ $tag->color }}</span>
                                </div>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Fecha de Creación</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $tag->created_at->format('d/m/Y H:i') }}</dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Descripción</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $tag->description ?: 'Sin descripción' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            @if($tag->news->count() > 0 || $tag->polls->count() > 0 || $tag->shoutouts->count() > 0)
                <div class="mt-8 bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Contenido Relacionado</h2>
                    </div>

                    <div class="p-6">
                        @if($tag->news->count() > 0)
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-3">Noticias ({{ $tag->news->count() }})</h3>
                                <div class="space-y-2">
                                    @foreach($tag->news as $news)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                            <span class="text-sm text-gray-900">{{ $news->title }}</span>
                                            <a href="{{ route('communications.news.edit', $news) }}"
                                                class="text-blue-600 hover:text-blue-800 text-sm">Editar</a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($tag->polls->count() > 0)
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-3">Encuestas ({{ $tag->polls->count() }})</h3>
                                <div class="space-y-2">
                                    @foreach($tag->polls as $poll)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                            <span class="text-sm text-gray-900">{{ $poll->question }}</span>
                                            <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">Editar</a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($tag->shoutouts->count() > 0)
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-3">Shoutouts ({{ $tag->shoutouts->count() }})</h3>
                                <div class="space-y-2">
                                    @foreach($tag->shoutouts as $shoutout)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                            <span class="text-sm text-gray-900">{{ Str::limit($shoutout->content, 50) }}</span>
                                            <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">Editar</a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
