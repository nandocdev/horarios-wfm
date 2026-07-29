<div class="py-2 px-4 space-y-6 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-2 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="chart-bar" variant="mini" class="text-blue-600" />
                KPIs
            </flux:heading>
            <flux:subheading>Indicadores clave de rendimiento</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:select wire:model.live="granularity" size="sm" class="md:w-36">
                <option value="global">Global</option>
                <option value="team">Por Equipo</option>
                <option value="employee">Por Agente</option>
            </flux:select>

            @if($granularity === 'team')
                <flux:select wire:model.live="teamId" size="sm" class="md:w-48">
                    <option value="">Todos los equipos</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </flux:select>
            @endif

            @if($granularity === 'employee')
                <flux:select wire:model.live="employeeId" size="sm" class="md:w-48">
                    <option value="">Todos los agentes</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
                    @endforeach
                </flux:select>
            @endif

            <flux:input type="date" wire:model.live="date" size="sm" />
        </div>
    </div>

    @if($kpi)
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Occupancy</p>
                <p class="text-2xl font-bold mt-1 {{ $kpi->occupancy >= 85 ? 'text-green-600' : ($kpi->occupancy >= 70 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ number_format($kpi->occupancy, 1) }}%
                </p>
            </flux:card>
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Utilization</p>
                <p class="text-2xl font-bold mt-1 {{ $kpi->utilization >= 85 ? 'text-green-600' : ($kpi->utilization >= 70 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ number_format($kpi->utilization, 1) }}%
                </p>
            </flux:card>
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Productivity</p>
                <p class="text-2xl font-bold mt-1 {{ $kpi->productivity >= 85 ? 'text-green-600' : ($kpi->productivity >= 70 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ number_format($kpi->productivity, 1) }}%
                </p>
            </flux:card>
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Adherence</p>
                <p class="text-2xl font-bold mt-1 {{ $kpi->adherence >= 90 ? 'text-green-600' : ($kpi->adherence >= 80 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ number_format($kpi->adherence, 1) }}%
                </p>
            </flux:card>
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Conformance</p>
                <p class="text-2xl font-bold mt-1 {{ $kpi->conformance >= 90 ? 'text-green-600' : ($kpi->conformance >= 80 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ number_format($kpi->conformance, 1) }}%
                </p>
            </flux:card>
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Shrinkage</p>
                <p class="text-2xl font-bold mt-1 {{ $kpi->shrinkage_pct <= 20 ? 'text-green-600' : ($kpi->shrinkage_pct <= 30 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ $kpi->shrinkage_pct !== null ? number_format($kpi->shrinkage_pct, 1) . '%' : '—' }}
                </p>
            </flux:card>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">AHT</p>
                <p class="text-xl font-bold text-slate-800 mt-1">{{ $kpi->aht_seconds ? sprintf('%02d:%02d', floor($kpi->aht_seconds / 60), (int) $kpi->aht_seconds % 60) : '—' }}</p>
            </flux:card>
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">ASA</p>
                <p class="text-xl font-bold text-slate-800 mt-1">{{ $kpi->asa_seconds ? sprintf('%02d:%02d', floor($kpi->asa_seconds / 60), (int) $kpi->asa_seconds % 60) : '—' }}</p>
            </flux:card>
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">SL</p>
                <p class="text-xl font-bold mt-1 {{ $kpi->service_level >= 80 ? 'text-green-600' : ($kpi->service_level >= 60 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ $kpi->service_level !== null ? number_format($kpi->service_level, 1) . '%' : '—' }}
                </p>
            </flux:card>
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">FCR</p>
                <p class="text-xl font-bold mt-1 {{ $kpi->fcr_pct >= 80 ? 'text-green-600' : ($kpi->fcr_pct >= 60 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ $kpi->fcr_pct !== null ? number_format($kpi->fcr_pct, 1) . '%' : '—' }}
                </p>
            </flux:card>
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">ACW</p>
                <p class="text-xl font-bold text-slate-800 mt-1">{{ $kpi->acw_seconds ? sprintf('%02d:%02d', floor($kpi->acw_seconds / 60), (int) $kpi->acw_seconds % 60) : '—' }}</p>
            </flux:card>
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Forecast Acc.</p>
                <p class="text-xl font-bold mt-1 {{ $kpi->forecast_accuracy_pct >= 90 ? 'text-green-600' : ($kpi->forecast_accuracy_pct >= 80 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ $kpi->forecast_accuracy_pct !== null ? number_format($kpi->forecast_accuracy_pct, 1) . '%' : '—' }}
                </p>
            </flux:card>
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Llamadas</p>
                <p class="text-xl font-bold text-slate-800 mt-1">{{ number_format($kpi->total_calls) }}</p>
            </flux:card>
        </div>
    @else
        <flux:card class="p-8 text-center">
            <flux:icon name="chart-bar" class="size-12 text-slate-300 mx-auto mb-3" />
            <flux:heading>Sin datos de KPIs</flux:heading>
            <flux:text class="text-slate-500">
                No hay KPIs calculados para {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}.
            </flux:text>
        </flux:card>
    @endif

    @if($rows->isNotEmpty() && $granularity !== 'global')
        <flux:card class="p-0 overflow-hidden">
            <div class="p-3 border-b border-slate-100">
                <flux:heading size="sm">
                    @if($granularity === 'team') Equipos @else Agentes @endif
                </flux:heading>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-800 text-white text-[10px] uppercase tracking-widest">
                        <tr>
                            <th class="py-1.5 px-2 font-semibold">
                                {{ $granularity === 'team' ? 'Equipo' : 'Agente' }}
                            </th>
                            <th class="py-1.5 px-2 font-semibold text-center">Occ</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Util</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Prod</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Adh</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Conf</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Shrink</th>
                            <th class="py-1.5 px-2 font-semibold text-center">AHT</th>
                            <th class="py-1.5 px-2 font-semibold text-center">SL</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Calls</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rows as $row)
                            @php
                                $name = $granularity === 'team'
                                    ? ($teams->firstWhere('id', $row->dim_team_id)?->name ?? '—')
                                    : ($employees->firstWhere('id', $row->dim_employee_id)?->full_name ?? '—');
                            @endphp
                            <tr class="hover:bg-blue-50/50">
                                <td class="py-1 px-2 font-semibold text-slate-700">{{ $name }}</td>
                                <td class="py-1 px-2 text-center font-mono {{ $row->occupancy >= 85 ? 'text-green-600' : ($row->occupancy >= 70 ? 'text-amber-600' : 'text-red-600') }}">{{ $row->occupancy ? number_format($row->occupancy, 1) . '%' : '—' }}</td>
                                <td class="py-1 px-2 text-center font-mono text-slate-600">{{ $row->utilization ? number_format($row->utilization, 1) . '%' : '—' }}</td>
                                <td class="py-1 px-2 text-center font-mono text-slate-600">{{ $row->productivity ? number_format($row->productivity, 1) . '%' : '—' }}</td>
                                <td class="py-1 px-2 text-center font-mono {{ $row->adherence >= 90 ? 'text-green-600' : ($row->adherence >= 80 ? 'text-amber-600' : 'text-red-600') }}">{{ $row->adherence ? number_format($row->adherence, 1) . '%' : '—' }}</td>
                                <td class="py-1 px-2 text-center font-mono text-slate-600">{{ $row->conformance ? number_format($row->conformance, 1) . '%' : '—' }}</td>
                                <td class="py-1 px-2 text-center font-mono text-slate-600">{{ $row->shrinkage_pct !== null ? number_format($row->shrinkage_pct, 1) . '%' : '—' }}</td>
                                <td class="py-1 px-2 text-center font-mono text-slate-600">{{ $row->aht_seconds ? sprintf('%02d:%02d', floor($row->aht_seconds / 60), (int) $row->aht_seconds % 60) : '—' }}</td>
                                <td class="py-1 px-2 text-center font-mono {{ $row->service_level >= 80 ? 'text-green-600' : ($row->service_level >= 60 ? 'text-amber-600' : 'text-red-600') }}">{{ $row->service_level !== null ? number_format($row->service_level, 1) . '%' : '—' }}</td>
                                <td class="py-1 px-2 text-center font-mono text-slate-800">{{ number_format($row->total_calls) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="p-4 text-center text-slate-400 italic">Sin registros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endif
</div>
