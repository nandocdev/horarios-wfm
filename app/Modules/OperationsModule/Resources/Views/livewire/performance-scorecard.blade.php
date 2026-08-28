<div class="space-y-8">
    <header data-tour="performance-header" class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-2">
            <div>
                <flux:heading size="xl" level="1">Desempeño {{ $employeeId ? ($selectedEmployee?->full_name ?? ($selectedEmployee?->getFullName() ?? (collect($employees)->firstWhere('id', (int)$employeeId)?->full_name ?? 'Seleccionado'))) : 'Selecciona un empleado' }}</flux:heading>
                <flux:subheading>Análisis de adherencia y productividad de Contact Center</flux:subheading>
            </div>

            <flux:modal.trigger name="calculation-formulas">
                <flux:button variant="ghost" icon="information-circle" size="sm" />
            </flux:modal.trigger>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-wfm.tour-button :tour="'operations.performance'" />
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre..." icon="magnifying-glass" class="w-64" />

            <flux:select wire:model.live="teamId" placeholder="Todos los Equipos" class="w-56">
                <x-slot name="icon">
                    <flux:icon name="users" variant="micro" />
                </x-slot>
                <flux:select.option value="">Todos los Equipos</flux:select.option>
                @foreach($teams as $team)
                    <flux:select.option value="{{ $team->id }}">{{ $team->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="employeeId" placeholder="Seleccione Operador" class="w-56">
                <x-slot name="icon">
                    <flux:icon name="user" variant="micro" />
                </x-slot>
                <flux:select.option value="">(Ver todos en lista)</flux:select.option>
                @foreach($employees as $employee)
                    <flux:select.option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->username }})</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="periodType" class="w-32">
                <flux:select.option value="daily">Diario</flux:select.option>
                <flux:select.option value="weekly">Semanal</flux:select.option>
                <flux:select.option value="monthly">Mensual</flux:select.option>
            </flux:select>

            <flux:input type="date" wire:model.live="date" />
        </div>
    </header>

    <flux:modal name="calculation-formulas" variant="flyout" class="space-y-4">
        <div>
            <flux:heading size="lg">Diccionario de Fórmulas</flux:heading>
            <flux:subheading>Detalle técnico de los cálculos de desempeño</flux:subheading>
        </div>

        <div class="space-y-4 overflow-y-auto max-h-[70vh] pr-2">
            <div class="space-y-3">
                <flux:heading size="sm">Glosario de Variables</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <flux:card class="flex items-center gap-2 p-2">
                        <flux:badge size="sm" color="blue" variant="solid">TP</flux:badge>
                        <span class="text-sm text-slate-600"><strong>Tiempo Productivo:</strong> Ready + Talking + Work + Reserved</span>
                    </flux:card>
                    <flux:card class="flex items-center gap-2 p-2">
                        <flux:badge size="sm" color="slate" variant="solid">TC</flux:badge>
                        <span class="text-sm text-slate-600"><strong>Tiempo de Conexión:</strong> Tiempo total logueado en UCCX</span>
                    </flux:card>
                    <flux:card class="flex items-center gap-2 p-2">
                        <flux:badge size="sm" color="slate" variant="solid">JP</flux:badge>
                        <span class="text-sm text-slate-600"><strong>Jornada Programada:</strong> Minutos totales asignados en el horario</span>
                    </flux:card>
                    <flux:card class="flex items-center gap-2 p-2">
                        <flux:badge size="sm" color="amber" variant="solid">TT</flux:badge>
                        <span class="text-sm text-slate-600"><strong>Tiempo Transcurrido:</strong> Minutos desde Inicio Programado hasta Ahora</span>
                    </flux:card>
                    <flux:card class="flex items-center gap-2 p-2">
                        <flux:badge size="sm" color="green" variant="solid">IR</flux:badge>
                        <span class="text-sm text-slate-600"><strong>Inicio Real:</strong> Primer estado activo detectado (&gt;10s)</span>
                    </flux:card>
                </div>
            </div>

            <flux:separator />

            <div class="space-y-3">
                <flux:heading size="sm">Eficiencia Operativa</flux:heading>
                <flux:card class="p-4">
                    <flux:heading size="md">Productividad (%)</flux:heading>
                    <p class="text-sm text-slate-500 mt-1 italic font-mono">(TP / TC) × 100</p>
                    <p class="text-xs text-slate-400 mt-2">Mide qué tan ocupado estuvo el agente mientras estuvo logueado.</p>
                </flux:card>
                <flux:card class="p-4 border-amber-200">
                    <flux:heading size="md">Utilización WFM (%)</flux:heading>
                    <p class="text-sm text-amber-700 mt-1 italic font-mono">Real-time: (TP / TT) × 100 | Histórico: (TP / JP) × 100</p>
                    <p class="text-xs text-amber-600 mt-2">Mide el rendimiento real contra lo planificado (afectado por tardanzas y desconexiones).</p>
                </flux:card>
                <flux:card class="p-4 border-blue-200">
                    <flux:heading size="md">Adherencia (%)</flux:heading>
                    <p class="text-sm text-blue-700 mt-1 italic font-mono">(Minutos Adherentes / Jornada Programada) × 100</p>
                    <p class="text-xs text-blue-600 mt-2">Mide el apego estricto al cronograma: estar en el estado correcto en el momento correcto.</p>
                </flux:card>
            </div>

            <div class="space-y-3">
                <flux:heading size="sm">Conexión y Logout</flux:heading>
                <flux:card class="p-4">
                    <flux:heading size="md">Logout (Desconexión) - Real Time</flux:heading>
                    <p class="text-sm text-slate-500 mt-1 italic font-mono">(Ahora - Inicio Real) - Tiempo Conexión</p>
                    <p class="text-xs text-slate-400 mt-2">Calcula la brecha de desconexión acumulada durante el turno actual.</p>
                </flux:card>
                <flux:card class="p-4">
                    <flux:heading size="md">Logout (Desconexión) - Histórico</flux:heading>
                    <p class="text-sm text-slate-500 mt-1 italic font-mono">Jornada Programada - Tiempo Conexión</p>
                    <p class="text-xs text-slate-400 mt-2">Muestra el tiempo total de desconexión al finalizar el día.</p>
                </flux:card>
            </div>

            <div class="space-y-3">
                <flux:heading size="sm">Asistencia y Llamadas</flux:heading>
                <flux:card class="p-4">
                    <flux:heading size="md">AHT (Average Handle Time)</flux:heading>
                    <p class="text-sm text-slate-500 mt-1 italic font-mono">(Talk Time + Work Time) / Llamadas Atendidas</p>
                </flux:card>
                <flux:card class="p-4">
                    <flux:heading size="md">Estatus de Asistencia</flux:heading>
                    <ul class="text-sm text-slate-500 mt-2 list-disc pl-4 space-y-1">
                        <li><strong>ON TIME:</strong> Entrada ≤ Programada + 5 min.</li>
                        <li><strong>LATE:</strong> Entrada > Programada + 5 min.</li>
                        <li><strong>ABSENT:</strong> Sin registro de actividad UCCX (No justificado).</li>
                        <li><strong>EXCEPTION:</strong> Excepción programada (Vacaciones, Licencia, etc.). Excluido del cálculo de ausentismo.</li>
                    </ul>
                </flux:card>
            </div>
        </div>

        <div class="flex justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Entendido</flux:button>
            </flux:modal.close>
        </div>
    </flux:modal>

    @if(!empty($performanceData))
        <div data-tour="performance-metrics" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <flux:tooltip position="top" content="Intensidad: Tiempo productivo vs Tiempo total de conexión.">
                <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-blue-600">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Productividad</span>
                    <span class="text-3xl font-black text-slate-900">
                        {{ number_format(collect($performanceData)->avg('metrics.productivity_percentage'), 1) }}%
                    </span>
                    @php $prodGoal = collect($performanceData)->first()['goals']['goal_productivity'] ?? null; @endphp
                    @if($prodGoal)
                        <span class="text-xs text-slate-500">Meta: {{ $prodGoal }}%</span>
                    @endif
                    <div class="h-1 w-full bg-slate-200 rounded-md overflow-hidden">
                        <div class="h-full bg-blue-600 rounded-md" style="width: {{ collect($performanceData)->avg('metrics.productivity_percentage') }}%"></div>
                    </div>
                </flux:card>
            </flux:tooltip>

            <flux:tooltip position="top" content="Disciplina: Tiempo productivo generado dentro de su horario programado.">
                <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-green-600">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Adherencia</span>
                    <span class="text-3xl font-black text-slate-900">
                        {{ number_format(collect($performanceData)->avg('metrics.adherence_percentage'), 1) }}%
                    </span>
                    @php $adhGoal = collect($performanceData)->first()['goals']['goal_adherence'] ?? null; @endphp
                    @if($adhGoal)
                        <span class="text-xs text-slate-500">Meta: {{ $adhGoal }}%</span>
                    @endif
                    <div class="h-1 w-full bg-slate-200 rounded-md overflow-hidden">
                        <div class="h-full bg-green-600 rounded-md" style="width: {{ collect($performanceData)->avg('metrics.adherence_percentage') }}%"></div>
                    </div>
                </flux:card>
            </flux:tooltip>

            <flux:tooltip position="top" content="Rendimiento: Producción real comparada contra la jornada completa planificada.">
                <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-amber-500">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Utilización</span>
                    <span class="text-3xl font-black text-slate-900">
                        {{ number_format(collect($performanceData)->avg('metrics.utilization_percentage'), 1) }}%
                    </span>
                    @php $utilGoal = collect($performanceData)->first()['goals']['goal_utilization'] ?? null; @endphp
                    @if($utilGoal)
                        <span class="text-xs text-slate-500">Meta: {{ $utilGoal }}%</span>
                    @else
                        <span class="text-xs text-slate-400 italic">Productivo vs Programado</span>
                    @endif
                    <div class="h-1 w-full bg-slate-200 rounded-md overflow-hidden">
                        <div class="h-full bg-amber-500 rounded-md" style="width: {{ collect($performanceData)->avg('metrics.utilization_percentage') }}%"></div>
                    </div>
                </flux:card>
            </flux:tooltip>

            <flux:tooltip position="top" content="Suma total de minutos en estados de atención (Ready, Talking, Work, Reserved).">
                <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-blue-600">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tiempo Productivo</span>
                    <span class="text-3xl font-black text-slate-900">
                        {{ $this->formatMinutes(collect($performanceData)->sum('metrics.total_productive_minutes')) }}
                    </span>
                    <span class="text-xs text-slate-400 italic">Efectivo en Ready/Talking</span>
                </flux:card>
            </flux:tooltip>

            <flux:tooltip position="top" content="Tiempo total acumulado de conexión en UCCX durante el periodo.">
                <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-slate-500">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Conexión Total</span>
                    <span class="text-3xl font-black text-slate-900">
                        {{ $this->formatMinutes(collect($performanceData)->sum('metrics.total_connected_minutes')) }}
                    </span>
                    <span class="text-xs text-slate-400 italic">Tiempo logueado en UCCX</span>
                </flux:card>
            </flux:tooltip>

            <flux:tooltip position="top" content="Cantidad total de llamadas atendidas en el periodo seleccionado.">
                <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-blue-600">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Llamadas</span>
                    @php
                        $totalCalls = collect($performanceData)->flatMap(fn($d) => array_values($d['queues']))->sum('total_calls');
                    @endphp
                    <span class="text-3xl font-black text-slate-900">{{ $totalCalls }}</span>
                    <span class="text-xs text-slate-400 italic">Suma de todas las colas</span>
                </flux:card>
            </flux:tooltip>
        </div>

        <div data-tour="performance-detail" class="space-y-4">
            @foreach($performanceData as $day)
                <flux:card class="overflow-hidden">
                    <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            @if($employeeId)
                                <div class="p-2 bg-blue-100 rounded-md">
                                    <flux:icon name="calendar" class="w-5 h-5 text-blue-600" />
                                </div>
                            @else
                                <flux:avatar src="{{ $day['employee']['avatar'] }}" size="sm" />
                            @endif
                            <div>
                                <flux:heading size="md" class="!leading-none">{{ $employeeId ? \Carbon\Carbon::parse($day['date'])->translatedFormat('l, d F Y') : $day['employee']['full_name'] }}</flux:heading>
                                @if(!$employeeId)
                                    <flux:subheading class="!text-[10px]">{{ \Carbon\Carbon::parse($day['date'])->translatedFormat('l, d F Y') }}</flux:subheading>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            @php
                                $statusColor = match($day['attendance']['status']) {
                                    'on_time' => 'green',
                                    'late' => 'amber',
                                    'absent' => 'red',
                                    'exception' => 'blue',
                                    default => 'blue'
                                };
                            @endphp
                            <flux:badge size="sm" :color="$statusColor">
                                {{ $day['attendance']['status'] === 'exception' ? ($day['attendance']['exception_reason'] ?? 'EXCEPCIÓN') : strtoupper($day['attendance']['status']) }}
                            </flux:badge>
                            <flux:badge size="sm" color="blue" inset="top bottom">
                                <flux:icon name="bolt" variant="mini" class="w-3 h-3" />
                                Util: {{ $day['metrics']['utilization_percentage'] }}%
                            </flux:badge>
                            <flux:badge size="sm" color="blue" inset="top bottom">
                                <flux:icon name="shield-check" variant="mini" class="w-3 h-3" />
                                Adh: {{ $day['metrics']['adherence_percentage'] }}%
                            </flux:badge>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">
                        {{-- Sección Superior: Métricas de Entrada y Estado --}}
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {{-- Asistencia y Entrada --}}
                            <div class="space-y-4">
                                <flux:heading size="sm" class="flex items-center gap-2">
                                    <flux:icon name="check-circle" size="sm" class="text-slate-400" />
                                    Asistencia y Entrada
                                </flux:heading>
                                <flux:card class="p-4 space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Programada</span>
                                        <span class="font-mono font-semibold text-slate-700">{{ $day['attendance']['scheduled_entry'] ?: '--:--' }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Real</span>
                                        <span class="font-mono font-semibold {{ $day['attendance']['actual_entry'] ? 'text-blue-600' : 'text-slate-400' }}">
                                            {{ $day['attendance']['actual_entry'] ?: 'Sin Registro' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center border-t border-slate-200 pt-3">
                                        <span class="text-xs font-bold text-slate-500">Diferencia</span>
                                        <span class="font-semibold text-sm {{ $day['attendance']['diff_minutes'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ $day['attendance']['diff_minutes'] > 0 ? '+' : '' }}{{ $day['attendance']['diff_minutes'] }} min
                                        </span>
                                    </div>
                                </flux:card>
                            </div>

                            {{-- Pausas Programadas --}}
                            <div class="space-y-4">
                                <flux:heading size="sm" class="flex items-center gap-2">
                                    <flux:icon name="no-symbol" size="sm" class="text-slate-400" />
                                    Pausas Programadas
                                </flux:heading>
                                <div class="space-y-3">
                                    <flux:card class="p-4 border-amber-200">
                                        <div class="flex justify-between items-start mb-3">
                                            <div>
                                                <flux:heading size="sm">Almuerzo</flux:heading>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="text-xs text-slate-400">Prog:</span>
                                                    <span class="text-xs font-mono font-bold text-slate-600">{{ $day['attendance']['lunch']['scheduled_start'] ?: '--:--' }}</span>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-lg font-bold text-amber-900 leading-none">
                                                    {{ $this->formatMinutes($day['attendance']['lunch']['actual_duration']) }}
                                                </div>
                                                <p class="text-xs text-amber-600">de {{ $day['attendance']['lunch']['scheduled_duration'] }}m</p>
                                            </div>
                                        </div>
                                        <div class="flex justify-between items-center pt-2 border-t border-amber-200">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-slate-400">Real:</span>
                                                <span class="text-xs font-mono font-bold text-amber-600">{{ $day['attendance']['lunch']['actual_start'] ?: '--:--' }}</span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="text-xs text-slate-400">Dif:</span>
                                                <span class="text-xs font-semibold {{ $day['attendance']['lunch']['diff_minutes'] > 5 ? 'text-red-600' : ($day['attendance']['lunch']['diff_minutes'] > 0 ? 'text-amber-600' : 'text-emerald-600') }}">
                                                    {{ $day['attendance']['lunch']['diff_minutes'] > 0 ? '+' : '' }}{{ $day['attendance']['lunch']['diff_minutes'] }} min
                                                </span>
                                            </div>
                                        </div>
                                    </flux:card>

                                    <flux:card class="p-4">
                                        <div class="flex justify-between items-start mb-3">
                                            <div>
                                                <flux:heading size="sm">Descanso</flux:heading>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="text-xs text-slate-400">Prog:</span>
                                                    <span class="text-xs font-mono font-bold text-slate-600">{{ $day['attendance']['break']['scheduled_start'] ?: '--:--' }}</span>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-lg font-bold text-slate-900 leading-none">
                                                    {{ $this->formatMinutes($day['attendance']['break']['actual_duration']) }}
                                                </div>
                                                <p class="text-xs text-slate-500">de {{ $day['attendance']['break']['scheduled_duration'] }}m</p>
                                            </div>
                                        </div>
                                        <div class="flex justify-between items-center pt-2 border-t border-slate-200">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-slate-400">Real:</span>
                                                <span class="text-xs font-mono font-bold text-slate-600">{{ $day['attendance']['break']['actual_start'] ?: '--:--' }}</span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="text-xs text-slate-400">Dif:</span>
                                                <span class="text-xs font-semibold {{ $day['attendance']['break']['diff_minutes'] > 5 ? 'text-red-600' : ($day['attendance']['break']['diff_minutes'] > 0 ? 'text-amber-600' : 'text-emerald-600') }}">
                                                    {{ $day['attendance']['break']['diff_minutes'] > 0 ? '+' : '' }}{{ $day['attendance']['break']['diff_minutes'] }} min
                                                </span>
                                            </div>
                                        </div>
                                    </flux:card>
                                </div>
                            </div>

                            {{-- Tiempo por Estado --}}
                            <div class="space-y-4">
                                <flux:heading size="sm" class="flex items-center gap-2">
                                    <flux:icon name="cpu-chip" size="sm" class="text-slate-400" />
                                    Tiempo por Estado
                                </flux:heading>
                                <flux:card class="p-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-y-3 gap-x-6">
                                        @forelse($day['activities'] as $state => $minutes)
                                            <div class="flex flex-col gap-1">
                                                <div class="flex justify-between text-xs">
                                                    <span class="font-bold text-slate-600 truncate">{{ $state }}</span>
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
                                </flux:card>
                            </div>
                        </div>

                        {{-- Sección Inferior: Motivos de Auxiliar, Historial de Desconexiones y Rendimiento por Cola --}}
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                            {{-- Motivos de Auxiliar --}}
                            <div class="space-y-4">
                                <flux:heading size="sm" class="flex items-center gap-2">
                                    <flux:icon name="pause" size="sm" class="text-amber-500" />
                                    Motivos de Auxiliar
                                </flux:heading>
                                <flux:card class="p-4">
                                    <div class="space-y-2 max-h-64 overflow-y-auto pr-2">
                                        @forelse($day['reasons'] as $reason => $data)
                                            <div class="flex justify-between text-xs items-center group py-1 border-b border-slate-50 last:border-0">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <flux:badge size="xs" color="amber" variant="solid">{{ $data['count'] }}</flux:badge>
                                                    <span class="text-slate-600 font-medium group-hover:text-slate-900 truncate">{{ $reason ?: 'Sin Motivo' }}</span>
                                                </div>
                                                <span class="font-black text-slate-700 shrink-0 ml-2">{{ $this->formatMinutes($data['minutes']) }}</span>
                                            </div>
                                        @empty
                                            <p class="text-xs text-slate-400 italic">No se registraron auxiliares</p>
                                        @endforelse
                                    </div>
                                </flux:card>
                            </div>

                            {{-- Historial de Desconexiones --}}
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <flux:heading size="sm" class="flex items-center gap-2">
                                        <flux:icon name="arrow-right-start-on-rectangle" size="sm" class="text-red-500" />
                                        Historial de Desconexiones
                                    </flux:heading>
                                    <flux:badge size="xs" color="red" variant="solid">{{ count($day['logout_details'] ?? []) }}</flux:badge>
                                </div>
                                <flux:card class="p-4">
                                    <div class="space-y-2 max-h-64 overflow-y-auto pr-2">
                                        @forelse($day['logout_details'] ?? [] as $logout)
                                            <div class="flex justify-between text-xs items-center group py-1 border-b border-slate-50 last:border-0">
                                                <div class="flex items-center gap-2">
                                                    <flux:icon name="arrow-right-start-on-rectangle" variant="mini" class="w-3 h-3 text-red-500" />
                                                    <span class="text-slate-500 font-mono">{{ $logout['start_time'] }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <flux:badge size="xs" color="red">{{ $this->formatMinutes($logout['duration_minutes']) }}</flux:badge>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-xs text-slate-400 italic">No se detectaron desconexiones</p>
                                        @endforelse
                                    </div>
                                </flux:card>
                            </div>

                            {{-- Rendimiento por Cola (Ajusta exactamente al contenido de su tabla) --}}
                            <div class="space-y-4">
                                <flux:heading size="sm" class="flex items-center gap-2">
                                    <flux:icon name="phone-arrow-down-left" size="sm" class="text-slate-400" />
                                    Rendimiento por Cola
                                </flux:heading>
                                <flux:card class="!p-0 overflow-hidden">
                                    <flux:table>
                                        <flux:table.columns>
                                            <flux:table.column>Cola</flux:table.column>
                                            <flux:table.column align="end">Llam.</flux:table.column>
                                            <flux:table.column align="end">AHT</flux:table.column>
                                        </flux:table.columns>

                                        <flux:table.rows>
                                            @foreach($day['queues'] as $queueName => $stats)
                                                <flux:table.row :key="$queueName">
                                                    <flux:table.cell class="font-bold text-slate-700 max-w-[120px] truncate" :title="$queueName">{{ $queueName }}</flux:table.cell>
                                                    <flux:table.cell align="end">
                                                        <flux:badge size="xs" color="blue" variant="solid">{{ $stats['total_calls'] }}</flux:badge>
                                                    </flux:table.cell>
                                                    <flux:table.cell align="end" class="font-mono">{{ round($stats['avg_handle_time']) }}s</flux:table.cell>
                                                </flux:table.row>
                                            @endforeach
                                        </flux:table.rows>
                                    </flux:table>
                                </flux:card>
                            </div>
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @else
        <flux:card class="border-2 border-dashed border-slate-200 p-20 text-center">
            <div class="bg-slate-50 w-20 h-20 rounded-md border border-slate-200 flex items-center justify-center mx-auto mb-6">
                <flux:icon name="presentation-chart-line" class="w-10 h-10 text-slate-400" />
            </div>
            <flux:heading size="lg">Sin datos de desempeño</flux:heading>
            <flux:subheading class="mt-2 max-w-sm mx-auto">Seleccione un operador y periodo en los filtros superiores para visualizar las métricas detalladas.</flux:subheading>
        </flux:card>
    @endif
</div>
