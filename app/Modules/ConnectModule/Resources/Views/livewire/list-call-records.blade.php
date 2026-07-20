<div class="space-y-6">
    <x-wfm.page-header title="Registro de Llamadas" description="Resumen de llamadas gestionadas." search searchWire="search" searchPlaceholder="Buscar teléfono o identificador...">
        <x-slot:actions>
            <flux:button href="{{ route('contact-center.calls.create') }}" variant="primary" icon="plus" wire:navigate>Nuevo registro</flux:button>
        </x-slot:actions>
        <x-slot:filters>
            <x-wfm.filter-bar>
                <flux:select wire:model.live="statusFilter" placeholder="Estado" class="!w-36">
                    <flux:select.option value="">Todos</flux:select.option>
                    <flux:select.option value="open">Abierto</flux:select.option>
                    <flux:select.option value="pending_operator">Pendiente</flux:select.option>
                    <flux:select.option value="closed">Cerrado</flux:select.option>
                </flux:select>
                <flux:input type="date" wire:model.live="dateFrom" class="!w-36" />
                <flux:input type="date" wire:model.live="dateTo" class="!w-36" />
                <flux:select wire:model.live="employeeFilter" placeholder="Empleado" class="!w-44">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach($employees as $employee)
                        <flux:select.option value="{{ $employee->id }}">{{ $employee->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </x-wfm.filter-bar>
        </x-slot:filters>
    </x-wfm.page-header>

    @if (session()->has('success'))
        <div class="rounded-md bg-wfm-success/10 border border-wfm-success/20 px-3 py-2 text-xs text-wfm-success">
            {{ session('success') }}
        </div>
    @endif

    <x-wfm.section>
        <div wire:loading.delay.class="opacity-50" class="transition-opacity">
            <x-wfm.table :headers="['ID', 'Teléfono', 'Asegurado', 'Estado', 'Tipo', 'Subtipo', 'Atiende', 'Creado', 'Cerrado', 'Acciones']" compact>
                @forelse($records as $record)
                    <flux:table.row :key="$record->id">
                        <flux:table.cell>{{ $record->id }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">{{ $record->phone_number }}</flux:table.cell>
                        <flux:table.cell class="text-xs">{{ $record->citizen_identifier ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <x-wfm.agent-status :status="match($record->status) { 'open' => 'available', 'pending_operator' => 'break', 'closed' => 'training', default => 'offline' }" :label="ucfirst(str_replace('_', ' ', $record->status))" size="xs" />
                        </flux:table.cell>
                        <flux:table.cell class="text-xs">{{ $record->queue?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-xs">{{ $record->caseSubtype?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-xs">{{ $record->employee?->full_name ?? 'Sin asignar' }}</flux:table.cell>
                        <flux:table.cell class="text-xs">{{ $record->ivr_started_at?->format('Y-m-d H:i') }}</flux:table.cell>
                        <flux:table.cell class="text-xs">{{ $record->closed_at?->format('Y-m-d H:i') ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:button href="{{ route('contact-center.calls.edit', $record) }}" variant="ghost" size="sm" wire:navigate>Editar</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="10">
                            <x-wfm.empty icon="phone" message="No hay registros que coincidan con el filtro." />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </x-wfm.table>
        </div>
        <div class="mt-4">{{ $records->links() }}</div>
    </x-wfm.section>
</div>
