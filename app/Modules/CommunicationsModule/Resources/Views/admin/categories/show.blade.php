@extends('layouts.app')

@section('title', 'Ver Categoría - Comunicaciones')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center">
                    <a href="{{ route('communications.admin.categories.index') }}"
                        class="text-slate-600 hover:text-slate-900 mr-4">
                        ← Volver a Categorías
                    </a>
                    <h1 class="text-3xl font-bold text-slate-900">{{ $category->name }}</h1>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('communications.admin.categories.edit', $category) }}"
                        class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-md font-medium">
                        Editar
                    </a>
                    <form method="POST" action="{{ route('communications.admin.categories.destroy', $category) }}"
                        class="inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta categoría?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md font-medium">
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-md rounded-md overflow-hidden">
                <div class="px-4 py-4 border-b border-slate-200">
                    <h2 class="text-xl font-semibold text-slate-900">Detalles de la Categoría</h2>
                </div>

                <div class="p-4">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Nombre</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $category->name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-slate-500">Estado</dt>
                            <dd class="mt-1">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-md {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $category->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-slate-500">Color</dt>
                            <dd class="mt-1">
                                <div class="flex items-center">
                                    <div class="w-4 h-4 rounded mr-2" style="background-color: {{ $category->color }}">
                                    </div>
                                    <span class="text-sm text-slate-900">{{ $category->color }}</span>
                                </div>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-slate-500">Fecha de Creación</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $category->created_at->format('d/m/Y H:i') }}</dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-slate-500">Descripción</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                {{ $category->description ?: 'Sin descripción' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            @if($category->news->count() > 0 || $category->polls->count() > 0 || $category->shoutouts->count() > 0)
                <div class="mt-8 bg-white shadow-md rounded-md overflow-hidden">
                    <div class="px-4 py-4 border-b border-slate-200">
                        <h2 class="text-xl font-semibold text-slate-900">Contenido Relacionado</h2>
                    </div>

                    <div class="p-4">
                        @if($category->news->count() > 0)
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-slate-900 mb-4">Noticias ({{ $category->news->count() }})</h3>
                                <div class="space-y-2">
                                    @foreach($category->news as $news)
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-md">
                                            <span class="text-sm text-slate-900">{{ $news->title }}</span>
                                            <a href="{{ route('communications.news.edit', $news) }}"
                                                class="text-slate-600 hover:text-slate-900 text-sm">Editar</a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($category->polls->count() > 0)
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-slate-900 mb-4">Encuestas ({{ $category->polls->count() }})</h3>
                                <div class="space-y-2">
                                    @foreach($category->polls as $poll)
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-md">
                                            <span class="text-sm text-slate-900">{{ $poll->question }}</span>
                                            <a href="#" class="text-slate-600 hover:text-slate-900 text-sm">Editar</a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($category->shoutouts->count() > 0)
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-slate-900 mb-4">Shoutouts ({{ $category->shoutouts->count() }})
                                </h3>
                                <div class="space-y-2">
                                    @foreach($category->shoutouts as $shoutout)
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-md">
                                            <span class="text-sm text-slate-900">{{ Str::limit($shoutout->content, 50) }}</span>
                                            <a href="#" class="text-slate-600 hover:text-slate-900 text-sm">Editar</a>
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
