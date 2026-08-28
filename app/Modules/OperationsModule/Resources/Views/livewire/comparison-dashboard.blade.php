<div class="py-2 px-4 space-y-6 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-3 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="arrow-right-circle" variant="mini" class="text-blue-600" />
                Comparativos de Operaciones
            </flux:heading>
            <flux:subheading>Compare el rendimiento operativo y de servicio entre {{ strtolower($dimensionLabel) }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 font-medium border border-blue-100">
                {{ count($selectedIds) }} {{ strtolower($dimensionLabel) }} seleccionados
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-white p-4 rounded-md shadow-sm border border-slate-200">
        <flux:select wire:model.live="dimension" size="sm" label="Dimensión de Comparación">
            <option value="team">Equipos</option>
            <option value="queue">Colas</option>
            <option value="supervisor">Supervisores</option>
        </flux:select>
        <flux:input type="date" wire:model.live="dateFrom" size="sm" label="Fecha Desde" />
        <flux:input type="date" wire:model.live="dateTo" size="sm" label="Fecha Hasta" />
    </div>

    @if($options->isNotEmpty())
        <flux:card class="p-4 bg-white">
            <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-100">
                <div>
                    <flux:heading size="sm">Seleccionar {{ $dimensionLabel }} a comparar</flux:heading>
                    <p class="text-xs text-slate-500">Haga clic en los elementos para incluirlos en la comparativa</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button"
                        wire:click="selectAll({{ json_encode($options->keys()->toArray()) }})"
                        class="text-xs text-blue-600 hover:text-blue-800 font-medium px-2 py-1 rounded hover:bg-blue-50 transition-colors">
                        Seleccionar todos
                    </button>
                    <span class="text-slate-300">|</span>
                    <button type="button"
                        wire:click="deselectAll"
                        class="text-xs text-slate-500 hover:text-slate-700 font-medium px-2 py-1 rounded hover:bg-slate-100 transition-colors">
                        Limpiar selección
                    </button>
                </div>
            </div>

            <div class="mt-3 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                @foreach($options as $id => $name)
                    @php
                        $isSelected = in_array((int) $id, array_map('intval', $selectedIds), true);
                    @endphp
                    <button type="button"
                        wire:click="toggleId({{ $id }})"
                        class="flex items-center gap-2.5 p-2.5 rounded-lg border text-left transition-all duration-150 {{ $isSelected ? 'border-blue-500 bg-blue-50/80 text-blue-900 shadow-sm ring-1 ring-blue-500/20' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700' }}">
                        <div class="w-4 h-4 rounded border flex items-center justify-center shrink-0 {{ $isSelected ? 'bg-blue-600 border-blue-600 text-white' : 'border-slate-300 bg-white' }}">
                            @if($isSelected)
                                <flux:icon name="check" variant="micro" class="w-3 h-3 text-white" />
                            @endif
                        </div>
                        <span class="text-xs font-medium truncate">{{ $name }}</span>
                    </button>
                @endforeach
            </div>
        </flux:card>
    @else
        <flux:card class="p-6 text-center text-slate-500">
            No se encontraron {{ strtolower($dimensionLabel) }} activos disponibles para comparar.
        </flux:card>
    @endif

    @if(!empty($results))
        <flux:card class="p-0 overflow-hidden bg-white shadow-sm border border-slate-200">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <flux:heading size="sm">Tabla Comparativa de Desempeño</flux:heading>
                <span class="text-xs text-slate-400">Rango: {{ $dateFrom }} al {{ $dateTo }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-800 text-white text-[10px] uppercase tracking-widest">
                        <tr>
                            <th class="py-2.5 px-3 font-semibold sticky left-0 bg-slate-800 z-10">{{ $dimensionLabel }}</th>
                            @foreach($metricLabels as $key => $label)
                                <th class="py-2.5 px-3 font-semibold text-center">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($results as $id => $row)
                            <tr class="hover:bg-blue-50/40 transition-colors duration-150">
                                <td class="py-2.5 px-3 font-semibold text-slate-800 sticky left-0 bg-white shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">{{ $row['name'] }}</td>
                                @foreach($metricLabels as $key => $label)
                                    @php
                                        $val = $row[$key] ?? null;
                                        $isPct = in_array($key, ['occupancy', 'utilization', 'productivity', 'adherence', 'conformance', 'shrinkage_pct', 'service_level', 'fcr_pct']);
                                        $isTime = in_array($key, ['aht_seconds', 'asa_seconds']);
                                        $isHigherBetter = in_array($key, ['occupancy', 'utilization', 'productivity', 'adherence', 'conformance', 'service_level', 'fcr_pct', 'total_calls']);
                                        $isLowerBetter = in_array($key, ['shrinkage_pct', 'aht_seconds', 'asa_seconds']);

                                        $allVals = array_filter(array_column($results, $key), fn ($v) => $v !== null);
                                        $maxVal = !empty($allVals) ? max($allVals) : 0;
                                        $minVal = !empty($allVals) ? min($allVals) : 0;

                                        $isBest = false;
                                        if ($val !== null && count($allVals) > 1) {
                                            if ($isHigherBetter && $maxVal > 0) {
                                                $isBest = $val == $maxVal;
                                            } elseif ($isLowerBetter && $minVal > 0) {
                                                $isBest = $val == $minVal;
                                            }
                                        }
                                    @endphp
                                    <td class="py-2.5 px-3 text-center font-mono {{ $isBest ? 'text-emerald-700 font-bold bg-emerald-50/50' : 'text-slate-600' }}">
                                        @if($val === null)
                                            <span class="text-slate-300">—</span>
                                        @elseif($isPct)
                                            {{ number_format((float) $val, 1) }}%
                                        @elseif($isTime)
                                            {{ sprintf('%02d:%02d', floor((float) $val / 60), (int) $val % 60) }}
                                        @else
                                            {{ number_format((float) $val) }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    @elseif(!empty($selectedIds))
        <flux:card class="p-8 text-center bg-white border border-dashed border-slate-300">
            <flux:icon name="chart-bar" class="w-8 h-8 mx-auto text-slate-300 mb-2" />
            <p class="text-sm font-medium text-slate-600">Sin datos de KPIs en el rango seleccionado</p>
            <p class="text-xs text-slate-400 mt-1">No se encontraron registros consolidados entre {{ $dateFrom }} y {{ $dateTo }} para los elementos seleccionados.</p>
        </flux:card>
    @else
        <flux:card class="p-8 text-center bg-white border border-dashed border-slate-300">
            <flux:icon name="cursor-arrow-rays" class="w-8 h-8 mx-auto text-blue-400 mb-2" />
            <p class="text-sm font-medium text-slate-700">Seleccione elementos para comparar</p>
            <p class="text-xs text-slate-400 mt-1">Haga clic en uno o más {{ strtolower($dimensionLabel) }} arriba para generar la tabla comparativa.</p>
        </flux:card>
    @endif
</div>
