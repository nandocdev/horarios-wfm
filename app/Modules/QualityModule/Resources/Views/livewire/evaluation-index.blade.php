<div class="space-y-6">
    <x-wfm.page-header title="Evaluaciones de Calidad" description="Registro y consulta de evaluaciones de llamadas por cola." tour="quality-evaluations" data-tour="quality-header">
        <x-slot:actions>
            <div data-tour="quality-new-btn">
                @can('create', App\Modules\QualityModule\Models\Evaluation::class)
                    <flux:button href="{{ route('quality.evaluations.create') }}" variant="primary" icon="plus" wire:navigate>Nueva Evaluación</flux:button>
                @endcan
            </div>
        </x-slot:actions>
        <x-slot:filters>
            <x-wfm.filter-bar>
                <flux:input wire:model.live.debounce.250ms="search" placeholder="Empleado, evaluador..." class="!w-48" />
                <flux:select wire:model.live="queueFilter" placeholder="Cola" class="!w-44">
                    <option value="">Todas</option>
                    @foreach($queues as $queue)
                        <option value="{{ $queue->id }}">{{ $queue->code }} — {{ $queue->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="teamFilter" placeholder="Equipo" class="!w-44">
                    <option value="">Todos</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </flux:select>
                <flux:input type="date" wire:model.live="dateFrom" class="!w-36" />
                <flux:input type="date" wire:model.live="dateTo" class="!w-36" />
                <flux:select wire:model.live="statusFilter" placeholder="Estado" class="!w-32">
                    <option value="">Todos</option>
                    <option value="activa">Activa</option>
                    <option value="eliminada">Eliminada</option>
                </flux:select>
            </x-wfm.filter-bar>
        </x-slot:filters>
    </x-wfm.page-header>

    <x-wfm.table :headers="[
        ['label' => 'Fecha', 'sortable' => true],
        ['label' => 'Cola'],
        ['label' => 'Empleado'],
        ['label' => 'Evaluador'],
        ['label' => 'Score', 'sortable' => true],
        ['label' => 'Estado'],
        ['label' => 'Acciones', 'align' => 'end'],
    ]" compact>
        @forelse($evaluations as $evaluation)
            <flux:table.row :key="$evaluation->id">
                <flux:table.cell class="text-sm cursor-pointer" wire:click="sortBy('dteval')">
                    {{ $evaluation->dteval?->format('d/m/Y') }}
                    @if($sortField === 'dteval')
                        <flux:icon :name="$sortDirection === 'asc' ? 'arrow-up' : 'arrow-down'" class="inline w-3 h-3 text-wfm-info" />
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    <flux:badge size="sm" color="slate">{{ $evaluation->queue?->code }}</flux:badge>
                </flux:table.cell>
                <flux:table.cell class="text-sm">{{ $evaluation->employee?->name ?? 'Desconocido' }}</flux:table.cell>
                <flux:table.cell class="text-sm">{{ $evaluation->evaluator?->name ?? 'Desconocido' }}</flux:table.cell>
                <flux:table.cell>
                    <x-wfm.adherence-badge :value="$evaluation->score ?? 0" target="60" size="xs" />
                </flux:table.cell>
                <flux:table.cell>
                    <x-wfm.agent-status :status="$evaluation->status === 'activa' ? 'available' : 'offline'" :label="$evaluation->status" size="xs" />
                </flux:table.cell>
                <flux:table.cell class="text-right">
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                        <flux:menu>
                            <flux:menu.item icon="eye" href="{{ route('quality.evaluations.show', $evaluation->id) }}" wire:navigate>Ver Detalle</flux:menu.item>
                            @can('create', [\App\Modules\QualityModule\Models\CalibrationLog::class, $evaluation])
                                <flux:menu.item icon="scale" href="{{ route('quality.calibrations.create', $evaluation->id) }}" wire:navigate>Calibrar</flux:menu.item>
                            @endcan
                            @can('create', [\App\Modules\QualityModule\Models\Feedback::class, $evaluation])
                                <flux:menu.item icon="chat-bubble-left-right" href="{{ route('quality.feedback.create', $evaluation->id) }}" wire:navigate>Dar Feedback</flux:menu.item>
                            @endcan
                        </flux:menu>
                    </flux:dropdown>
                </flux:table.cell>
            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="7">
                    <x-wfm.empty icon="magnifying-glass" message="No se encontraron evaluaciones." />
                </flux:table.cell>
            </flux:table.row>
        @endforelse
    </x-wfm.table>
</div>
