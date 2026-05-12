@extends('layouts.app')

@section('title', 'Editar Etiqueta - Comunicaciones')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center mb-6">
            <a href="{{ route('communications.admin.tags.show', $tag) }}"
               class="text-blue-600 hover:text-blue-800 mr-4">
                ← Volver a Etiqueta
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Editar Etiqueta</h1>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6">
            <form method="POST" action="{{ route('communications.admin.tags.update', $tag) }}">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre *
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $tag->name) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Descripción
                    </label>
                    <textarea id="description"
                              name="description"
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror">{{ old('description', $tag->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="color" class="block text-sm font-medium text-gray-700 mb-2">
                        Color
                    </label>
                    <input type="color"
                           id="color"
                           name="color"
                           value="{{ old('color', $tag->color) }}"
                           class="w-20 h-10 border border-gray-300 rounded-md @error('color') border-red-500 @enderror">
                    @error('color')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               {{ old('is_active', $tag->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Etiqueta activa</span>
                    </label>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="{{ route('communications.admin.tags.show', $tag) }}"
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Actualizar Etiqueta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
