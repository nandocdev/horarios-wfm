<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <flux:heading size="xl">Registros de Llamadas</flux:heading>
            <flux:subheading>Gestión de registros de llamadas del Contact Center.</flux:subheading>
        </div>
        <div>
            <flux:button href="{{ route('connect.call-records.create') }}" variant="primary" wire:navigate icon="plus">
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
                <flux:select.option value="pending_operator">Pendiente Operador</flux:select.option>
                <flux:select.option value="completed">Completado</flux:select.option>
                <flux:select.option value="closed">Cerrado</flux:select.option>
            </flux:select>

            <flux:input wire:model.live="dateFrom" type="date" placeholder="Desde" />
            <flux:input wire:model.live="dateTo" type="date" placeholder="Hasta" />

            <flux:select wire:model.live="employeeFilter" placeholder="Filtrar por empleado">
                <flux:select.option value="">Todos</flux:select.option>
            </flux:select>
        </div>
    </flux:card>

    <flux:card>
        <flux:table :paginate="$records">
            <flux:table.columns>
                <flux:table.column>ID</flux:table.column>
                <flux:table.column>Teléfono</flux:table.column>
                <flux:table.column>Identificador</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Cola</flux:table.column>
                <flux:table.column>Subtipo</flux:table.column>
                <flux:table.column>Agente</flux:table.column>
                <flux:table.column>Inicio</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($records as $record)
                    <flux:table.row :key="$record->id">
                        <flux:table.cell>{{ $record->id }}</flux:table.cell>
                        <flux:table.cell>{{ $record->phone_number }}</flux:table.cell>
                        <flux:table.cell>{{ $record->citizen_identifier ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm">
                                {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $record->queue?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $record->caseSubtype?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $record->employee?->full_name ?? 'Sin asignar' }}</flux:table.cell>
                        <flux:table.cell>{{ $record->ivr_started_at?->format('Y-m-d H:i') }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button href="{{ route('connect.call-records.edit', $record) }}" variant="ghost" size="sm"
                                wire:navigate>
                                Editar
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9" align="center">
                            <flux:text>No hay registros que coincidan con el filtro.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">{{ $records->links() }}</div>
    </flux:card>
</div>
