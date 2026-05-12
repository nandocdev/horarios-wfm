<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Audit Logs</h1>
                <div class="flex gap-2">
                    <flux:button variant="secondary" href="{{ route('dashboard') }}">Volver</flux:button>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <flux:input wire:model.debounce.250ms="search" label="Buscar" placeholder="Entidad, acción, IP" />
                <flux:input wire:model="action" label="Acción" placeholder="created, updated, deleted" />
                <flux:input wire:model="entityType" label="Tipo de Entidad" placeholder="App\\Modules\\..." />
                <div class="grid grid-cols-2 gap-2">
                    <flux:input wire:model="dateFrom" type="date" label="Desde" />
                    <flux:input wire:model="dateTo" type="date" label="Hasta" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Por página</label>
                <select wire:model="perPage" class="mt-1 block w-24 rounded border-gray-300">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <flux:table :paginate="$auditLogs">
                    <flux:table.columns>
                        <flux:table.column>Fecha</flux:table.column>
                        <flux:table.column>Entidad</flux:table.column>
                        <flux:table.column>Acción</flux:table.column>
                        <flux:table.column>Usuario</flux:table.column>
                        <flux:table.column>IP</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($auditLogs as $log)
                            <flux:table.row :key="$log->id">
                                <flux:table.cell>{{ $log->created_at->format('Y-m-d H:i:s') }}</flux:table.cell>
                                <flux:table.cell>{{ class_basename($log->entity_type) }} ({{ $log->entity_id }})
                                </flux:table.cell>
                                <flux:table.cell>{{ ucfirst($log->action) }}</flux:table.cell>
                                <flux:table.cell>{{ optional($log->user)->name ?? 'Sistema' }}</flux:table.cell>
                                <flux:table.cell>{{ $log->ip_address ?? 'N/A' }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center py-8 text-gray-500">
                                    No se encontraron registros.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="mt-4 flex gap-2">
                <flux:button wire:click.prevent="export('csv')" variant="secondary">Exportar CSV</flux:button>
                <flux:button wire:click.prevent="export('json')" variant="secondary">Exportar JSON</flux:button>
            </div>

            @if($auditLogs->hasPages())
                <div class="mt-4">{{ $auditLogs->links() }}</div>
            @endif
        </div>
    </div>
</div>
