<div class="space-y-6">
    <x-wfm.page-header title="Administración de Notificaciones" description="Gestiona los eventos notificables del sistema, su alcance y canales de envío." />

    <x-wfm.section>
        <x-wfm.table :headers="['Evento', 'Descripción', 'Canales', 'Estado', '']" compact>
            @forelse($configs as $config)
                <flux:table.row :key="$config['event_type']">
                    @if($editingEventType === $config['event_type'])
                        <flux:table.cell colspan="5" class="p-0">
                            <div class="p-4 bg-wfm-surface rounded border border-wfm-info/20">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="text-sm font-semibold text-wfm-navy-800 dark:text-white">{{ $config['label'] }}</p>
                                        <p class="text-xs text-wfm-surface-muted">{{ $config['description'] }}</p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <flux:field>
                                        <flux:label>Habilitado</flux:label>
                                        <flux:switch wire:model="editIsEnabled" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Canales de Notificación</flux:label>
                                        <div class="flex flex-wrap gap-4 mt-1">
                                            @foreach($channelOptions as $channel => $channelLabel)
                                                <label class="flex items-center gap-2 text-xs cursor-pointer">
                                                    <flux:checkbox wire:model="editChannels" value="{{ $channel }}" />
                                                    {{ $channelLabel }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </flux:field>

                                    <div class="flex justify-end gap-2 pt-2 border-t border-wfm-surface-border">
                                        <flux:button wire:click="cancelEdit" variant="ghost">Cancelar</flux:button>
                                        <flux:button wire:click="save" variant="primary">Guardar</flux:button>
                                    </div>
                                </div>
                            </div>
                        </flux:table.cell>
                    @else
                        <flux:table.cell>
                            <div>
                                <p class="text-xs font-medium text-wfm-navy-800 dark:text-white">{{ $config['label'] }}</p>
                                <p class="text-[10px] text-wfm-surface-muted font-mono">{{ $config['event_type'] }}</p>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-wfm-surface-muted max-w-xs truncate">{{ $config['description'] }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @foreach($config['channels'] as $ch)
                                    <flux:badge size="sm" :color="match($ch) {
                                        'database' => 'slate',
                                        'broadcast' => 'blue',
                                        'webex' => 'green',
                                        'mail' => 'orange',
                                        default => 'slate',
                                    }">{{ $channelOptions[$ch] ?? $ch }}</flux:badge>
                                @endforeach
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :size="'sm'" :color="$config['is_enabled'] ? 'emerald' : 'red'">
                                {{ $config['is_enabled'] ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button wire:click="startEdit('{{ $config['event_type'] }}')" variant="ghost" size="sm" icon="pencil-square" />
                        </flux:table.cell>
                    @endif
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <x-wfm.empty icon="bell" message="No hay configuraciones de notificación disponibles." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </x-wfm.table>
    </x-wfm.section>
</div>
