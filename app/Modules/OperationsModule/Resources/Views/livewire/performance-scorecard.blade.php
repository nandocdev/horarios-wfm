<div class="p-6 space-y-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 bg-white/50 backdrop-blur-md p-6 rounded-2xl border border-white shadow-xl">
        <div class="flex items-center gap-4">
            <div>
                <flux:heading size="xl" level="1">Desempeño {{ $employeeId ? ($employees->firstWhere('id', (int)$employeeId)?->full_name ?? 'Seleccionado') : 'Selecciona un empleado' }}</flux:heading>
                <flux:subheading>Análisis de adherencia y productividad de Contact Center</flux:subheading>
            </div>
            
            <flux:modal.trigger name="calculation-formulas">
                <flux:button variant="ghost" icon="information-circle" size="sm" class="text-blue-500 hover:bg-blue-50" />
            </flux:modal.trigger>
        </div>

        <flux:modal name="calculation-formulas" variant="flyout" class="space-y-6">
            <div>
                <flux:heading size="lg">Diccionario de Fórmulas</flux:heading>
                <flux:subheading>Detalle técnico de los cálculos de desempeño</flux:subheading>
            </div>

            <div class="space-y-6 overflow-y-auto max-h-[70vh] pr-2">
                <!-- Glosario de Variables -->
                <div class="space-y-3">
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Glosario de Variables</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-100 flex items-center gap-2">
                            <span class="bg-blue-100 text-blue-700 text-[10px] font-black px-1.5 py-0.5 rounded">TP</span>
                            <span class="text-[10px] text-slate-600"><strong>Tiempo Productivo:</strong> Ready + Talking + Work + Reserved</span>
                        </div>
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-100 flex items-center gap-2">
                            <span class="bg-slate-200 text-slate-700 text-[10px] font-black px-1.5 py-0.5 rounded">TC</span>
                            <span class="text-[10px] text-slate-600"><strong>Tiempo de Conexión:</strong> Tiempo total logueado en UCCX</span>
                        </div>
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-100 flex items-center gap-2">
                            <span class="bg-indigo-100 text-indigo-700 text-[10px] font-black px-1.5 py-0.5 rounded">JP</span>
                            <span class="text-[10px] text-slate-600"><strong>Jornada Programada:</strong> Minutos totales asignados en el horario</span>
                        </div>
                        <div class="p-2 bg-amber-100 text-amber-700 text-[10px] font-black px-1.5 py-0.5 rounded">TT</span>
                            <span class="text-[10px] text-slate-600"><strong>Tiempo Transcurrido:</strong> Minutos desde Inicio Programado hasta Ahora</span>
                        </div>
                        <div class="p-2 bg-emerald-100 text-emerald-700 text-[10px] font-black px-1.5 py-0.5 rounded">IR</span>
                            <span class="text-[10px] text-slate-600"><strong>Inicio Real:</strong> Primer estado activo detectado (>10s)</span>
                        </div>
                    </div>
                </div>

                <flux:separator />

                <div class="space-y-3">
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Eficiencia Operativa</p>
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <p class="text-sm font-bold text-slate-800">Productividad (%)</p>
                        <p class="text-xs text-slate-500 mt-1 italic font-mono">(TP / TC) × 100</p>
                        <p class="text-[10px] text-slate-400 mt-2">Mide qué tan ocupado estuvo el agente mientras estuvo logueado.</p>
                    </div>
                    <div class="bg-amber-50 p-4 rounded-xl border border-amber-100">
                        <p class="text-sm font-bold text-amber-900">Utilización WFM (%)</p>
                        <p class="text-xs text-amber-700 mt-1 italic font-mono">Real-time: (TP / TT) × 100 | Histórico: (TP / JP) × 100</p>
                        <p class="text-[10px] text-amber-600 mt-2">Mide el rendimiento real contra lo planificado (afectado por tardanzas y desconexiones).</p>
                    </div>
                    <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                        <p class="text-sm font-bold text-indigo-900">Adherencia (%)</p>
                        <p class="text-xs text-indigo-700 mt-1 italic font-mono">(Minutos Adherentes / Jornada Programada) × 100</p>
                        <p class="text-[10px] text-indigo-600 mt-2">Mide el apego estricto al cronograma: estar en el estado correcto en el momento correcto.</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Conexión y Logout</p>
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <p class="text-sm font-bold text-slate-800">Logout (Desconexión) - Real Time</p>
                        <p class="text-xs text-slate-500 mt-1 italic font-mono">(Ahora - Inicio Real) - Tiempo Conexión</p>
                        <p class="text-[10px] text-slate-400 mt-2">Calcula la brecha de desconexión acumulada durante el turno actual.</p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <p class="text-sm font-bold text-slate-800">Logout (Desconexión) - Histórico</p>
                        <p class="text-xs text-slate-500 mt-1 italic font-mono">Jornada Programada - Tiempo Conexión</p>
                        <p class="text-[10px] text-slate-400 mt-2">Muestra el tiempo total de desconexión al finalizar el día.</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Asistencia y Llamadas</p>
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <p class="text-sm font-bold text-slate-800">AHT (Average Handle Time)</p>
                        <p class="text-xs text-slate-500 mt-1 italic font-mono">(Talk Time + Work Time) / Llamadas Atendidas</p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <p class="text-sm font-bold text-slate-800">Estatus de Asistencia</p>
                        <ul class="text-[10px] text-slate-500 mt-2 list-disc pl-4 space-y-1">
                            <li><strong>ON TIME:</strong> Entrada ≤ Programada + 5 min.</li>
                            <li><strong>LATE:</strong> Entrada > Programada + 5 min.</li>
                            <li><strong>ABSENT:</strong> Sin registro de actividad UCCX (No justificado).</li>
                            <li><strong>EXCEPTION:</strong> Excepción programada (Vacaciones, Licencia, etc.). Excluido del cálculo de ausentismo.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Entendido</flux:button>
                </flux:modal.close>
            </div>
        </flux:modal>

        <div class="flex flex-wrap items-center gap-4">
            <div class="w-64">
                <flux:select wire:model.live="teamId" placeholder="Filtrar por Equipo">
                    <x-slot name="icon">
                        <flux:icon name="users" variant="micro" />
                    </x-slot>
                    <flux:select.option value="">Todos los Equipos</flux:select.option>
                    @foreach($teams as $team)
                        <flux:select.option value="{{ $team->id }}">{{ $team->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="w-64">
                <flux:select wire:model.live="employeeId" placeholder="Seleccione Operador">
                    <x-slot name="icon">
                        <flux:icon name="user" variant="micro" />
                    </x-slot>
                    @foreach($employees as $employee)
                        <flux:select.option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->username }})</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="w-40">
                <flux:select wire:model.live="periodType">
                    <flux:select.option value="daily">Diario</flux:select.option>
                    <flux:select.option value="weekly">Semanal</flux:select.option>
                    <flux:select.option value="monthly">Mensual</flux:select.option>
                </flux:select>
            </div>

            <flux:input type="date" wire:model.live="selectedDate" />
        </div>
    </div>

    @if(!empty($performanceData))
        <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
            <!-- Productividad -->
            <flux:tooltip position="top" content="Intensidad: Tiempo productivo vs Tiempo total de conexión.">
                <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 to-indigo-700 p-6 rounded-2xl shadow-lg group hover:scale-[1.02] transition-transform duration-300 h-full">
                    <div class="absolute top-0 right-0 -mr-4 -mt-4 opacity-10 group-hover:scale-125 transition-transform duration-500">
                        <flux:icon name="chart-bar" class="w-24 h-24 text-white" />
                    </div>
                    <p class="text-xs font-bold text-blue-100 uppercase tracking-widest">Productividad</p>
                    <p class="text-3xl font-black text-white mt-1">
                        {{ number_format(collect($performanceData)->avg('metrics.productivity_percentage'), 1) }}%
                    </p>
                    <div class="mt-4 h-1 w-full bg-white/20 rounded-full overflow-hidden">
                        <div class="h-full bg-white rounded-full" style="width: {{ collect($performanceData)->avg('metrics.productivity_percentage') }}%"></div>
                    </div>
                </div>
            </flux:tooltip>

            <!-- Adherencia -->
            <flux:tooltip position="top" content="Disciplina: Tiempo productivo generado dentro de su horario programado.">
                <div class="relative overflow-hidden bg-gradient-to-br from-indigo-500 to-blue-700 p-6 rounded-2xl shadow-lg group hover:scale-[1.02] transition-transform duration-300 border border-indigo-400/20 h-full">
                    <div class="absolute top-0 right-0 -mr-4 -mt-4 opacity-10 group-hover:scale-125 transition-transform duration-500">
                        <flux:icon name="shield-check" class="w-24 h-24 text-white" />
                    </div>
                    <p class="text-xs font-bold text-indigo-100 uppercase tracking-widest">Adherencia</p>
                    <p class="text-3xl font-black text-white mt-1">
                        {{ number_format(collect($performanceData)->avg('metrics.adherence_percentage'), 1) }}%
                    </p>
                    <div class="mt-4 h-1 w-full bg-white/20 rounded-full overflow-hidden">
                        <div class="h-full bg-white rounded-full" style="width: {{ collect($performanceData)->avg('metrics.adherence_percentage') }}%"></div>
                    </div>
                </div>
            </flux:tooltip>

            <!-- Utilización WFM -->
            <flux:tooltip position="top" content="Rendimiento: Producción real comparada contra la jornada completa planificada.">
                <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-600 p-6 rounded-2xl shadow-lg group hover:scale-[1.02] transition-transform duration-300 border border-orange-400/20 h-full">
                    <div class="absolute top-0 right-0 -mr-4 -mt-4 opacity-10 group-hover:scale-125 transition-transform duration-500">
                        <flux:icon name="bolt" class="w-24 h-24 text-white" />
                    </div>
                    <p class="text-xs font-bold text-amber-100 uppercase tracking-widest">Utilización WFM</p>
                    <p class="text-3xl font-black text-white mt-1">
                        {{ number_format(collect($performanceData)->avg('metrics.utilization_percentage'), 1) }}%
                    </p>
                    <p class="text-[10px] text-amber-50 mt-2 italic opacity-80">Productivo vs Programado</p>
                </div>
            </flux:tooltip>

            <!-- Tiempo Productivo -->
            <flux:tooltip position="top" content="Suma total de minutos en estados de atención (Ready, Talking, Work, Reserved).">
                <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-600 p-6 rounded-2xl shadow-lg group hover:scale-[1.02] transition-transform duration-300 h-full">
                    <div class="absolute top-0 right-0 -mr-4 -mt-4 opacity-10 group-hover:scale-125 transition-transform duration-500">
                        <flux:icon name="clock" class="w-24 h-24 text-white" />
                    </div>
                    <p class="text-xs font-bold text-emerald-100 uppercase tracking-widest">Tiempo Productivo</p>
                    <p class="text-3xl font-black text-white mt-1">
                        {{ number_format(collect($performanceData)->sum('metrics.total_productive_minutes'), 0) }}<span class="text-sm">m</span>
                    </p>
                    <p class="text-[10px] text-emerald-50 mt-2 italic opacity-80">Efectivo en Ready/Talking</p>
                </div>
            </flux:tooltip>

            <!-- Conexión Total -->
            <flux:tooltip position="top" content="Tiempo total acumulado de conexión en UCCX durante el periodo.">
                <div class="relative overflow-hidden bg-gradient-to-br from-slate-700 to-slate-900 p-6 rounded-2xl shadow-lg group hover:scale-[1.02] transition-transform duration-300 h-full">
                    <div class="absolute top-0 right-0 -mr-4 -mt-4 opacity-10 group-hover:scale-125 transition-transform duration-500">
                        <flux:icon name="arrow-path" class="w-24 h-24 text-white" />
                    </div>
                    <p class="text-xs font-bold text-slate-300 uppercase tracking-widest">Conexión Total</p>
                    <p class="text-3xl font-black text-white mt-1">
                        {{ number_format(collect($performanceData)->sum('metrics.total_connected_minutes'), 0) }}<span class="text-sm">m</span>
                    </p>
                    <p class="text-[10px] text-slate-400 mt-2 italic">Tiempo logueado en UCCX</p>
                </div>
            </flux:tooltip>

            <!-- Llamadas -->
            <flux:tooltip position="top" content="Cantidad total de llamadas atendidas en el periodo seleccionado.">
                <div class="relative overflow-hidden bg-gradient-to-br from-purple-600 to-fuchsia-700 p-6 rounded-2xl shadow-lg group hover:scale-[1.02] transition-transform duration-300 h-full">
                    <div class="absolute top-0 right-0 -mr-4 -mt-4 opacity-10 group-hover:scale-125 transition-transform duration-500">
                        <flux:icon name="phone" class="w-24 h-24 text-white" />
                    </div>
                    <p class="text-xs font-bold text-purple-100 uppercase tracking-widest">Llamadas</p>
                    @php 
                        $totalCalls = collect($performanceData)->flatMap(fn($d) => $d['queues'])->sum('total_calls');
                    @endphp
                    <p class="text-3xl font-black text-white mt-1">{{ $totalCalls }}</p>
                    <p class="text-[10px] text-purple-100 mt-2 italic opacity-80">Suma de todas las colas</p>
                </div>
            </flux:tooltip>
        </div>

        <div class="space-y-6">
            @foreach($performanceData as $day)
                <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden hover:shadow-2xl transition-shadow duration-300">
                    <!-- Titulo del Día -->
                    <div class="bg-slate-50/50 backdrop-blur-sm px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                @if($employeeId)
                                    <flux:icon name="calendar" class="w-5 h-5 text-blue-600" />
                                @else
                                    <flux:avatar src="{{ $day['employee']['avatar'] }}" size="xs" />
                                @endif
                            </div>
                            <div>
                                <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight leading-none">
                                    {{ $employeeId ? \Carbon\Carbon::parse($day['date'])->translatedFormat('l, d F Y') : $day['employee']['full_name'] }}
                                </h3>
                                @if(!$employeeId)
                                    <p class="text-[10px] font-bold text-slate-500 mt-1 uppercase">
                                        {{ \Carbon\Carbon::parse($day['date'])->translatedFormat('l, d F Y') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            @php
                                $statusColor = match($day['attendance']['status']) {
                                    'on_time' => 'green',
                                    'late' => 'yellow',
                                    'absent' => 'red',
                                    'exception' => 'blue',
                                    default => 'blue'
                                };
                            @endphp
                            <flux:badge size="sm" :color="$statusColor" inset="top bottom">
                                {{ $day['attendance']['status'] === 'exception' ? ($day['attendance']['exception_reason'] ?? 'EXCEPCIÓN') : strtoupper($day['attendance']['status']) }}
                            </flux:badge>
                            <div class="flex items-center gap-2 px-3 py-1 bg-blue-50 rounded-full border border-blue-100">
                                <flux:icon name="bolt" class="w-3 h-3 text-blue-500" />
                                <span class="text-xs font-black text-blue-700">Util: {{ $day['metrics']['utilization_percentage'] }}%</span>
                            </div>
                            <div class="flex items-center gap-2 px-3 py-1 bg-indigo-50 rounded-full border border-indigo-100">
                                <flux:icon name="shield-check" class="w-3 h-3 text-indigo-500" />
                                <span class="text-xs font-black text-indigo-700">Adh: {{ $day['metrics']['adherence_percentage'] }}%</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 grid grid-cols-1 lg:grid-cols-4 gap-8">
                        <!-- Columna 1: Asistencia y Entrada -->
                        <div class="space-y-4">
                            <div class="group">
                                <div class="flex items-center gap-2 mb-3">
                                    <flux:icon name="check-circle" class="w-4 h-4 text-slate-400" />
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Asistencia y Entrada</h4>
                                </div>
                                <div class="bg-slate-50 rounded-2xl p-4 space-y-3 shadow-inner">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Programada</span>
                                        <span class="font-mono font-black text-slate-700 bg-white px-2 py-1 rounded shadow-sm border border-slate-100">{{ $day['attendance']['scheduled_entry'] ?: '--:--' }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Real</span>
                                        <span class="font-mono font-black {{ $day['attendance']['actual_entry'] ? 'text-indigo-600' : 'text-slate-400' }} bg-white px-2 py-1 rounded shadow-sm border border-slate-100">
                                            {{ $day['attendance']['actual_entry'] ?: 'Sin Registro' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center border-t border-slate-200 pt-3">
                                        <span class="text-xs font-bold text-slate-500">Diferencia</span>
                                        <span class="font-black text-sm {{ $day['attendance']['diff_minutes'] > 0 ? 'text-rose-500' : 'text-emerald-500' }}">
                                            {{ $day['attendance']['diff_minutes'] > 0 ? '+' : '' }}{{ $day['attendance']['diff_minutes'] }} min
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna 2: Almuerzo y Descanso -->
                        <div class="space-y-4">
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <flux:icon name="no-symbol" class="w-4 h-4 text-slate-400" />
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Pausas Programadas</h4>
                                </div>
                                <div class="grid grid-cols-1 gap-3">
                                    <div class="bg-orange-50/50 rounded-2xl p-4 border border-orange-100 flex items-center justify-between">
                                        <div>
                                            <p class="text-[10px] font-black text-orange-600 uppercase">Almuerzo</p>
                                            <p class="text-xs font-mono font-bold text-orange-800 mt-1">Inicio: {{ $day['attendance']['lunch']['actual_start'] ?: '--:--' }}</p>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-black text-orange-900">
                                                {{ $day['attendance']['lunch']['actual_duration'] }}<span class="text-[10px] opacity-60">m</span>
                                            </div>
                                            <p class="text-[10px] font-bold text-orange-600 opacity-60">de {{ $day['attendance']['lunch']['scheduled_duration'] }}m</p>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-teal-50/50 rounded-2xl p-4 border border-teal-100 flex items-center justify-between">
                                        <div>
                                            <p class="text-[10px] font-black text-teal-600 uppercase">Descanso</p>
                                            <p class="text-xs font-mono font-bold text-teal-800 mt-1">Inicio: {{ $day['attendance']['break']['actual_start'] ?: '--:--' }}</p>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-black text-teal-900">
                                                {{ $day['attendance']['break']['actual_duration'] }}<span class="text-[10px] opacity-60">m</span>
                                            </div>
                                            <p class="text-[10px] font-bold text-teal-600 opacity-60">de {{ $day['attendance']['break']['scheduled_duration'] }}m</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna 3: Actividades UCCX -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <flux:icon name="cpu-chip" class="w-4 h-4 text-slate-400" />
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Estados y Auxiliares</h4>
                            </div>
                            <div class="space-y-4">
                                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                                    <p class="text-[10px] font-black text-slate-400 uppercase mb-3 flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        Tiempo por Estado
                                    </p>
                                    <div class="space-y-2">
                                        @forelse($day['activities'] as $state => $minutes)
                                            <div class="flex flex-col gap-1">
                                                <div class="flex justify-between text-xs">
                                                    <span class="font-bold text-slate-600">{{ $state }}</span>
                                                    <span class="font-black text-slate-900">{{ $this->formatMinutes($minutes) }}</span>
                                                </div>
                                                <div class="h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-blue-400 rounded-full" style="width: {{ min(100, ($minutes / 480) * 100) }}%"></div>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-xs text-slate-400 italic">Sin actividad UCCX registrada</p>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                                    <p class="text-[10px] font-black text-slate-400 uppercase mb-3 flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                                        Motivos de Auxiliar
                                    </p>
                                    <div class="space-y-2">
                                        @forelse($day['reasons'] as $reason => $data)
                                            <div class="flex justify-between text-xs items-center group py-1">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-orange-100 text-orange-700 font-black text-[9px] shrink-0" title="{{ $data['count'] }} ocurrencia(s)">{{ $data['count'] }}</span>
                                                    <span class="text-slate-600 font-medium group-hover:text-slate-900 transition-colors truncate">{{ $reason ?: 'Sin Motivo' }}</span>
                                                </div>
                                                <span class="font-black text-slate-700 bg-slate-50 px-1.5 py-0.5 rounded shrink-0 ml-2">{{ $this->formatMinutes($data['minutes']) }}</span>
                                            </div>
                                        @empty
                                            <p class="text-xs text-slate-400 italic">No se registraron auxiliares</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna 4: Volumen por Cola -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <flux:icon name="phone-arrow-down-left" class="w-4 h-4 text-slate-400" />
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Rendimiento por Cola</h4>
                            </div>
                            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                                <table class="w-full text-xs">
                                    <thead class="bg-slate-50 text-slate-400">
                                        <tr>
                                            <th class="text-left font-black p-3 uppercase text-[9px] tracking-widest">Cola</th>
                                            <th class="text-right font-black p-3 uppercase text-[9px] tracking-widest">Llam.</th>
                                            <th class="text-right font-black p-3 uppercase text-[9px] tracking-widest">AHT</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        @foreach($day['queues'] as $queueName => $stats)
                                            <tr class="hover:bg-slate-50 transition-colors">
                                                <td class="p-3 text-slate-700 font-bold max-w-[120px] truncate" title="{{ $queueName }}">{{ $queueName }}</td>
                                                <td class="p-3 text-right">
                                                    <span class="font-black text-purple-600 bg-purple-50 px-2 py-1 rounded-lg">{{ $stats['total_calls'] }}</span>
                                                </td>
                                                <td class="p-3 text-right text-slate-500 font-mono">{{ round($stats['avg_handle_time']) }}s</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white/50 backdrop-blur-md rounded-3xl border-2 border-dashed border-slate-200 p-20 text-center shadow-xl">
            <div class="bg-slate-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                <flux:icon name="presentation-chart-line" class="w-10 h-10 text-slate-400" />
            </div>
            <flux:heading size="lg">Sin datos de desempeño</flux:heading>
            <flux:subheading class="mt-2 max-w-sm mx-auto">Seleccione un operador y periodo en los filtros superiores para visualizar las métricas detalladas.</flux:subheading>
        </div>
    @endif
</div>
