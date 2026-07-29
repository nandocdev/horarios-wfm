<div class="py-2 px-4 space-y-6 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-2 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="arrow-right-circle" variant="mini" class="text-blue-600" />
                Comparativos
            </flux:heading>
            <flux:subheading>Compare rendimiento entre {{ $dimensionLabel }}</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        <flux:select wire:model.live="dimension" size="sm" label="Dimensión">
            <option value="team">Equipos</option>
            <option value="queue">Colas</option>
            <option value="supervisor">Supervisores</option>
        </flux:select>
        <flux:input type="date" wire:model.live="dateFrom" size="sm" label="Desde" />
        <flux:input type="date" wire:model.live="dateTo" size="sm" label="Hasta" />
    </div>

    @if($options->isNotEmpty())
        <flux:card class="p-4">
            <flux:heading size="sm">Seleccionar {{ $dimensionLabel }} a comparar</flux:heading>
            <div class="mt-3 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                @foreach($options as $id => $name)
                    <label class="flex items-center gap-2 p-2 rounded border cursor-pointer hover:bg-blue-50 transition-colors duration-150 {{ in_array($id, $selectedIds) ? 'border-blue-400 bg-blue-50' : 'border-slate-200' }}">
                        <flux:checkbox wire:click="toggleId({{ $id }})" :checked="in_array($id, $selectedIds)" />
                        <span class="text-sm font-medium text-slate-700">{{ $name }}</span>
                    </label>
                @endforeach
            </div>
        </flux:card>
    @endif

    @if(!empty($results))
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-800 text-white text-[10px] uppercase tracking-widest">
                    <tr>
                        <th class="py-2 px-3 font-semibold sticky left-0 bg-slate-800">{{ $dimensionLabel }}</th>
                        @foreach($metricLabels as $key => $label)
                            <th class="py-2 px-3 font-semibold text-center">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($results as $id => $row)
                        <tr class="hover:bg-blue-50/50 transition-colors duration-150">
                            <td class="py-2 px-3 font-semibold text-slate-700 sticky left-0 bg-white">{{ $row['name'] }}</td>
                            @foreach($metricLabels as $key => $label)
                                @php
                                    $val = $row[$key] ?? null;
                                    $isPct = in_array($key, ['occupancy', 'utilization', 'productivity', 'adherence', 'conformance', 'shrinkage_pct', 'service_level', 'fcr_pct']);
                                    $isTime = in_array($key, ['aht_seconds', 'asa_seconds']);
                                    $isHigherBetter = in_array($key, ['occupancy', 'utilization', 'productivity', 'adherence', 'conformance', 'service_level', 'fcr_pct', 'total_calls']);
                                    $isLowerBetter = in_array($key, ['shrinkage_pct', 'aht_seconds', 'asa_seconds']);

                                    $allVals = array_column($results, $key);
                                    $maxVal = !empty($allVals) ? max($allVals) : 0;
                                    $minVal = !empty($allVals) ? min($allVals) : 0;

                                    $isBest = false;
                                    if ($val !== null && $maxVal > 0) {
                                        if ($isHigherBetter) {
                                            $isBest = $val === $maxVal;
                                        } elseif ($isLowerBetter) {
                                            $isBest = $val === $minVal;
                                        }
                                    }
                                @endphp
                                <td class="py-2 px-3 text-center font-mono {{ $isBest ? 'text-green-600 font-bold' : 'text-slate-600' }}">
                                    @if($val === null)
                                        <span class="text-slate-300">—</span>
                                    @elseif($isPct)
                                        {{ number_format($val, 1) }}%
                                    @elseif($isTime)
                                        {{ sprintf('%02d:%02d', floor($val / 60), (int) $val % 60) }}
                                    @else
                                        {{ number_format($val) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif(!empty($selectedIds))
        <flux:card class="p-6 text-center text-slate-400 italic">
            Sin datos de KPIs en el rango seleccionado para los items elegidos.
        </flux:card>
    @endif
</div>
