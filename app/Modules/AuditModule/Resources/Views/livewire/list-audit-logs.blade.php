<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Auditoría de Cambios</flux:heading>
            <flux:subheading>Trazabilidad completa de acciones y modificaciones en el sistema</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button wire:click.prevent="export('csv')" icon="document-arrow-down" variant="ghost">CSV</flux:button>
            <flux:button wire:click.prevent="export('json')" icon="code-bracket" variant="ghost">JSON</flux:button>
        </div>
    </div>

    <flux:card>
        <div class="space-y-6">
            <!-- Filtros -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <flux:input wire:model.debounce.250ms="search" label="Búsqueda rápida" placeholder="Entidad, acción, IP..." />
                <flux:input wire:model="action" label="Acción específica" placeholder="created, updated, deleted" />
                <flux:input wire:model="entityType" label="Tipo de Entidad" placeholder="Ej. Employee" />
                <div class="grid grid-cols-2 gap-2">
                    <flux:input wire:model="dateFrom" type="date" label="Desde" />
                    <flux:input wire:model="dateTo" type="date" label="Hasta" />
                </div>
            </div>

            <div class="flex justify-between items-center">
                <flux:select wire:model="perPage" label="Mostrar" class="w-24">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </flux:select>
            </div>

            <!-- Tabla -->
            <flux:table :paginate="$auditLogs">
                <flux:table.columns>
                    <flux:table.column>Fecha y Hora</flux:table.column>
                    <flux:table.column>Entidad / Recurso</flux:table.column>
                    <flux:table.column>Acción</flux:table.column>
                    <flux:table.column>Usuario Responsable</flux:table.column>
                    <flux:table.column>Dirección IP</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($auditLogs as $log)
                        <flux:table.row :key="$log->id" wire:click="showDetail({{ $log->id }})" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <flux:table.cell class="font-mono text-xs">{{ $log->created_at->format('Y-m-d H:i:s') }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex flex-col">
                                    <flux:text size="sm" class="font-bold">{{ class_basename($log->entity_type) }}</flux:text>
                                    <flux:text size="xs" class="text-zinc-400">ID: {{ $log->entity_id }}</flux:text>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="{{ match($log->action) { 'created' => 'emerald', 'updated' => 'indigo', 'deleted' => 'rose', default => 'zinc' } }}" inset="top">
                                    {{ strtoupper($log->action) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    @if($log->user)
                                        <flux:avatar :initials="$log->user->initials()" size="xs" />
                                        <flux:text size="sm">{{ $log->user->name }}</flux:text>
                                    @else
                                        <flux:icon name="cpu-chip" class="w-4 h-4 text-zinc-400" />
                                        <flux:text size="sm" class="text-zinc-400 italic">Sistema</flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">{{ $log->ip_address ?? 'N/A' }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-12">
                                <flux:icon name="magnifying-glass" class="w-12 h-12 text-zinc-200 mx-auto mb-3" />
                                <flux:text class="text-zinc-400">No se encontraron registros de auditoría</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <flux:modal wire:model="showDetailModal" class="md:min-w-[45rem] space-y-6">
        @if($selectedLog)
            <div>
                <flux:heading size="lg">Detalle de Cambio</flux:heading>
                <flux:subheading>{{ $selectedLog->created_at->format('Y-m-d H:i:s') }} —
                    {{ class_basename($selectedLog->entity_type) }} #{{ $selectedLog->entity_id }}</flux:subheading>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <span class="text-xs text-zinc-500 uppercase font-bold">Acción</span>
                    <flux:badge size="sm" color="{{ match($selectedLog->action) { 'created' => 'emerald', 'updated' => 'indigo', 'deleted' => 'rose', default => 'zinc' } }}" inset="top">
                        {{ strtoupper($selectedLog->action) }}
                    </flux:badge>
                </div>
                <div class="space-y-1">
                    <span class="text-xs text-zinc-500 uppercase font-bold">Usuario</span>
                    <p class="text-sm">{{ $selectedLog->user?->name ?? 'Sistema' }}</p>
                    <p class="text-xs text-zinc-400">IP: {{ $selectedLog->ip_address ?? 'N/A' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <span class="text-xs text-zinc-500 uppercase font-bold">Valores Anteriores (before)</span>
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border overflow-auto max-h-64">
                        @if($selectedLog->before)
                            <pre class="text-xs font-mono leading-relaxed">{{ json_encode($selectedLog->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        @else
                            <p class="text-xs text-zinc-400 italic">Sin datos previos</p>
                        @endif
                    </div>
                </div>
                <div class="space-y-2">
                    <span class="text-xs text-zinc-500 uppercase font-bold">Valores Nuevos (after)</span>
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border overflow-auto max-h-64">
                        @if($selectedLog->after)
                            <pre class="text-xs font-mono leading-relaxed">{{ json_encode($selectedLog->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        @else
                            <p class="text-xs text-zinc-400 italic">Sin datos nuevos</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="subtle">Cerrar</flux:button>
                </flux:modal.close>
            </div>
        @endif
    </flux:modal>
</div>
