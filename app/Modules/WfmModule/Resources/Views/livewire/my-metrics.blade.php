<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Mis Métricas</flux:heading>
            <flux:subheading>{{ $currentDate->locale('es')->translatedFormat('l d F Y') }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button wire:click="previousDay" icon="chevron-left" size="sm" variant="subtle" />
            <flux:button wire:click="nextDay" icon="chevron-right" size="sm" variant="subtle" />
            <flux:button wire:click="$set('date', '{{ now()->toDateString() }}')" size="sm" variant="subtle">Hoy</flux:button>
        </div>
    </div>

    @if(!$employee)
        <flux:card>
            <div class="text-center py-12">
                <flux:icon name="user-circle" class="w-12 h-12 text-slate-200 mx-auto mb-3" />
                <flux:text class="text-slate-400">Debes tener un perfil de empleado asociado para ver métricas</flux:text>
            </div>
        </flux:card>
    @else
        {{-- Hero KPIs --}}
        @if(!empty($heroKpis))
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                @foreach($heroKpis as $kpi)
                    <flux:card class="text-center">
                        <flux:text size="xs" class="text-slate-500 uppercase font-bold">{{ $kpi['label'] }}</flux:text>
                        <flux:heading size="2xl" class="mt-1">{{ $kpi['value'] }}</flux:heading>
                        @isset($kpi['delta'])
                            <flux:text size="xs" class="{{ str_starts_with($kpi['delta'], '+') ? 'text-green-600' : (str_starts_with($kpi['delta'], '-') ? 'text-red-600' : 'text-slate-400') }}">
                                {{ $kpi['delta'] }}
                            </flux:text>
                        @endisset
                    </flux:card>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Col 1: Estado Actual --}}
            <div class="space-y-6">
                <flux:card>
                    <div class="space-y-3">
                        <flux:heading size="sm">Estado Actual</flux:heading>
                        <flux:separator />
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full {{ $states['current'] !== 'LOGOUT' && $states['current'] !== 'OFFLINE' ? 'bg-green-500' : 'bg-red-400' }}"></span>
                            <div>
                                <flux:text size="lg" class="font-bold">{{ $states['current'] }}</flux:text>
                                @if($states['reason'])
                                    <flux:text size="xs" class="text-slate-400 block">{{ $states['reason'] }}</flux:text>
                                @endif
                            </div>
                        </div>
                        <flux:separator />
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">Tiempo conectado</span><span class="font-mono font-medium">{{ gmdate('H:i:s', $states['logged_seconds']) }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">TALKING</span><span class="font-mono">{{ gmdate('H:i:s', $states['talking']) }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">READY</span><span class="font-mono">{{ gmdate('H:i:s', $states['ready']) }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">NOT READY</span><span class="font-mono">{{ gmdate('H:i:s', $states['not_ready']) }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Almuerzo</span><span class="font-mono">{{ gmdate('H:i:s', $states['lunch']) }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Descanso</span><span class="font-mono">{{ gmdate('H:i:s', $states['break']) }}</span></div>
                        </div>
                    </div>
                </flux:card>

                {{-- Horario --}}
                @if($schedule)
                    <flux:card>
                        <div class="space-y-3">
                            <flux:heading size="sm">Horario de Hoy</flux:heading>
                            <flux:separator />
                            <div class="text-sm space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Turno</span>
                                    <span class="font-medium">{{ $schedule->schedule?->name ?? '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Entrada</span>
                                    <span class="font-mono font-medium">{{ $schedule->start_time ? Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '--:--' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Salida</span>
                                    <span class="font-mono font-medium">{{ $schedule->end_time ? Carbon\Carbon::parse($schedule->end_time)->format('H:i') : '--:--' }}</span>
                                </div>
                                @if($schedule->schedule?->total_minutes)
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Duración</span>
                                        <span class="font-mono font-medium">{{ $schedule->schedule->total_minutes }} min</span>
                                    </div>
                                @endif
                            </div>
                            @if($hasExceptions)
                                <flux:badge color="amber" size="sm">Con excepción de horario</flux:badge>
                            @endif
                        </div>
                    </flux:card>
                @endif
            </div>

            {{-- Col 2: Llamadas --}}
            <div class="space-y-6">
                <flux:card>
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <flux:icon name="phone" class="w-4 h-4 text-indigo-500" />
                            <flux:heading size="sm">Llamadas</flux:heading>
                        </div>
                        <flux:separator />
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-md">
                                <flux:text size="2xl" class="font-bold text-indigo-600">{{ $callStats->total ?? 0 }}</flux:text>
                                <flux:text size="xs" class="text-slate-500 block">Total</flux:text>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-md">
                                <flux:text size="2xl" class="font-bold text-green-600">{{ $callStats->handled ?? 0 }}</flux:text>
                                <flux:text size="xs" class="text-slate-500 block">Atendidas</flux:text>
                            </div>
                        </div>
                        @if($callStats->total > 0)
                            <flux:text size="xs" class="text-slate-400 text-center block">
                                SLA: {{ round(($callStats->handled / $callStats->total) * 100, 1) }}%
                            </flux:text>
                        @endif
                    </div>
                </flux:card>

                {{-- Por Cola --}}
                @if($queuePerf->isNotEmpty())
                    <flux:card>
                        <div class="space-y-3">
                            <flux:heading size="sm">Desempeño por Cola</flux:heading>
                            <flux:separator />
                            <div class="space-y-2">
                                @foreach($queuePerf as $qp)
                                    <div class="flex items-center justify-between p-2 bg-slate-50 dark:bg-slate-900 rounded-md text-sm">
                                        <div>
                                            <flux:text size="sm" class="font-medium">{{ $qp['queue_name'] ?? '—' }}</flux:text>
                                            <flux:text size="xs" class="text-slate-400 block">{{ ($qp['total_offered'] ?? 0) - ($qp['handled'] ?? 0) }} abandonadas</flux:text>
                                        </div>
                                        <div class="text-right">
                                            <flux:text size="sm" class="font-mono font-bold">{{ $qp['total_offered'] ?? 0 }}</flux:text>
                                            <flux:text size="xs" class="text-slate-400 block">AHT {{ $qp['avg_aht'] ? gmdate('i:s', (int) $qp['avg_aht']) : '--:--' }}</flux:text>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </flux:card>
                @endif
            </div>

            {{-- Col 3: Actividades + Shrinkage --}}
            <div class="space-y-6">
                <flux:card>
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <flux:icon name="clipboard-document-list" class="w-4 h-4 text-amber-500" />
                            <flux:heading size="sm">Actividades Intradía</flux:heading>
                        </div>
                        <flux:separator />
                        @if($intradayEvents->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($intradayEvents as $event)
                                    <div class="p-2 bg-slate-50 dark:bg-slate-900 rounded-md text-sm">
                                        <flux:text size="sm" class="font-medium">{{ $event['title'] ?? 'Actividad' }}</flux:text>
                                        <flux:text size="xs" class="text-slate-400 block">{{ $event['time'] ?? '' }}</flux:text>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <flux:text class="text-slate-400 text-center py-4">Sin actividades programadas</flux:text>
                        @endif
                    </div>
                </flux:card>

                <flux:card>
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <flux:icon name="chart-bar" class="w-4 h-4 text-emerald-500" />
                            <flux:heading size="sm">Shrinkage (Reductores)</flux:heading>
                        </div>
                        <flux:separator />
                        <div class="text-center py-4">
                            <flux:heading size="3xl" class="font-bold">{{ round($shrinkage, 1) }}%</flux:heading>
                            <flux:text size="xs" class="text-slate-500">Tiempo no productivo</flux:text>
                        </div>
                    </div>
                </flux:card>

                @if(isset($heroKpis['adherence']))
                    <flux:card>
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <flux:icon name="clock" class="w-4 h-4 text-blue-500" />
                                <flux:heading size="sm">Adherencia</flux:heading>
                            </div>
                            <flux:separator />
                            <div class="text-center py-4">
                                <flux:heading size="3xl" class="font-bold text-blue-600">{{ $heroKpis['adherence']['value'] }}</flux:heading>
                                <flux:text size="xs" class="text-slate-500">vs {{ $heroKpis['adherence']['delta'] ?? '—' }} ayer</flux:text>
                            </div>
                        </div>
                    </flux:card>
                @endif
            </div>
        </div>
    @endif
</div>
