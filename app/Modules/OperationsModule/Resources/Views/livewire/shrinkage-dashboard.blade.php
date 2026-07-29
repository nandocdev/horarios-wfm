<div class="py-2 px-4 space-y-6 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-2 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="clock" variant="mini" class="text-blue-600" />
                Shrinkage
            </flux:heading>
            <flux:subheading>Análisis de tiempo no productivo</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <flux:input type="date" wire:model.live="dateFrom" size="sm" label="Desde" />
        <flux:input type="date" wire:model.live="dateTo" size="sm" label="Hasta" />
        <flux:select wire:model.live="employeeFilter" size="sm" label="Agente">
            <option value="">Todos</option>
            @foreach($employees as $emp)
                <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
            @endforeach
        </flux:select>
        <div class="flex items-end">
            <div class="p-3 bg-white rounded-md border border-slate-200 w-full">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Shrinkage %</p>
                <p class="text-xl font-bold {{ $loggedMinutes > 0 ? ($grandTotalMinutes / $loggedMinutes * 100 > 30 ? 'text-red-600' : ($grandTotalMinutes / $loggedMinutes * 100 > 20 ? 'text-amber-600' : 'text-green-600')) : 'text-slate-400' }} mt-1">
                    {{ $loggedMinutes > 0 ? number_format(($grandTotalMinutes / $loggedMinutes) * 100, 1) . '%' : '—' }}
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach($totalsByCategory as $cat)
            <flux:card class="p-3" style="border-left: 4px solid {{ $cat->color ?? '#6b7280' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $cat->name }}</p>
                        <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($cat->total_minutes) }} min</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-500">
                            {{ $grandTotalMinutes > 0 ? number_format(($cat->total_minutes / $grandTotalMinutes) * 100, 1) : 0 }}%
                        </p>
                        <p class="text-[10px] text-slate-400">{{ $cat->unique_employees }} agentes</p>
                    </div>
                </div>
                <div class="mt-2 w-full bg-slate-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full" style="width: {{ $grandTotalMinutes > 0 ? min(100, ($cat->total_minutes / $grandTotalMinutes) * 100) : 0 }}%; background-color: {{ $cat->color ?? '#6b7280' }}"></div>
                </div>
            </flux:card>
        @endforeach
    </div>

    @if($totalsByCategory->isEmpty())
        <flux:card class="p-8 text-center">
            <flux:icon name="clock" class="size-12 text-slate-300 mx-auto mb-3" />
            <flux:heading>Sin datos de shrinkage</flux:heading>
            <flux:text class="text-slate-500">No hay registros de tiempo no productivo en el rango seleccionado.</flux:text>
        </flux:card>
    @endif

    <flux:card class="p-0 overflow-hidden">
        <div class="p-3 border-b border-slate-100">
            <flux:heading size="sm">Registros de Shrinkage</flux:heading>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[10px] font-semibold text-slate-500 uppercase tracking-widest border-b">
                    <tr>
                        <th class="py-2 px-3">Fecha</th>
                        <th class="py-2 px-3">Agente</th>
                        <th class="py-2 px-3">Categoría</th>
                        <th class="py-2 px-3 text-center">Duración (min)</th>
                        <th class="py-2 px-3">Fuente</th>
                        <th class="py-2 px-3">Notas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($records as $r)
                        <tr class="hover:bg-blue-50/50 transition-colors duration-150">
                            <td class="py-2 px-3 font-mono text-xs">{{ $r->date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="py-2 px-3 font-medium">{{ $r->employee?->full_name ?? '—' }}</td>
                            <td class="py-2 px-3">
                                <flux:badge
                                    size="sm"
                                    class="rounded-md"
                                    style="background-color: {{ $r->category?->color ?? '#6b7280' }}20; color: {{ $r->category?->color ?? '#6b7280' }}; border-color: {{ $r->category?->color ?? '#6b7280' }}40"
                                >
                                    {{ $r->category?->name ?? '—' }}
                                </flux:badge>
                            </td>
                            <td class="py-2 px-3 text-center font-mono font-semibold">{{ number_format($r->duration_minutes) }}</td>
                            <td class="py-2 px-3 text-xs text-slate-500">{{ $r->source_type ?? '—' }}</td>
                            <td class="py-2 px-3 text-xs text-slate-400 max-w-[200px] truncate">{{ $r->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-slate-400 italic">Sin registros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-3 border-t border-slate-100">{{ $records->links() }}</div>
        @endif
    </flux:card>
</div>
