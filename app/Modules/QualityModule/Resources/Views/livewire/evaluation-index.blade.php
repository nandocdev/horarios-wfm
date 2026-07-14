<div class="space-y-8">
    <div>
        <flux:heading size="xl" level="1">Evaluaciones de Calidad</flux:heading>
        <flux:subheading>Registro y consulta de evaluaciones de llamadas por cola</flux:subheading>
    </div>

    <flux:card>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <flux:input wire:model.live.debounce.250ms="search" label="Buscar" placeholder="Empleado, evaluador..." />
                <flux:select wire:model.live="queueFilter" label="Cola" placeholder="Todas">
                    @foreach($queues as $queue)
                        <option value="{{ $queue->id }}">{{ $queue->code }} — {{ $queue->name }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model.live="dateFrom" type="date" label="Desde" />
                <flux:input wire:model.live="dateTo" type="date" label="Hasta" />
            </div>

            <div class="flex justify-between items-center">
                <flux:select wire:model.live="statusFilter" label="Estado" placeholder="Todos">
                    <option value="activa">Activa</option>
                    <option value="eliminada">Eliminada</option>
                </flux:select>
                {{-- <flux:button href="{{ route('quality.evaluations.create') }}" icon="plus">Nueva Evaluación</flux:button> --}}
            </div>

            <flux:table :paginate="$evaluations">
                <flux:table.columns>
                    <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 cursor-pointer" wire:click="sortBy('dteval')">
                        Fecha @if($sortField === 'dteval')<flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="inline w-3 h-3" />@endif
                    </flux:table.column>
                    <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Cola</flux:table.column>
                    <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Empleado</flux:table.column>
                    <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Evaluador</flux:table.column>
                    <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 cursor-pointer" wire:click="sortBy('score')">
                        Score @if($sortField === 'score')<flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="inline w-3 h-3" />@endif
                    </flux:table.column>
                    <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Estado</flux:table.column>
                    <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Acciones</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($evaluations as $evaluation)
                        <flux:table.row :key="$evaluation->id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <flux:table.cell class="py-2 text-sm">{{ $evaluation->dteval?->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell class="py-2">
                                <flux:badge size="sm" color="slate" inset="top">{{ $evaluation->queue?->code }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="py-2 text-sm">{{ $evaluation->employee_id }}</flux:table.cell>
                            <flux:table.cell class="py-2 text-sm">{{ $evaluation->evaluator_id }}</flux:table.cell>
                            <flux:table.cell class="py-2">
                                <flux:badge size="sm" color="{{ $evaluation->score >= 80 ? 'green' : ($evaluation->score >= 60 ? 'amber' : 'red') }}" inset="top">
                                    {{ $evaluation->score ?? '—' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="py-2">
                                <flux:badge size="sm" color="{{ $evaluation->status === 'activa' ? 'green' : 'zinc' }}" inset="top">
                                    {{ $evaluation->status }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="py-2">
                                {{-- <flux:button href="{{ route('quality.evaluations.show', $evaluation->id) }}" variant="ghost" size="sm" icon="eye">Ver</flux:button> --}}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center py-12">
                                <flux:icon name="magnifying-glass" class="w-12 h-12 text-slate-200 mx-auto mb-3" />
                                <flux:text class="text-slate-400">No se encontraron evaluaciones</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>
