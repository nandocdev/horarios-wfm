@extends('layouts.app')

@section('title', 'Ubicaciones Geográficas')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Ubicaciones Geográficas de Panamá</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($provinces as $province)
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-lg transition-shadow">
                        <h2 class="text-lg font-semibold text-gray-700 mb-3">{{ $province->name }}</h2>

                        @if($province->districts->count() > 0)
                            <div class="space-y-2">
                                <h3 class="text-sm font-medium text-gray-600">Distritos:</h3>
                                <ul class="text-sm text-gray-500 space-y-1">
                                    @foreach($province->districts as $district)
                                        <li class="flex items-center">
                                            <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                                            {{ $district->name }}
                                            @if($district->townships->count() > 0)
                                                <span class="text-xs text-gray-400 ml-1">({{ $district->townships->count() }}
                                                    corregimientos)</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <p class="text-sm text-gray-400">Sin distritos registrados</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-8 p-4 bg-blue-50 rounded-lg">
                <h3 class="text-sm font-medium text-blue-800 mb-2">Estadísticas</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-blue-700">Provincias:</span>
                        <span class="text-blue-600">{{ $provinces->count() }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-blue-700">Distritos:</span>
                        <span class="text-blue-600">{{ $provinces->sum(fn($p) => $p->districts->count()) }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-blue-700">Corregimientos:</span>
                        <span
                            class="text-blue-600">{{ $provinces->sum(fn($p) => $p->districts->sum(fn($d) => $d->townships->count())) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
