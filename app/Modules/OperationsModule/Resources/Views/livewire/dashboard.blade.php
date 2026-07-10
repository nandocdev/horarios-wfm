<div class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Dashboard personal</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ $greeting }}, {{ $displayName }}</h1>
                <p class="mt-2 text-sm text-slate-600">{{ $todayLabel }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">Turno
                    {{ $shift['start'] }} - {{ $shift['end'] }}</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">Equipo
                    {{ $shift['team'] }}</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">Sup.
                    {{ $shift['supervisor'] }}</span>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-500">Score Operativo</p>
                    <div class="mt-3 flex items-end gap-2">
                        <span
                            class="text-5xl font-semibold tracking-tight text-slate-900">{{ $operationalScore['value'] }}</span>
                        <span class="pb-1 text-xl text-slate-500">/ 100</span>
                    </div>
                </div>
                <span
                    class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">{{ $operationalScore['label'] }}</span>
            </div>
            <p class="mt-4 text-sm text-slate-600">{{ $operationalScore['delta'] }}</p>
            <a href="#" class="mt-4 inline-flex text-sm font-medium text-blue-600 hover:text-blue-700">¿Cómo se
                calcula?</a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Estado Actual</p>
            <div class="mt-4 flex items-center gap-3">
                <span class="inline-flex h-3 w-3 rounded-full bg-emerald-500"></span>
                <span class="text-lg font-semibold text-slate-900">{{ $status['label'] }}</span>
            </div>
            <div class="mt-5 space-y-3 text-sm text-slate-600">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <span>{{ $status['subtitle'] }}</span>
                    <span class="font-semibold text-slate-900">{{ $status['currentCall'] }}</span>
                </div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <span>Tiempo logueado</span>
                    <span class="font-semibold text-slate-900">{{ $status['loggedTime'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Tiempo restante turno</span>
                    <span class="font-semibold text-slate-900">{{ $status['remainingTime'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Mi Jornada</h2>
            <div class="mt-6 space-y-4">
                @foreach ($journey as $step)
                    <div class="flex items-center gap-3">
                        <div class="flex flex-col items-center">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-full {{ $step['complete'] ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500' }}">
                                {{ $step['complete'] ? '✓' : '○' }}
                            </div>
                            @if (!$loop->last)
                                <div class="mt-1 h-8 w-px bg-slate-200"></div>
                            @endif
                        </div>
                        <div class="flex flex-1 items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                            <span class="text-sm font-medium text-slate-700">{{ $step['label'] }}</span>
                            <span class="text-sm text-slate-500">{{ $step['time'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Mi Productividad Hoy</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($productivity as $card)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $card['value'] }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $card['meta'] }}</p>
                        <div class="mt-3 h-2 rounded-full bg-slate-200">
                            <div class="h-2 rounded-full bg-blue-600" style="width: {{ min($card['progress'], 100) }}%">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Mi Comparación</h2>
            <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Indicador</th>
                            <th class="px-4 py-3 font-medium">Yo</th>
                            <th class="px-4 py-3 font-medium">Equipo</th>
                            <th class="px-4 py-3 font-medium">Mejor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($comparison as $row)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-700">{{ $row['indicator'] }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $row['self'] }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $row['team'] }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $row['top'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Adherencia</h2>
            <div class="mt-5 flex items-end justify-between gap-4">
                <div>
                    <p class="text-4xl font-semibold text-slate-900">{{ $adherence['value'] }}%</p>
                    <p class="mt-2 text-sm font-medium text-emerald-600">Excelente</p>
                </div>
                <div class="text-sm text-slate-500">{{ $adherence['detail'] }}</div>
            </div>
            <div class="mt-5 h-3 rounded-full bg-slate-100">
                <div class="h-3 rounded-full bg-emerald-500" style="width: {{ $adherence['value'] }}%"></div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Mi Disponibilidad</h2>
            <div class="mt-6 space-y-3">
                @foreach ($availability as $item)
                    <div class="flex items-center gap-3">
                        <div class="h-3 w-3 rounded-full bg-blue-600"></div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between text-sm text-slate-600">
                                <span>{{ $item['label'] }}</span>
                                <span class="font-semibold text-slate-900">{{ $item['value'] }}%</span>
                            </div>
                            <div class="mt-2 h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-blue-600" style="width: {{ $item['value'] }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Calidad</h2>
            <div class="mt-5 flex items-end gap-3">
                <span class="text-4xl font-semibold text-slate-900">{{ $quality['value'] }}%</span>
                <span class="pb-1 text-sm font-medium text-amber-500">★★★★★</span>
            </div>
            <div class="mt-6 space-y-3">
                @foreach ($quality['items'] as $item)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-center justify-between text-sm text-slate-600">
                            <span>{{ $item['label'] }}</span>
                            <span class="font-semibold text-slate-900">{{ $item['value'] }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>