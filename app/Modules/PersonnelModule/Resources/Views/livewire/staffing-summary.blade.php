<div class="space-y-8">
    <x-wfm.page-header title="Inventario de Staffing" description="Distribución y demografía de la fuerza laboral activa">
        <x-slot:actions>
            <flux:button icon="arrow-path" wire:click="$refresh" variant="ghost">Actualizar</flux:button>
        </x-slot:actions>
    </x-wfm.page-header>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <x-wfm.kpi :value="$stats['total']" label="Total Empleados" icon="users" />
        <x-wfm.kpi :value="$stats['active']" label="Activos" icon="check-circle" color="success" />
        <x-wfm.kpi :value="$stats['inactive']" label="Inactivos" icon="x-circle" color="danger" />
        <x-wfm.kpi :value="$stats['managers']" label="Líderes / Managers" icon="user-group" color="info" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <x-wfm.section title="Personal por Equipo">
            @forelse($byTeam as $team)
                <div class="flex items-center gap-4 mb-3 last:mb-0">
                    <div class="flex-1">
                        <div class="flex justify-between mb-1">
                            <span class="text-xs font-medium text-wfm-navy-800 dark:text-white">{{ $team['name'] }}</span>
                            <span class="text-xs text-wfm-surface-muted">{{ $team['employees_count'] }}</span>
                        </div>
                        <div class="w-full bg-wfm-surface rounded-full h-1.5">
                            <div class="bg-wfm-navy-500 h-1.5 rounded-full" style="width: {{ $stats['active'] > 0 ? round(($team['employees_count'] / $stats['active']) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            @empty
                <x-wfm-empty icon="users" message="Sin datos de equipos" />
            @endforelse
        </x-wfm.section>

        <x-wfm.section title="Modalidad de Contratación">
            <x-wfm.table :headers="['Estatus', 'Cantidad', '%']" compact>
                @foreach($byStatus as $status)
                    <flux:table.row :key="$status['id']">
                        <flux:table.cell class="font-medium">{{ $status['name'] }}</flux:table.cell>
                        <flux:table.cell>{{ $status['employees_count'] }}</flux:table.cell>
                        <flux:table.cell class="text-wfm-surface-muted">
                            {{ $stats['active'] > 0 ? round(($status['employees_count'] / $stats['active']) * 100, 1) : 0 }}%
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </x-wfm.table>
        </x-wfm.section>
    </div>

    <x-wfm.section title="Distribución por Cargos / Posiciones">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
            @forelse($byPosition as $position)
                @if($position['employees_count'] > 0)
                    <div class="flex items-center justify-between p-3 rounded border border-wfm-surface-border bg-wfm-surface/50">
                        <div class="min-w-0">
                            <p class="text-[10px] uppercase tracking-wider text-wfm-surface-muted font-semibold">{{ $position['position_code'] ?? '' }}</p>
                            <p class="text-xs font-medium text-wfm-navy-800 dark:text-white truncate">{{ $position['name'] }}</p>
                        </div>
                        <span class="inline-flex items-center justify-center min-w-[1.5rem] h-5 px-1.5 rounded-full bg-wfm-navy-100 dark:bg-wfm-navy-800 text-[10px] font-bold text-wfm-navy-700 dark:text-wfm-blue-200">{{ $position['employees_count'] }}</span>
                    </div>
                @endif
            @empty
                <div class="col-span-3">
                    <x-wfm-empty icon="briefcase" message="Sin datos de posiciones" />
                </div>
            @endforelse
        </div>
    </x-wfm.section>
</div>
