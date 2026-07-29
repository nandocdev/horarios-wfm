<div class="py-2 px-4 space-y-6 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-2 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="clock" variant="mini" class="text-blue-600" />
                Intervalos
            </flux:heading>
            <flux:subheading>96 slots de 15 minutos &middot; {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</flux:subheading>
        </div>

        <flux:input type="date" wire:model.live="date" size="sm" />
    </div>

    @php
        $grandTotal = $rows->sum('real');
        $avgOcc = $rows->avg('occupancy');
        $avgAht = $rows->avg('aht');
        $avgAdh = $rows->avg('adherence');
        $avgForecast = $rows->avg('forecast');
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Llamadas</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($grandTotal) }}</p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Forecast Prom.</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $avgForecast !== null ? number_format($avgForecast, 1) : '—' }}</p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Occupancy Prom.</p>
            <p class="text-2xl font-bold {{ $avgOcc > 85 ? 'text-green-600' : ($avgOcc > 70 ? 'text-amber-600' : 'text-red-600') }} mt-1">
                {{ $avgOcc !== null ? number_format($avgOcc, 1) . '%' : '—' }}
            </p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">AHT Prom.</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $avgAht !== null ? sprintf('%02d:%02d', floor($avgAht / 60), (int) $avgAht % 60) : '—' }}</p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Adherencia Prom.</p>
            <p class="text-2xl font-bold {{ $avgAdh > 90 ? 'text-green-600' : ($avgAdh > 80 ? 'text-amber-600' : 'text-red-600') }} mt-1">
                {{ $avgAdh !== null ? number_format($avgAdh, 1) . '%' : '—' }}
            </p>
        </flux:card>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
            <table class="w-full text-left text-xs">
                <thead class="sticky top-0 z-10 bg-slate-800 text-white">
                    <tr>
                        <th class="py-1.5 px-2 font-semibold text-center w-16">Slot</th>
                        <th class="py-1.5 px-2 font-semibold text-center w-20">Intervalo</th>
                        <th class="py-1.5 px-2 font-semibold text-center w-16">Forecast</th>
                        <th class="py-1.5 px-2 font-semibold text-center w-16">Real</th>
                        <th class="py-1.5 px-2 font-semibold text-center w-16">
                            <span class="block">Occupancy</span>
                        </th>
                        <th class="py-1.5 px-2 font-semibold text-center w-16">AHT</th>
                        <th class="py-1.5 px-2 font-semibold text-center w-16">ASA</th>
                        <th class="py-1.5 px-2 font-semibold text-center w-16">SL</th>
                        <th class="py-1.5 px-2 font-semibold text-center w-16">Adherencia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        @php
                            $occColor = $row->occupancy !== null
                                ? ($row->occupancy > 85 ? 'text-green-600' : ($row->occupancy > 70 ? 'text-amber-600' : 'text-red-600'))
                                : 'text-slate-400';
                            $slColor = $row->sl !== null
                                ? ($row->sl >= 80 ? 'text-green-600' : ($row->sl >= 60 ? 'text-amber-600' : 'text-red-600'))
                                : 'text-slate-400';
                            $adhColor = $row->adherence !== null
                                ? ($row->adherence > 90 ? 'text-green-600' : ($row->adherence > 80 ? 'text-amber-600' : 'text-red-600'))
                                : 'text-slate-400';
                            $isBusinessHours = \Carbon\Carbon::createFromFormat('H:i', substr($row->label, 0, 5))->between(
                                \Carbon\Carbon::createFromTime(7, 0),
                                \Carbon\Carbon::createFromTime(19, 0)
                            );
                            $rowBg = $isBusinessHours && ($row->slot % 2 === 0) ? 'bg-white' : 'bg-slate-50/50';
                            if (! $isBusinessHours) {
                                $rowBg = 'bg-slate-100/30';
                            }
                        @endphp
                        <tr class="{{ $rowBg }} hover:bg-blue-50/50 transition-colors duration-100">
                            <td class="py-1 px-2 text-center font-mono text-slate-400 text-[10px]">{{ $row->slot }}</td>
                            <td class="py-1 px-2 text-center font-mono font-semibold text-slate-700">{{ $row->label }}</td>
                            <td class="py-1 px-2 text-center font-mono {{ $row->forecast !== null ? 'text-blue-600' : 'text-slate-300' }}">
                                {{ $row->forecast !== null ? number_format($row->forecast) : '—' }}
                            </td>
                            <td class="py-1 px-2 text-center font-mono font-semibold {{ $row->real > 0 ? 'text-slate-900' : 'text-slate-400' }}">
                                {{ $row->real > 0 ? $row->real : '—' }}
                            </td>
                            <td class="py-1 px-2 text-center font-mono font-semibold {{ $occColor }}">
                                {{ $row->occupancy !== null ? number_format($row->occupancy, 1) . '%' : '—' }}
                            </td>
                            <td class="py-1 px-2 text-center font-mono {{ $row->aht !== null ? 'text-slate-700' : 'text-slate-300' }}">
                                {{ $row->aht !== null ? sprintf('%02d:%02d', floor($row->aht / 60), (int) $row->aht % 60) : '—' }}
                            </td>
                            <td class="py-1 px-2 text-center font-mono {{ $row->asa !== null ? 'text-slate-700' : 'text-slate-300' }}">
                                {{ $row->asa !== null ? sprintf('%02d:%02d', floor($row->asa / 60), (int) $row->asa % 60) : '—' }}
                            </td>
                            <td class="py-1 px-2 text-center font-mono font-semibold {{ $slColor }}">
                                {{ $row->sl !== null ? number_format($row->sl, 1) . '%' : '—' }}
                            </td>
                            <td class="py-1 px-2 text-center font-mono font-semibold {{ $adhColor }}">
                                {{ $row->adherence !== null ? number_format($row->adherence, 1) . '%' : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400 italic">
                                No se encontraron datos para esta fecha.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>
</div>
