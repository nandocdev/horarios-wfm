<div class="space-y-8">
    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Desempeño del Equipo</flux:heading>
            <flux:subheading>Monitoreo consolidado de KPIs y adherencia operativa</flux:subheading>
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
        <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-red-500">
            <div class="flex justify-between items-start">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ausentismo</span>
                <div class="flex flex-col items-end gap-1">
                    <flux:badge size="sm" color="red" variant="pill" title="Ausencias injustificadas">
                        {{ $teamTotals['absent_count'] ?? 0 }}
                    </flux:badge>
                    @if(($teamTotals['exception_count'] ?? 0) > 0)
                        <flux:badge size="sm" color="blue" variant="pill" title="Excepciones programadas (Excluidas)">
                            +{{ $teamTotals['exception_count'] }} Exc
                        </flux:badge>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <flux:icon name="user-minus" size="sm" class="text-red-500" />
                <span
                    class="text-2xl font-black text-slate-900">{{ number_format($teamTotals['absenteeism'], 1) }}%</span>
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-blue-500">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Utilización</span>
            <div class="flex items-center gap-2">
                <flux:icon name="bolt" size="sm" class="text-blue-500" />
                <span
                    class="text-2xl font-black text-slate-900">{{ number_format($teamTotals['utilization'], 1) }}%</span>
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-green-500">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Productividad</span>
            <div class="flex items-center gap-2">
                <flux:icon name="chart-bar" size="sm" class="text-green-500" />
                <span
                    class="text-2xl font-black text-slate-900">{{ number_format($teamTotals['productivity'], 1) }}%</span>
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-slate-500">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">T. Productivo</span>
            <div class="flex items-center gap-2">
                <flux:icon name="clock" size="sm" class="text-slate-500" />
                <span
                    class="text-2xl font-black text-slate-900">{{ $this->formatMinutes($teamTotals['total_productive']) }}</span>
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-slate-500">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Conexión Total</span>
            <div class="flex items-center gap-2">
                <flux:icon name="link" size="sm" class="text-slate-500" />
                <span
                    class="text-2xl font-black text-slate-900">{{ $this->formatMinutes($teamTotals['total_connected']) }}</span>
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-1 p-4 border-l-4 border-l-slate-500">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Llamadas</span>
            <div class="flex items-center gap-2">
                <flux:icon name="phone" size="sm" class="text-slate-500" />
                <span class="text-2xl font-black text-slate-900">{{ number_format($teamTotals['total_calls']) }}</span>
            </div>
        </flux:card>
    </div>

    <flux:card class="!p-3 overflow-hidden border-slate-200">
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Operador</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Entrada (Real)</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Estado</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">TP / TC</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Productividad</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Utilización WFM</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Logout</flux:table.column>
                <flux:table.column align="end" class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($teamPerformance as $row)
                    @php
                        $metrics = $row['performance']['metrics'];
                        $attendance = $row['performance']['attendance'];
                        $statusColor = match ($attendance['status']) {
                            'on_time' => 'green',
                            'late' => 'amber',
                            'absent' => 'red',
                            'exception' => 'blue',
                            default => 'slate'
                        };

                        $productivity = $metrics['productivity_percentage'];
                        $prodColor = $productivity >= 85 ? 'green' : ($productivity >= 75 ? 'amber' : 'red');

                        $utilization = $metrics['utilization_percentage'] ?? 0;
                        $utilColor = $utilization >= 90 ? 'green' : ($utilization >= 80 ? 'amber' : 'red');
                    @endphp
                    <flux:table.row :key="$row['employee']['id']" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 py-2">
                        <flux:table.cell class="py-2">
                            <div class="flex items-center gap-3">
                                <flux:avatar size="sm" :name="$row['employee']['full_name']" class="bg-slate-200" />
                                <div class="flex flex-col">
                                    <span
                                        class="font-semibold text-slate-900 leading-none">{{ $row['employee']['full_name'] }}</span>
                                    <span class="text-[10px] text-slate-400 mt-1 uppercase tracking-wider">Agente</span>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="py-2">
                            <div class="flex flex-col">
                                <span
                                    class="text-sm font-bold text-slate-700">{{ $attendance['actual_entry'] ?: '--:--' }}</span>
                                <span class="text-[10px] text-slate-400">P:
                                    {{ $attendance['scheduled_entry'] ?: '--:--' }}</span>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="py-2">
                            <flux:badge size="sm" :color="$statusColor" variant="pill">
                                {{ $attendance['status'] === 'exception' ? ($attendance['exception_reason'] ?? 'EXCEPCIÓN') : strtoupper($attendance['status']) }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="py-2">
                            <div class="flex flex-col gap-1 min-w-[120px]">
                                <div
                                    class="flex justify-between items-center text-[10px] font-bold text-slate-500 uppercase tracking-tighter">
                                    <span>TP: {{ $this->formatMinutes($metrics['total_productive_minutes']) }}</span>
                                    <span>TC: {{ $this->formatMinutes($metrics['total_connected_minutes']) }}</span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 rounded-md overflow-hidden">
                                    <div class="h-full bg-blue-500 rounded-md" style="width: {{ $productivity }}%"></div>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="py-2">
                            <flux:badge size="sm" :color="$prodColor" variant="solid">
                                {{ number_format($productivity, 1) }}%
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="py-2">
                            <div class="flex items-center gap-2">
                                <flux:icon name="bolt" size="sm"
                                    class="{{ $utilColor === 'red' ? 'text-red-400' : ($utilColor === 'amber' ? 'text-amber-400' : 'text-green-400') }}" />
                                <span
                                    class="text-sm font-black {{ $utilColor === 'red' ? 'text-red-600' : ($utilColor === 'amber' ? 'text-amber-600' : 'text-green-600') }}">
                                    {{ number_format($utilization, 1) }}%
                                </span>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="py-2">
                            <span
                                class="text-xs font-mono {{ $metrics['total_logout_minutes'] > 30 ? 'text-red-500 font-bold' : 'text-slate-500' }}">
                                {{ $this->formatMinutes($metrics['total_logout_minutes']) }}
                            </span>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="py-2">
                            <flux:button size="xs" variant="ghost" icon="chart-bar"
                                href="{{ route('contact-center.performance.scorecard', ['employeeId' => $row['employee']['id'], 'selectedDate' => $selectedDate]) }}"
                                wire:navigate />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell class="py-2" colspan="8" class="text-center py-20">
                            <div class="flex flex-col items-center gap-3">
                                <div class="p-4 bg-slate-50 rounded-md">
                                    <flux:icon name="magnifying-glass" class="w-8 h-8 text-slate-300" />
                                </div>
                                <div class="flex flex-col items-center">
                                    <span class="text-slate-900 font-bold">Sin registros de desempeño</span>
                                    <span class="text-slate-500 text-sm">No se encontraron datos para los filtros
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