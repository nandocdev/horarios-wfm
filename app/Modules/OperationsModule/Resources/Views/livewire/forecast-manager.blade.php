<div class="py-2 px-4 space-y-6 bg-slate-50 min-h-screen">
    @if($view === 'list')
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-2 rounded-md shadow-sm border border-slate-200">
            <div>
                <flux:heading size="xl" level="1" class="flex items-center gap-2">
                    <flux:icon name="chart-bar" variant="mini" class="text-blue-600" />
                    Forecast
                </flux:heading>
                <flux:subheading>Gestión de pronósticos de llamadas</flux:subheading>
            </div>
            <flux:button wire:click="showGenerate" icon="plus">Nuevo Forecast</flux:button>
        </div>

        <div class="space-y-4">
            @forelse($groups as $group)
                <flux:card class="p-0 overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <flux:heading size="lg">{{ $group->name }}</flux:heading>
                            <flux:text size="sm" class="text-slate-500">{{ $group->group_type }} · {{ $group->versions->count() }} versión(es)</flux:text>
                        </div>
                    </div>

                    @if($group->versions->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-50 text-[10px] font-semibold text-slate-500 uppercase tracking-widest">
                                    <tr>
                                        <th class="py-2 px-4">Versión</th>
                                        <th class="py-2 px-4">#</th>
                                        <th class="py-2 px-4">Estado</th>
                                        <th class="py-2 px-4">Generado por</th>
                                        <th class="py-2 px-4">Escenarios</th>
                                        <th class="py-2 px-4 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($group->versions as $version)
                                        <tr class="hover:bg-blue-50/50 transition-colors duration-150">
                                            <td class="py-2 px-4 font-semibold text-slate-700">{{ $version->name }}</td>
                                            <td class="py-2 px-4 font-mono text-xs text-slate-500">v{{ $version->version_number }}</td>
                                            <td class="py-2 px-4">
                                                @php
                                                    $statusColor = match($version->status) {
                                                        'published' => 'green',
                                                        'draft' => 'amber',
                                                        default => 'slate',
                                                    };
                                                @endphp
                                                <flux:badge :color="$statusColor" size="sm" class="rounded-md">{{ $version->status }}</flux:badge>
                                            </td>
                                            <td class="py-2 px-4 text-slate-500 text-xs">{{ $version->generator?->name ?? '—' }}</td>
                                            <td class="py-2 px-4 text-xs text-slate-500">{{ $version->scenarios->pluck('name')->join(', ') ?: '—' }}</td>
                                            <td class="py-2 px-4 text-right">
                                                <flux:button.group>
                                                    <flux:button wire:click="selectVersion('{{ $version->id }}')" size="sm" icon="eye">Ver</flux:button>
                                                    @if($version->status === 'draft')
                                                        <flux:button wire:click="publishVersion('{{ $version->id }}')" size="sm" icon="check" color="green">Publicar</flux:button>
                                                    @else
                                                        <flux:button wire:click="draftVersion('{{ $version->id }}')" size="sm" icon="archive" color="amber">Borrador</flux:button>
                                                    @endif
                                                </flux:button.group>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-6 text-center text-slate-400 italic">
                            Sin versiones. Genere un nuevo forecast.
                        </div>
                    @endif
                </flux:card>
            @empty
                <flux:card class="p-8 text-center">
                    <flux:icon name="chart-bar" class="size-12 text-slate-300 mx-auto mb-3" />
                    <flux:heading>Sin forecasts</flux:heading>
                    <flux:text class="text-slate-500">No hay grupos de forecast. Genere el primero.</flux:text>
                    <flux:button wire:click="showGenerate" icon="plus" class="mt-4">Nuevo Forecast</flux:button>
                </flux:card>
            @endforelse

            @if($groups->hasPages())
                <div class="pt-4">{{ $groups->links() }}</div>
            @endif
        </div>

    @elseif($view === 'generate')
        <div class="max-w-2xl mx-auto space-y-6">
            <flux:card>
                <div class="flex items-center gap-3 mb-4">
                    <flux:button wire:click="back" icon="arrow-left" size="sm" variant="ghost"></flux:button>
                    <div>
                        <flux:heading size="lg">Generar Forecast</flux:heading>
                        <flux:subheading>Basado en promedio histórico de llamadas</flux:subheading>
                    </div>
                </div>

                <flux:separator class="mb-4" />

                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Cola de Referencia</flux:label>
                        <flux:select wire:model.live="referenceId">
                            <option value="">Seleccione una cola</option>
                            @foreach($queues as $queue)
                                <option value="{{ $queue->id }}">{{ $queue->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="referenceId" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Nombre del Grupo</flux:label>
                        <flux:input wire:model="groupName" placeholder="Forecast Cola Principal" />
                        <flux:error name="groupName" />
                    </flux:field>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Fecha Inicio</flux:label>
                            <flux:input type="date" wire:model="startDate" />
                            <flux:error name="startDate" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Fecha Fin</flux:label>
                            <flux:input type="date" wire:model="endDate" />
                            <flux:error name="endDate" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Semanas Históricas</flux:label>
                        <flux:input type="number" wire:model="historicalWeeks" min="1" max="52" />
                        <flux:error name="historicalWeeks" />
                    </flux:field>

                    <flux:button wire:click="generate" icon="play" class="w-full" size="lg">
                        Generar Forecast
                    </flux:button>
                </div>
            </flux:card>
        </div>

    @elseif($view === 'detail')
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <flux:button wire:click="back" icon="arrow-left" size="sm" variant="ghost"></flux:button>
                <div>
                    <flux:heading size="xl" level="1" class="flex items-center gap-2">
                        <flux:icon name="chart-bar" variant="mini" class="text-blue-600" />
                        {{ $version->name }}
                    </flux:heading>
                    <flux:subheading>
                        Grupo: {{ $version->group->name }} · v{{ $version->version_number }} ·
                        <flux:badge size="sm" :color="($version->status === 'published' ? 'green' : 'amber')" class="rounded-md inline">{{ $version->status }}</flux:badge>
                    </flux:subheading>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <flux:card class="p-3">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Escenarios</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">{{ $version->scenarios->count() }}</p>
                </flux:card>
                <flux:card class="p-3">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Intervalos</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">{{ $intervals->count() }}</p>
                </flux:card>
                <flux:card class="p-3">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Vol. Total Pron.</p>
                    <p class="text-xl font-bold text-blue-600 mt-1">{{ number_format($intervals->sum('call_volume_forecast')) }}</p>
                </flux:card>
                <flux:card class="p-3">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">AHT Prom. Pron.</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">{{ $intervals->avg('aht_seconds_forecast') ? sprintf('%02d:%02d', floor($intervals->avg('aht_seconds_forecast') / 60), (int) $intervals->avg('aht_seconds_forecast') % 60) : '—' }}</p>
                </flux:card>
            </div>

            @if($version->scenarios->count() > 1)
                <div class="flex gap-2">
                    @foreach($version->scenarios as $s)
                        <flux:badge
                            wire:click="selectScenario('{{ $s->id }}')"
                            :color="$scenario?->id === $s->id ? 'blue' : 'slate'"
                            size="sm"
                            class="rounded-md cursor-pointer hover:opacity-80"
                        >
                            {{ $s->name }} ({{ $s->scenario_type }})
                        </flux:badge>
                    @endforeach
                </div>
            @endif

            <flux:card class="p-0 overflow-hidden">
                <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 z-10 bg-slate-800 text-white">
                            <tr>
                                <th class="py-1.5 px-2 font-semibold text-center">Fecha</th>
                                <th class="py-1.5 px-2 font-semibold text-center">Inicio</th>
                                <th class="py-1.5 px-2 font-semibold text-center">Volumen</th>
                                <th class="py-1.5 px-2 font-semibold text-center">AHT</th>
                                <th class="py-1.5 px-2 font-semibold text-center">Staff Req.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($intervals as $interval)
                                <tr class="hover:bg-blue-50/50">
                                    <td class="py-1 px-2 text-center font-mono text-slate-600">{{ $interval->interval_start->format('d/m') }}</td>
                                    <td class="py-1 px-2 text-center font-mono text-slate-700">{{ $interval->interval_start->format('H:i') }}</td>
                                    <td class="py-1 px-2 text-center font-mono font-semibold">{{ number_format($interval->call_volume_forecast) }}</td>
                                    <td class="py-1 px-2 text-center font-mono">{{ sprintf('%02d:%02d', floor($interval->aht_seconds_forecast / 60), (int) $interval->aht_seconds_forecast % 60) }}</td>
                                    <td class="py-1 px-2 text-center font-mono">{{ number_format($interval->staff_required, 1) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-slate-400 italic">
                                        Sin intervalos en este escenario.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>
    @endif
</div>
