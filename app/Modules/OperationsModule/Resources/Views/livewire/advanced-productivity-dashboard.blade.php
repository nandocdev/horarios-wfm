<div class="py-2 px-4 space-y-8 bg-slate-50 min-h-screen">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gapy-2 px-4 bg-white py-2 px-4 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="presentation-chart-line" variant="mini" class="text-blue-600" />
                Analítica de Productividad Avanzada
            </flux:heading>
            <flux:subheading>Métricas de PWI, Work Units y Capacidad Operativa (Snapshots Persistidos)</flux:subheading>
        </div>

        <div class="flex items-center gapy-2 px-4">
            <div class="w-64">
                <flux:select wire:model.live="teamId" placeholder="Todos los Equipos">
                    <flux:select.option value="">Todos los Equipos</flux:select.option>
                    @foreach($teams as $team)
                        <flux:select.option value="{{ $team->id }}">{{ $team->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:input type="date" wire:model.live="date" />
        </div>
    </div>

    {{-- Resumen de KPIs Superiores --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gapy-2 px-4">
        <flux:card class="bg-blue-600 text-white rounded-md overflow-hidden relative">
            <div class="absolute right-0 bottom-0 opacity-10">
                <flux:icon name="bolt" class="w-24 h-24" />
            </div>
            <p class="text-xs font-bold uppercase tracking-widest opacity-80">PWI Promedio</p>
            <p class="text-4xl font-black mt-2">{{ number_format($summary['avg_pwi'], 1) }}%</p>
            <p class="text-[10px] mt-2 opacity-60">Availability × Efficiency</p>
        </flux:card>

        <flux:card>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Work Units Atendidos</p>
            <p class="text-4xl font-semibold text-slate-800 mt-2">{{ number_format($summary['total_work_units'], 0) }}</p>
            <p class="text-[10px] text-slate-500 mt-2 italic">Minutos equivalentes de carga</p>
        </flux:card>

        <flux:card>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Capacidad Teórica</p>
            <p class="text-4xl font-semibold text-slate-800 mt-2">{{ number_format($summary['total_capacity_calls'], 0) }}</p>
            <p class="text-[10px] text-slate-500 mt-2 italic">Llamadas esperadas según T. Prod.</p>
        </flux:card>

        <flux:card class="border-l-4 border-l-red-600">
            <p class="text-xs font-semibold text-red-600 uppercase tracking-widest">Brecha de Capacidad (Gap)</p>
            <p class="text-4xl font-black text-red-600 mt-2">{{ number_format($summary['total_gap'], 0) }}</p>
            <p class="text-[10px] text-red-500 mt-2 italic">Llamadas potenciales no realizadas</p>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gapy-2 px-4">
        {{-- Rankings --}}
        <div class="space-y-4">
            <flux:card class="space-y-4">
                <flux:heading size="lg">Top PWI (Eficiencia)</flux:heading>
                <div class="space-y-3">
                    @foreach($topPerformers as $m)
                        <div class="flex items-center justify-between p-2 bg-slate-50 rounded-md">
                            <div class="flex items-center gap-3">
                                <flux:avatar src="{{ $m->employee->avatar_url }}" size="xs" />
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-700 truncate">{{ $m->employee->full_name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $m->employee->team?->name }}</p>
                                </div>
                            </div>
                            <flux:badge color="green" inset="top bottom" class="rounded-md">{{ number_format($m->pwi_pct, 1) }}%</flux:badge>
                        </div>
                    @endforeach
                </div>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Mayores Gaps (Oportunidad)</flux:heading>
                <div class="space-y-3">
                    @foreach($underPerformers as $m)
                        <div class="flex items-center justify-between p-2 bg-slate-50 rounded-md">
                            <div class="flex items-center gap-3">
                                <flux:avatar src="{{ $m->employee->avatar_url }}" size="xs" />
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-700 truncate">{{ $m->employee->full_name }}</p>
                                    <p class="text-[10px] text-slate-400">Gap: {{ number_format($m->capacity_gap, 0) }} llam.</p>
                                </div>
                            </div>
                            <flux:badge color="red" inset="top bottom" class="rounded-md">{{ number_format($m->efficiency_pct, 1) }}% Eff.</flux:badge>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        </div>

        {{-- Tabla Detallada --}}
        <div class="lg:col-span-2">
            <flux:card class="p-0 overflow-hidden">
                <div class="py-2 px-4 border-b border-slate-100 flex justify-between items-center">
                    <flux:heading size="lg">Desglose Detallado por Agente</flux:heading>
                    <flux:button variant="ghost" size="sm" icon="arrow-down-tray">Exportar</flux:button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="sticky top-0 z-10 bg-slate-50 text-[10px] font-semibold text-slate-500 uppercase tracking-widest">
                            <tr>
                                <th class="py-2 px-4">Agente</th>
                                <th class="py-2 px-4 text-center">Availability</th>
                                <th class="py-2 px-4 text-center">Efficiency</th>
                                <th class="py-2 px-4 text-center">Capacidad</th>
                                <th class="py-2 px-4 text-center">Real</th>
                                <th class="py-2 px-4 text-center">PWI</th>
                                <th class="py-2 px-4 text-center">WU</th>
                                <th class="py-2 px-4 text-center">AHTw</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @forelse($metrics as $m)
                                <tr class="hover:bg-slate-50 transition-colors duration-150">
                                    <td class="py-2 px-4">
                                        <div class="flex items-center gap-3">
                                            <flux:avatar src="{{ $m->employee->avatar_url }}" size="xs" />
                                            <div>
                                                <p class="font-semibold text-slate-700">{{ $m->employee->full_name }}</p>
                                                <p class="text-[10px] text-slate-500">{{ $m->employee->position?->name }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2 px-4 text-center">
                                        <div class="flex flex-col items-center">
                                            <span class="font-bold {{ $m->availability_pct >= 90 ? 'text-green-600' : ($m->availability_pct >= 80 ? 'text-amber-600' : 'text-red-600') }}">
                                                {{ number_format($m->availability_pct, 1) }}%
                                            </span>
                                            <span class="text-[9px] text-slate-400 uppercase">Disp.</span>
                                        </div>
                                    </td>
                                    <td class="py-2 px-4 text-center">
                                        <div class="flex flex-col items-center">
                                            <span class="font-bold {{ $m->efficiency_pct >= 85 ? 'text-green-600' : ($m->efficiency_pct >= 70 ? 'text-amber-600' : 'text-red-600') }}">
                                                {{ number_format($m->efficiency_pct, 1) }}%
                                            </span>
                                            <span class="text-[9px] text-slate-400 uppercase">Aprovech.</span>
                                        </div>
                                    </td>
                                    <td class="py-2 px-4 text-center">
                                        <div class="flex flex-col items-center">
                                            <span class="font-semibold text-slate-600">{{ number_format($m->capacity_calls, 1) }}</span>
                                            <span class="text-[9px] text-slate-400 uppercase">Esperadas</span>
                                        </div>
                                    </td>
                                    <td class="py-2 px-4 text-center">
                                        <div class="flex flex-col items-center">
                                            <span class="font-semibold text-slate-800">{{ $m->calls_total }}</span>
                                            <span class="text-[9px] text-slate-400 uppercase">Atendidas</span>
                                        </div>
                                    </td>
                                    <td class="py-2 px-4 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="px-2 py-1 rounded bg-blue-50 border border-blue-200 rounded-md">
                                                <span class="font-bold text-blue-600">
                                                    {{ number_format($m->pwi_pct, 1) }}%
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2 px-4 text-center font-mono text-slate-600">
                                        {{ number_format($m->work_units, 0) }}
                                    </td>
                                    <td class="py-2 px-4 text-center font-mono text-slate-400">
                                        {{ $m->weighted_aht }}s
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center">
                                        <div class="flex flex-col items-center">
                                            <flux:icon name="face-frown" class="w-10 h-10 text-slate-300 mb-2" />
                                            <p class="text-slate-500 italic">No hay métricas agregadas para esta fecha.</p>
                                            <p class="text-xs text-slate-400">Ejecuta wfm:aggregate-metrics para procesar datos.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Diccionario de Indicadores --}}
    <flux:card class="space-y-4 bg-white/50">
        <div>
            <flux:heading size="lg">Diccionario de Indicadores — Modelo WU/PWI</flux:heading>
            <flux:subheading>Guía técnica para la interpretación de métricas de productividad avanzada.</flux:subheading>
        </div>

        <flux:separator />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- Columna 1 --}}
            <div class="space-y-4">
                <div class="py-2 px-4 bg-white rounded-md border border-slate-100 shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <flux:badge color="blue" size="sm" inset="top bottom" class="rounded-md">WU</flux:badge>
                        <p class="text-sm font-semibold text-slate-800">Work Units (Carga Atendida)</p>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed italic mb-2">Σ (Llamadas_Cola × AHT_Meta_Cola)</p>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Normaliza el trabajo realizado convirtiendo las llamadas en <strong>minutos de carga equivalentes</strong>. Permite comparar agentes de diferentes colas sin el sesgo de la complejidad o duración de la llamada.
                    </p>
                </div>

                <div class="py-2 px-4 bg-white rounded-md border border-slate-100 shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <flux:badge color="blue" size="sm" inset="top bottom" class="rounded-md">AHTw</flux:badge>
                        <p class="text-sm font-semibold text-slate-800">AHT Ponderado (Meta)</p>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed italic mb-2">Σ (Distribución_Cola × AHT_Meta_Cola)</p>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Representa el tiempo promedio de atención <strong>esperado</strong> para el mix de llamadas que el agente atendió realmente en su turno.
                    </p>
                </div>
            </div>

            {{-- Columna 2 --}}
            <div class="space-y-4">
                <div class="py-2 px-4 bg-white rounded-md border border-slate-100 shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <flux:badge color="amber" size="sm" inset="top bottom" class="rounded-md">CAP</flux:badge>
                        <p class="text-sm font-semibold text-slate-800">Capacidad Teórica</p>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed italic mb-2">Tiempo_Productivo / AHT_Ponderado</p>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Define cuántas llamadas <strong>podría haber atendido</strong> el operador basándose en su tiempo disponible en estados productivos y el AHT meta.
                    </p>
                </div>

                <div class="py-2 px-4 bg-white rounded-md border border-slate-100 shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <flux:badge color="red" size="sm" inset="top bottom" class="rounded-md">GAP</flux:badge>
                        <p class="text-sm font-semibold text-slate-800">Brecha Operativa (Gap)</p>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed italic mb-2">Capacidad_Teórica - Llamadas_Reales</p>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Indica la pérdida de productividad: volumen de trabajo que no se ejecutó a pesar de tener tiempo disponible. Valores altos sugieren tiempos de espera (Ready) prolongados o ineficiencia.
                    </p>
                </div>
            </div>

            {{-- Columna 3 --}}
            <div class="space-y-4">
                <div class="py-2 px-4 bg-blue-50 rounded-md border border-blue-100 shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <flux:badge color="blue" size="sm" inset="top bottom" class="rounded-md">PWI</flux:badge>
                        <p class="text-sm font-semibold text-blue-900">Productive Work Index</p>
                    </div>
                    <p class="text-[11px] text-blue-700 leading-relaxed italic mb-2">Availability % × Efficiency %</p>
                    <p class="text-xs text-blue-800 leading-relaxed">
                        Es la <strong>métrica maestra</strong> de productividad. Consolida qué tanto tiempo estuvo disponible el agente y qué tan bien aprovechó ese tiempo para producir. Un PWI alto indica un balance perfecto entre disciplina de conexión y rendimiento.
                    </p>
                </div>

                <div class="py-2 px-4 bg-white rounded-md border border-slate-100 shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <flux:badge color="slate" size="sm" inset="top bottom" class="rounded-md">EFF</flux:badge>
                        <p class="text-sm font-semibold text-slate-800">Efficiency (Aprovechamiento)</p>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed italic mb-2">Llamadas_Reales / Capacidad_Teórica</p>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Mide qué porcentaje de la capacidad disponible fue convertida en producción real. No depende de cuántas horas se conectó el agente, sino de qué hizo mientras estuvo listo.
                    </p>
                </div>
            </div>
        </div>
    </flux:card>
</div>
