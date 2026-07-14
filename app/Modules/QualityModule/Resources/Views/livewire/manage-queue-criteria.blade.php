<div class="space-y-8">
    <div>
        <flux:heading size="xl" level="1">Criterios por Cola</flux:heading>
        <flux:subheading>Administre qué criterios aplican a cada cola y su versionado</flux:subheading>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-1">
            <flux:card>
                <div class="space-y-2">
                    <flux:heading size="sm">Seleccionar Cola</flux:heading>
                    <flux:separator />
                    <div class="space-y-1">
                        @foreach($queues as $queue)
                            <button wire:click="selectQueue('{{ $queue->id }}')"
                                    class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors duration-150
                                    {{ $selectedQueueId === $queue->id ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-900/30 dark:text-indigo-300' : 'hover:bg-slate-50 dark:hover:bg-slate-800/50 text-slate-700 dark:text-slate-300' }}">
                                <span class="font-mono text-xs">{{ $queue->code }}</span>
                                <span class="block text-xs text-slate-400">{{ $queue->name }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </flux:card>

            @if($selectedQueueId && $availableCriteria->isNotEmpty())
                <flux:card class="mt-4">
                    <div class="space-y-2">
                        <flux:heading size="sm">Agregar Criterio</flux:heading>
                        <flux:separator />
                        <flux:select wire:model="newCriteriaId" placeholder="Seleccionar criterio...">
                            @foreach($availableCriteria as $criteria)
                                <option value="{{ $criteria->id }}">
                                    {{ $criteria->code }} — {{ $criteria->currentVersion?->criterio_text }}
                                </option>
                            @endforeach
                        </flux:select>
                        <flux:button wire:click="addCriteria" class="w-full" icon="plus" size="sm">Asignar</flux:button>
                    </div>
                </flux:card>
            @endif
        </div>

        <div class="lg:col-span-3">
            @if(!$selectedQueueId)
                <flux:card>
                    <div class="text-center py-12">
                        <flux:icon name="arrow-left" class="w-12 h-12 text-slate-200 mx-auto mb-3" />
                        <flux:text class="text-slate-400">Seleccione una cola para ver sus criterios</flux:text>
                    </div>
                </flux:card>
            @else
                <flux:card>
                    <div class="space-y-4">
                        @if(session('message'))
                            <flux:callout color="green" icon="check-circle">{{ session('message') }}</flux:callout>
                        @endif
                        @if(session('error'))
                            <flux:callout color="red" icon="exclamation-circle">{{ session('error') }}</flux:callout>
                        @endif

                        <div class="flex items-center justify-between">
                            <flux:heading size="lg">
                                @php $queue = $queues->firstWhere('id', $selectedQueueId); @endphp
                                {{ $queue?->code }} — {{ $queue?->name ?? '' }}
                            </flux:heading>
                            <flux:text size="xs" class="text-slate-400">{{ $assignedCriteria->count() }} criterios</flux:text>
                        </div>

                        <flux:separator />

                        @if($assignedCriteria->isEmpty())
                            <div class="text-center py-8">
                                <flux:text class="text-slate-400">No hay criterios asignados a esta cola</flux:text>
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach($assignedCriteria as $index => $ac)
                                    <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-900 rounded-md {{ !$ac['is_active'] ? 'opacity-50' : '' }}">
                                        <div class="flex flex-col items-center gap-1 pt-1">
                                            <button wire:click="moveUp('{{ $ac['id'] }}')" class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200" title="Subir">
                                                <flux:icon name="chevron-up" class="w-3 h-3" />
                                            </button>
                                            <span class="text-xs font-mono text-slate-400">{{ $ac['orden'] }}</span>
                                            <button wire:click="moveDown('{{ $ac['id'] }}')" class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200" title="Bajar">
                                                <flux:icon name="chevron-down" class="w-3 h-3" />
                                            </button>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <flux:badge size="sm" color="indigo" inset="top">{{ $ac['codigo'] }}</flux:badge>
                                                <flux:badge size="sm" color="slate" inset="top">v{{ $ac['version'] }}</flux:badge>
                                                <flux:text size="xs" class="text-slate-400 ml-auto">Score: {{ $ac['puntaje'] }}</flux:text>
                                            </div>
                                            <flux:text size="sm" class="mt-1">{{ $ac['criterio_text'] }}</flux:text>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button wire:click="editVersion('{{ $ac['criteria_version_id'] }}')"
                                                    class="p-1.5 rounded-md text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                                                    title="Editar (nueva versión)">
                                                <flux:icon name="pencil" class="w-4 h-4" />
                                            </button>
                                            <button wire:click="toggleActive('{{ $ac['id'] }}')"
                                                    class="p-1.5 rounded-md text-slate-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20"
                                                    title="{{ $ac['is_active'] ? 'Desactivar' : 'Activar' }}">
                                                <flux:icon name="{{ $ac['is_active'] ? 'eye' : 'eye-off' }}" class="w-4 h-4" />
                                            </button>
                                            <button wire:click="removeCriteria('{{ $ac['id'] }}')"
                                                    wire:confirm="¿Eliminar este criterio de la cola?"
                                                    class="p-1.5 rounded-md text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                                                    title="Remover de la cola">
                                                <flux:icon name="trash" class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endif
        </div>
    </div>

    <flux:modal wire:model="showEditModal" class="md:min-w-[32rem] space-y-4">
        @if($showEditModal)
            <div>
                <flux:heading size="lg">Editar Criterio</flux:heading>
                <flux:subheading>Se crear&aacute; una nueva versi&oacute;n. La anterior quedar&aacute; como hist&oacute;rico.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="editCriterioText" label="Texto del criterio *" maxlength="500" />
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="editPuntaje" label="Puntaje *" type="number" min="1" max="100" />
                    <flux:input wire:model="editDescripcion" label="Descripci&oacute;n" maxlength="1000" />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t dark:border-slate-800">
                <flux:modal.close>
                    <flux:button variant="subtle">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button wire:click="saveVersion" variant="primary">Guardar Versi&oacute;n</flux:button>
            </div>
        @endif
    </flux:modal>
</div>
