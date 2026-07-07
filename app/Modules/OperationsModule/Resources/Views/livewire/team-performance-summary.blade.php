<div class="space-y-8">
    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">
                {{ match($view) {
                    'attendance' => 'Asistencia y Puntualidad',
                    'compliance' => 'Cumplimiento de Horario',
                    default => 'Desempeño del Equipo'
                } }}
            </flux:heading>
            <flux:subheading>
                {{ match($view) {
                    'attendance' => 'Análisis de entradas, salidas y puntualidad histórica',
                    'compliance' => 'Métricas de productividad, utilización y adherencia',
                    default => 'Monitoreo consolidado de KPIs y adherencia operativa'
                } }}
            </flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:select wire:model.live="teamId" class="w-64">
                <option value="">Selecciona un equipo</option>
                @foreach($teams as $team)
                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                @endforeach
            </flux:select>

            <div class="flex items-center gap-2">
                <flux:input type="date" wire:model.live="selectedDate" />
                <flux:button variant="ghost" size="sm" wire:click="$set('selectedDate', '{{ now()->toDateString() }}')"
                    icon="calendar-days">
                    Hoy
                </flux:button>
            </div>
        </div>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @if($view === 'summary' || $view === 'attendance')
            <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-red-600 hover:shadow-md transition-opacity">
                <div class="flex justify-between items-start">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ausentismo</span>
                    <div class="flex flex-col items-end gap-1">
                        <flux:badge size="sm" color="red" variant="subtle" class="rounded-md" title="Ausencias injustificadas">
                            {{ $teamTotals['absent_count'] ?? 0 }}
                        </flux:badge>
                        @if(($teamTotals['exception_count'] ?? 0) > 0)
                            <flux:badge size="sm" color="blue" variant="subtle" class="rounded-md" title="Excepciones programadas (Excluidas)">
                                +{{ $teamTotals['exception_count'] }} Exc
                            </flux:badge>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="user-minus" size="sm" class="text-red-600" />
                    <span
                        class="text-2xl font-bold text-slate-900">{{ number_format($teamTotals['absenteeism'], 1) }}%</span>
                </div>
            </flux:card>
        @endif

        @if($view === 'summary' || $view === 'compliance')
            <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-blue-600 hover:shadow-md transition-opacity">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Utilización</span>
                <div class="flex items-center gap-2">
                    <flux:icon name="bolt" size="sm" class="text-blue-600" />
                    <span
                        class="text-2xl font-bold text-slate-900">{{ number_format($teamTotals['utilization'], 1) }}%</span>
                </div>
            </flux:card>

            <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-green-600 hover:shadow-md transition-opacity">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Productividad</span>
                <div class="flex items-center gap-2">
                    <flux:icon name="chart-bar" size="sm" class="text-green-600" />
                    <span
                        class="text-2xl font-bold text-slate-900">{{ number_format($teamTotals['productivity'], 1) }}%</span>
                </div>
            </flux:card>
        @endif

        <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-blue-600 hover:shadow-md transition-opacity">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">T. Productivo</span>
            <div class="flex items-center gap-2">
                <flux:icon name="clock" size="sm" class="text-blue-600" />
                <span
                    class="text-2xl font-bold text-slate-900">{{ $this->formatMinutes($teamTotals['total_productive']) }}</span>
            </div>
        </flux:card>

        @if($view === 'summary' || $view === 'compliance')
            <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-slate-600 hover:shadow-md transition-opacity">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Conexión Total</span>
                <div class="flex items-center gap-2">
                    <flux:icon name="link" size="sm" class="text-slate-600" />
                    <span
                        class="text-2xl font-bold text-slate-900">{{ $this->formatMinutes($teamTotals['total_connected']) }}</span>
                </div>
            </flux:card>

            <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-amber-500 hover:shadow-md transition-opacity">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Llamadas</span>
                <div class="flex items-center gap-2">
                    <flux:icon name="phone" size="sm" class="text-amber-500" />
                    <span class="text-2xl font-bold text-slate-900">{{ number_format($teamTotals['total_calls']) }}</span>
                </div>
            </flux:card>
        @endif
    </div>

    <flux:card class="!p-3 overflow-hidden border-slate-200">
        <flux:table>
            <flux:table.columns>
                <flux:table.column 
                    sortable 
                    :direction="$sortField === 'operator' ? $sortDirection : null" 
                    wire:click="sortBy('operator')"
                >
                    Operador
                </flux:table.column>

                @if($view === 'summary' || $view === 'attendance')
                    <flux:table.column class="sticky top-0 z-10">Entrada (Real)</flux:table.column>
                    <flux:table.column class="sticky top-0 z-10">Estado</flux:table.column>
                @endif

                @if($view === 'summary' || $view === 'compliance')
                    <flux:table.column class="sticky top-0 z-10">TP / TC</flux:table.column>
                    <flux:table.column 
                        sortable 
                        :direction="$sortField === 'productivity' ? $sortDirection : null" 
                        wire:click="sortBy('productivity')"
                    >
                        Productividad
                    </flux:table.column>
                    <flux:table.column 
                        sortable 
                        :direction="$sortField === 'utilization' ? $sortDirection : null" 
                        wire:click="sortBy('utilization')"
                    >
                        Utilización WFM
                    </flux:table.column>
                @endif
                @if($view === 'summary' || $view === 'attendance')
                    <flux:table.column class="sticky top-0 z-10">Logout</flux:table.column>
                @endif
                <flux:table.column align="end"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($teamPerformance as $row)
                    @php
                        $metrics = $row['performance']['metrics'];
                        $attendance = $row['performance']['attendance'];
                        $statusColor = match ($attendance['status']) {
                            'a_tiempo' => 'green',
                            'tardanza' => 'amber',
                            'ausente' => 'red',
                            'excepción' => 'blue',
                            default => 'slate'
                        };

                        $productivity = $metrics['productivity_percentage'];
                        $prodColor = $productivity >= 85 ? 'green' : ($productivity >= 75 ? 'amber' : 'red');

                        $utilization = $metrics['utilization_percentage'] ?? 0;
                        $utilColor = $utilization >= 90 ? 'green' : ($utilization >= 80 ? 'amber' : 'red');
                    @endphp
                    <flux:table.row class="hover:bg-slate-50 transition-colors duration-150" :key="$row['employee']['id']">
                        <flux:table.cell class="py-2">
                            <div class="flex items-center gap-3">
                                <flux:avatar size="sm" :name="$row['employee']['full_name']" class="bg-slate-100 rounded-md" />
                                <div class="flex flex-col">
                                    <span
                                        class="font-semibold text-slate-900 leading-none">{{ $row['employee']['full_name'] }}</span>
                                    <span class="text-[10px] text-slate-400 mt-1 uppercase tracking-wider">Agente</span>
                                </div>
                            </div>
                        </flux:table.cell>

                        @if($view === 'summary' || $view === 'attendance')
                            <flux:table.cell class="py-2">
                                <div class="flex flex-col">
                                    <span
                                        class="text-sm font-semibold text-slate-700">{{ $attendance['actual_entry'] ?: '--:--' }}</span>
                                    <span class="text-[10px] text-slate-400">P:
                                        {{ $attendance['scheduled_entry'] ?: '--:--' }}</span>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="py-2">
                                <flux:badge size="sm" :color="$statusColor" variant="subtle" class="rounded-md">
                                    {{ $attendance['status'] === 'excepción' ? ($attendance['exception_reason'] ?? 'EXCEPCIÓN') : strtoupper($attendance['status']) }}
                                </flux:badge>
                            </flux:table.cell>
                        @endif

                        @if($view === 'summary' || $view === 'compliance')
                            <flux:table.cell class="py-2">
                                <div class="flex flex-col gap-1 min-w-[120px]">
                                    <div
                                        class="flex justify-between items-center text-[10px] font-bold text-slate-600 uppercase tracking-tighter">
                                        <span>TP: {{ $this->formatMinutes($metrics['total_productive_minutes']) }}</span>
                                        <span>TC: {{ $this->formatMinutes($metrics['total_connected_minutes']) }}</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-slate-100 rounded-md overflow-hidden">
                                        <div class="h-full bg-blue-600 rounded-md" style="width: {{ $productivity }}%"></div>
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="py-2">
                                <flux:badge size="sm" :color="$prodColor" variant="solid" class="rounded-md">
                                    {{ number_format($productivity, 1) }}%
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell class="py-2">
                                <div class="flex items-center gap-2">
                                    <flux:icon name="bolt" size="sm"
                                        class="{{ $utilColor === 'red' ? 'text-red-400' : ($utilColor === 'orange' ? 'text-amber-500' : 'text-green-400') }}" />
                                    <span
                                        class="text-sm font-black {{ $utilColor === 'red' ? 'text-red-600' : ($utilColor === 'orange' ? 'text-amber-600' : 'text-green-600') }}">
                                        {{ number_format($utilization, 1) }}%
                                    </span>
                                </div>
                            </flux:table.cell>
                        @endif

                        @if($view === 'summary' || $view === 'attendance')
                            <flux:table.cell class="py-2">
                                <span
                                    class="text-xs font-mono {{ $metrics['total_logout_minutes'] > 30 ? 'text-red-600 font-bold' : 'text-slate-600' }}">
                                    {{ $this->formatMinutes($metrics['total_logout_minutes']) }}
                                </span>
                            </flux:table.cell>
                        @endif

                        <flux:table.cell align="end">
                            <flux:button size="xs" variant="ghost" icon="chart-bar"
                                href="{{ route('operations.performance', ['employeeId' => $row['employee']['id'], 'date' => $selectedDate]) }}"
                                wire:navigate />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center py-20">
                            <div class="flex flex-col items-center gap-3">
                                <div class="p-4 bg-slate-50 rounded-full">
                                    <flux:icon name="magnifying-glass" class="w-10 h-10 text-slate-300" />
                                </div>
                                <div class="flex flex-col items-center">
                                    <span class="text-slate-900 font-bold">Sin registros de desempeño</span>
                                    <span class="text-slate-600 text-sm">No se encontraron datos para los filtros
                                        seleccionados</span>
                                </div>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>