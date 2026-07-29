<div class="py-2 px-4 space-y-6 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-2 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="chart-bar" variant="mini" class="text-blue-600" />
                Data Explorer
            </flux:heading>
            <flux:subheading>Consultas ad-hoc sobre tablas de hechos</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <flux:select wire:model.live="table" size="sm" label="Tabla">
            @foreach($tables as $key => $label)
                <option value="{{ $key }}">{{ $label }} ({{ $key }})</option>
            @endforeach
        </flux:select>

        <flux:input type="number" wire:model.live="limit" min="10" max="500" size="sm" label="Límite" />

        <div class="flex items-end gap-2">
            <flux:button wire:click="run" icon="play" class="flex-1">Ejecutar</flux:button>
            <flux:button wire:click="exportCsv" icon="arrow-down-tray" variant="ghost">CSV</flux:button>
        </div>
    </div>

    <flux:card class="p-4">
        <div class="flex items-center justify-between mb-3">
            <flux:heading size="sm">Columnas</flux:heading>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach($tableMeta['columns'] as $key => $label)
                <label class="flex items-center gap-1.5 text-xs cursor-pointer px-2 py-1 rounded border {{ in_array($key, $selectedColumns) ? 'border-blue-400 bg-blue-50' : 'border-slate-200' }}"
                       wire:click="$set('selectedColumns', {{ json_encode(
                           in_array($key, $selectedColumns)
                               ? array_values(array_filter($selectedColumns, fn($c) => $c !== $key))
                               : array_merge($selectedColumns, [$key])
                       ) }})">
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </flux:card>

    <flux:card class="p-4">
        <div class="flex items-center justify-between mb-3">
            <flux:heading size="sm">Filtros</flux:heading>
            <flux:button wire:click="addFilter" icon="plus" size="sm" variant="ghost">Agregar</flux:button>
        </div>

        @if(count($filters) > 0)
            <div class="space-y-2">
                @foreach($filters as $i => $filter)
                    <div class="flex items-center gap-2" wire:key="filter-{{ $i }}">
                        <flux:select wire:model="filters.{{ $i }}.column" size="sm" class="md:w-44">
                            <option value="">— Seleccione —</option>
                            @foreach($tableMeta['columns'] as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="filters.{{ $i }}.operator" size="sm" class="md:w-24">
                            <option value="=">=</option>
                            <option value="!=">≠</option>
                            <option value=">">&gt;</option>
                            <option value=">=">&gt;=</option>
                            <option value="<">&lt;</option>
                            <option value="<=">&lt;=</option>
                            <option value="LIKE">LIKE</option>
                        </flux:select>
                        <flux:input wire:model="filters.{{ $i }}.value" size="sm" class="flex-1" placeholder="Valor" />
                        <flux:button wire:click="removeFilter({{ $i }})" icon="x-mark" size="sm" variant="ghost"></flux:button>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-xs text-slate-400 italic">Sin filtros. Todos los registros serán consultados.</p>
        @endif
    </flux:card>

    <flux:card class="p-0 overflow-hidden">
        <div class="p-3 border-b border-slate-100 flex items-center justify-between">
            <flux:heading size="sm">Resultados ({{ $results->total() }} registros)</flux:heading>
        </div>

        @if($results->isNotEmpty())
            <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 z-10 bg-slate-800 text-white">
                        <tr>
                            @foreach($columns as $col => $label)
                                <th class="py-1.5 px-2 font-semibold whitespace-nowrap">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($results as $row)
                            <tr class="hover:bg-blue-50/50">
                                @foreach($columns as $col => $label)
                                    <td class="py-1 px-2 font-mono {{ is_numeric($row->$col) ? 'text-right' : '' }}">{{ $row->$col ?? '—' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($results->hasPages())
                <div class="p-3 border-t border-slate-100">{{ $results->links() }}</div>
            @endif
        @else
            <div class="p-8 text-center text-slate-400 italic">
                Sin resultados. Ajuste los filtros o seleccione otra tabla.
            </div>
        @endif
    </flux:card>
</div>
