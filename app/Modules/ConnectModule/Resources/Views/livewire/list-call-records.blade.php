<div class="space-y-8">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <flux:heading size="xl">Tus Registros de Hoy</flux:heading>
            <flux:subheading>Resumen de llamadas gestionadas durante la jornada actual.</flux:subheading>
        </div>
        <div>
            <flux:button href="{{ route('contact-center.calls.create') }}" variant="primary" wire:navigate icon="plus">
                Nuevo registro
            </flux:button>
        </div>
    </div>

    <flux:card>
        <flux:input icon="magnifying-glass" wire:model.live.debounce.300ms="search"
            placeholder="Buscar teléfono o identificador" />
    </flux:card>

    <flux:card>
        <div class="grid gap-4 md:grid-cols-4">
            <flux:select wire:model.live="statusFilter" placeholder="Filtrar por estado">
                <flux:select.option value="">Todos</flux:select.option>
                <flux:select.option value="open">Abierto</flux:select.option>
                <flux:select.option value="pending_operator">Pendiente Operador</flux:select.option>
                <flux:select.option value="closed">Cerrado</flux:select.option>
            </flux:select>

            <flux:input wire:model.live="dateFrom" type="date" placeholder="Desde" />
            <flux:input wire:model.live="dateTo" type="date" placeholder="Hasta" />

            <flux:select wire:model.live="employeeFilter" placeholder="Filtrar por empleado">
                <flux:select.option value="">Todos</flux:select.option>
                @foreach($employees as $employee)
                    <flux:select.option value="{{ $employee->id }}">{{ $employee->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    @if (session()->has('success'))
        <flux:card color="green" class="border-green-200 bg-green-50 text-green-600">
            <flux:text>{{ session('success') }}</flux:text>
        </flux:card>
    @endif

    <flux:card>
        <flux:table :paginate="$records">
            <flux:table.columns>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">ID</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Teléfono</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Asegurado</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Estado</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Tipo</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Subtipo</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Atiende</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Creado</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Cerrado</flux:table.column>
                <flux:table.column align="end" class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($records as $record)
                    <flux:table.row :key="$record->id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 py-2">
                        <flux:table.cell class="py-2">{{ $record->id }}</flux:table.cell>
                        <flux:table.cell class="py-2">{{ $record->phone_number }}</flux:table.cell>
                        <flux:table.cell class="py-2">{{ $record->citizen_identifier ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="py-2">
                            <flux:badge
                                :color="['open' => 'green', 'pending_operator' => 'amber', 'closed' => 'blue'][$record->status] ?? 'slate'"
                                size="sm">
                                {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="py-2">{{ $record->queue?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="py-2">{{ $record->caseSubtype?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="py-2">{{ $record->employee?->full_name ?? 'Sin asignar' }}</flux:table.cell>
                        <flux:table.cell class="py-2">{{ $record->ivr_started_at?->format('Y-m-d H:i') }}</flux:table.cell>
                        <flux:table.cell class="py-2">{{ $record->closed_at?->format('Y-m-d H:i') ?? '—' }}</flux:table.cell>
                        <flux:table.cell align="end" class="py-2">
                            <flux:button href="{{ route('contact-center.calls.edit', $record) }}" variant="ghost" size="sm"
                                wire:navigate>
                                Editar
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell class="py-2" colspan="10" align="center">
                            <flux:text>No hay registros que coincidan con el filtro.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">{{ $records->links() }}</div>
    </flux:card>
</div>
