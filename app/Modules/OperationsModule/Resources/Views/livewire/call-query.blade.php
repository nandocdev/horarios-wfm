<div class="py-2 px-4 space-y-6 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-2 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="phone" variant="mini" class="text-blue-600" />
                Consulta de Llamadas
            </flux:heading>
            <flux:subheading>Búsqueda y análisis de registros de llamadas</flux:subheading>
        </div>
    </div>

    @php
        $t = $totals;
        $abandonPct = $t->total > 0 ? round(($t->abandoned / $t->total) * 100, 1) : 0;
        $handlePct = $t->total > 0 ? round(($t->handled / $t->total) * 100, 1) : 0;
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($t->total) }}</p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Atendidas</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($t->handled) }}
                <span class="text-xs font-normal text-green-500">({{ $handlePct }}%)</span>
            </p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Abandonadas</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($t->abandoned) }}
                <span class="text-xs font-normal text-red-500">({{ $abandonPct }}%)</span>
            </p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Talk Prom.</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $t->avg_talk ? sprintf('%02d:%02d', floor($t->avg_talk / 60), (int) $t->avg_talk % 60) : '—' }}</p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">AHT Prom.</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $t->avg_aht ? sprintf('%02d:%02d', floor($t->avg_aht / 60), (int) $t->avg_aht % 60) : '—' }}</p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">ASA Prom.</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $t->avg_asa ? sprintf('%02d:%02d', floor($t->avg_asa / 60), (int) $t->avg_asa % 60) : '—' }}</p>
        </flux:card>
    </div>

    <flux:card class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-8 gap-3">
            <flux:input type="date" wire:model.live="dateFrom" size="sm" label="Desde" />
            <flux:input type="date" wire:model.live="dateTo" size="sm" label="Hasta" />
            <flux:select wire:model.live="queueFilter" size="sm" label="Cola">
                <option value="">Todas</option>
                @foreach($queues as $queue)
                    <option value="{{ $queue->name }}">{{ $queue->name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="employeeFilter" size="sm" label="Agente">
                <option value="">Todos</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="dispositionFilter" size="sm" label="Disposición">
                <option value="">Todas</option>
                <option value="2">Atendida</option>
                <option value="1">Abandonada</option>
                <option value="4">Abortada</option>
                <option value="99">Depurada</option>
            </flux:select>
            <flux:select wire:model.live="statusFilter" size="sm" label="Estado">
                <option value="">Todos</option>
                <option value="pending_operator">Pendiente</option>
                <option value="open">Abierta</option>
                <option value="closed">Cerrada</option>
                <option value="abandoned">Abandonada</option>
            </flux:select>
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Teléfono o cédula..." size="sm" label="Buscar" class="md:col-span-2" />
        </div>
    </flux:card>

    <flux:card class="p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50 text-[10px] font-semibold text-slate-500 uppercase tracking-widest border-b">
                    <tr>
                        <th class="py-2 px-3">Inicio</th>
                        <th class="py-2 px-3">Cola</th>
                        <th class="py-2 px-3">Agente</th>
                        <th class="py-2 px-3">Teléfono</th>
                        <th class="py-2 px-3 text-center">Disposición</th>
                        <th class="py-2 px-3 text-center">Estado</th>
                        <th class="py-2 px-3 text-center">Talk</th>
                        <th class="py-2 px-3 text-center">AHT</th>
                        <th class="py-2 px-3 text-center">ASA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($records as $r)
                        <tr class="hover:bg-blue-50/50 transition-colors duration-150">
                            <td class="py-2 px-3 font-mono text-xs text-slate-600">
                                {{ $r->ivr_started_at?->format('d/m H:i:s') ?? '—' }}
                            </td>
                            <td class="py-2 px-3 font-semibold text-slate-700">{{ $r->queue?->name ?? '—' }}</td>
                            <td class="py-2 px-3">{{ $r->employee?->full_name ?? $r->raw_agent_name ?? '—' }}</td>
                            <td class="py-2 px-3 font-mono text-xs">{{ $r->phone_number ?? '—' }}</td>
                            <td class="py-2 px-3 text-center">
                                @php
                                    $disp = match($r->contact_disposition) {
                                        2 => ['label' => 'Atendida', 'color' => 'green'],
                                        1 => ['label' => 'Abandonada', 'color' => 'red'],
                                        4 => ['label' => 'Abortada', 'color' => 'amber'],
                                        99 => ['label' => 'Depurada', 'color' => 'slate'],
                                        default => ['label' => '—', 'color' => 'slate'],
                                    };
                                @endphp
                                <flux:badge :color="$disp['color']" size="sm" class="rounded-md">{{ $disp['label'] }}</flux:badge>
                            </td>
                            <td class="py-2 px-3 text-center">
                                <span class="text-xs font-medium text-slate-500">{{ $r->status ?? '—' }}</span>
                            </td>
                            <td class="py-2 px-3 text-center font-mono text-xs">
                                {{ $r->talk_time ? sprintf('%02d:%02d', floor($r->talk_time / 60), $r->talk_time % 60) : '—' }}
                            </td>
                            <td class="py-2 px-3 text-center font-mono text-xs {{ $r->talk_time + $r->work_time > 300 ? 'text-amber-600 font-semibold' : '' }}">
                                {{ $r->talk_time || $r->work_time ? sprintf('%02d:%02d', floor(($r->talk_time + $r->work_time) / 60), ($r->talk_time + $r->work_time) % 60) : '—' }}
                            </td>
                            <td class="py-2 px-3 text-center font-mono text-xs">
                                {{ $r->queue_time ? sprintf('%02d:%02d', floor($r->queue_time / 60), $r->queue_time % 60) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400 italic">
                                No se encontraron llamadas con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-3 border-t border-slate-100">
                {{ $records->links() }}
            </div>
        @endif
    </flux:card>
</div>
