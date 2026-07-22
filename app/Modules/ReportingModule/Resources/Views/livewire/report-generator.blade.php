<div>
    {{-- Selector de Categoría --}}
    <div class="flex flex-wrap gap-1 border-b border-zinc-200 pb-px mb-4">
        @foreach($this->categories as $key => $cat)
            <button wire:click="selectCategory('{{ $key }}')" wire:key="cat-{{ $key }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-t-md transition-colors
                           {{ $category === $key
                               ? 'text-blue-700 border-b-2 border-blue-600 bg-blue-50'
                               : 'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50' }}">
                <flux:icon name="{{ $cat['icon'] }}" variant="micro" />
                {{ $cat['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Selector de Subreporte --}}
    <div class="flex flex-wrap items-center gap-2 mb-6">
        <span class="text-xs font-medium text-zinc-400 uppercase tracking-wider mr-1">Reporte:</span>
        @foreach($this->subReports as $key => $label)
            <button wire:click="selectSubReport('{{ $key }}')" wire:key="sub-{{ $key }}"
                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium transition-colors
                           {{ $subReport === $key
                               ? 'bg-blue-600 text-white'
                               : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Encabezado --}}
    <flux:heading size="xl" class="mt-2">{{ $this->reportTitle }}</flux:heading>
    <p class="mb-6 text-sm text-zinc-500">{{ $this->reportDescription }}</p>

    {{-- Filtros Compartidos --}}
    <x-reporting.filters
        :category="$category"
        :subReport="$subReport"
    />

    {{-- Botón Generar --}}
    @can('reports.export')
        <div class="mt-4">
            <flux:button wire:click="generate" variant="primary" :loading="$loading">
                <flux:icon name="play" variant="micro" />
                Generar Reporte
            </flux:button>
        </div>
    @endcan

    <flux:separator class="my-6" />

    {{-- Resultados del Reporte --}}
    @if ($preview)
        <div wire:key="report-results">
            {{-- KPIs --}}
            <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($preview['summary'] as $kpi)
                    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-white p-4">
                        @if(isset($kpi['icon']))
                            <flux:icon name="{{ $kpi['icon'] }}" variant="solid" class="text-blue-600 shrink-0" size="lg" />
                        @endif
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 truncate">{{ $kpi['label'] }}</p>
                            <p class="text-xl font-bold text-zinc-900 mt-0.5">{{ $kpi['value'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Chart (solo volumen por intervalo) --}}
            @if (isset($preview['chartConfig']) && $preview['chartConfig']['type'] === 'bar')
                <x-wfm.section title="Distribución por Intervalo">
                    <x-apex-chart
                        id="report-volume-interval"
                        :options="json_encode([
                            'chart' => ['type' => 'bar', 'toolbar' => ['show' => false], 'zoom' => ['enabled' => false], 'offsetX' => 0, 'offsetY' => 0, 'parentHeightOffset' => 0],
                            'series' => $preview['chartConfig']['data']['series'],
                            'xaxis' => ['categories' => $preview['chartConfig']['data']['categories'], 'labels' => ['rotate' => -45, 'style' => ['fontSize' => '10px']]],
                            'yaxis' => ['title' => ['text' => 'Llamadas']],
                            'colors' => ['#3b82f6', '#10b981', '#ef4444'],
                            'plotOptions' => ['bar' => ['columnWidth' => '70%', 'distributed' => false]],
                            'legend' => ['position' => 'top'],
                            'grid' => ['borderColor' => '#e5e7eb', 'strokeDashArray' => 2, 'padding' => ['left' => 10, 'right' => 10, 'top' => 10, 'bottom' => 10]],
                            'tooltip' => ['shared' => true, 'intersect' => false],
                        ])"
                        height="300"
                    />
                </x-wfm.section>
            @endif

            {{-- Tabla de datos --}}
            <x-wfm.section title="Datos">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200">
                                @foreach ($preview['columns'] as $col)
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider whitespace-nowrap">
                                        {{ $col['label'] }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @forelse ($preview['rows'] as $row)
                                <tr class="hover:bg-zinc-50">
                                    @foreach ($preview['columns'] as $col)
                                        <td class="px-3 py-2 text-xs text-zinc-700 whitespace-nowrap">
                                            @php $val = data_get($row, $col['key']); @endphp
                                            @if (is_bool($val))
                                                @if ($val)
                                                    <flux:icon name="check" variant="solid" class="text-green-600 size-4" />
                                                @else
                                                    <flux:icon name="x-mark" variant="solid" class="text-red-400 size-4" />
                                                @endif
                                            @elseif ($val === null || $val === '')
                                                <span class="text-zinc-300">—</span>
                                            @else
                                                {{ $val }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($preview['columns']) }}" class="px-3 py-8 text-center text-sm text-zinc-400">
                                        No hay datos para los filtros seleccionados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (count($preview['rows']) > 0)
                    <p class="mt-2 text-xs text-zinc-400 text-right">
                        {{ count($preview['rows']) }} registro(s)
                    </p>
                @endif
            </x-wfm.section>

            {{-- Botones de Exportación --}}
            @can('reports.export')
                <div class="mt-4 flex items-center gap-3">
                    <span class="text-sm font-medium text-zinc-500">Exportar:</span>
                    <flux:button wire:click="exportPdf" variant="primary" icon="document-arrow-down">
                        PDF
                    </flux:button>
                    <flux:button wire:click="exportXls" variant="primary" icon="document-arrow-down">
                        XLS (Excel)
                    </flux:button>
                </div>
            @endcan
        </div>
    @elseif (!$loading)
        <div class="flex flex-col items-center justify-center py-16 text-zinc-400">
            <flux:icon name="document-chart-bar" variant="solid" class="size-12 mb-4 text-zinc-300" />
            <p class="text-sm font-medium">Selecciona un reporte y aplica los filtros</p>
            <p class="text-xs mt-1">Luego haz clic en "Generar Reporte" para ver los resultados</p>
        </div>
    @else
        <div class="flex items-center justify-center py-16">
            <flux:icon name="arrow-path" variant="solid" class="size-8 animate-spin text-blue-500" />
        </div>
    @endif
</div>
