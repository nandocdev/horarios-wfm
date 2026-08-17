<div class="space-y-6">
    <x-wfm.page-header title="Nuevo Registro de Llamada" description="Ingresa los detalles de la interacción recibida." tour="connect.call-create" data-tour="call-create-header">
        <x-slot:actions>
            <flux:button href="{{ route('contact-center.calls.index') }}" wire:navigate variant="ghost" icon="arrow-left">Volver al Listado</flux:button>
        </x-slot:actions>
    </x-wfm.page-header>

    @if(count($qualityCapsules) > 0)
        <div x-data="{ current: 0, count: {{ count($qualityCapsules) }} }"
            x-init="setInterval(() => { current = (current + 1) % count }, 8000)"
            class="w-full relative overflow-hidden rounded-md border border-wfm-info/20 bg-wfm-info/5">
            <div class="flex transition-transform duration-150 ease-in-out" :style="`transform: translateX(-${current * 100}%)`">
                @foreach($qualityCapsules as $capsule)
                    <div class="w-full flex-shrink-0 p-3">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 p-1.5 bg-wfm-info/10 rounded-md">
                                <flux:icon :name="$capsule['icon']" class="w-5 h-5 text-wfm-info" />
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-wfm-navy-800 dark:text-white">{{ $capsule['title'] }}</p>
                                <p class="text-[10px] text-wfm-surface-muted">{{ $capsule['content'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="absolute bottom-2 right-3 flex items-center gap-1">
                @foreach($qualityCapsules as $index => $capsule)
                    <button @click="current = {{ $index }}" class="h-1 rounded-full transition-all duration-150"
                        :class="current === {{ $index }} ? 'w-4 bg-wfm-info' : 'w-1.5 bg-wfm-info/30'"></button>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div data-tour="call-create-form" class="lg:col-span-2">
            <x-wfm.section>
                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Canal de Entrada</flux:label>
                            <flux:select wire:model.live="form.channel_id" placeholder="Selecciona canal...">
                                @foreach($channels as $channel)
                                    <flux:select.option value="{{ $channel->id }}">{{ $channel->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="form.channel_id" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Cola / Servicio</flux:label>
                            <flux:select wire:model.live="form.queue_id" placeholder="Selecciona cola...">
                                @foreach($queues as $queue)
                                    <flux:select.option value="{{ $queue->id }}">{{ $queue->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="form.queue_id" />
                        </flux:field>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Número de Teléfono</flux:label>
                            <flux:input wire:model.live.debounce.500ms="form.phone_number" placeholder="Ej. 22334455" />
                            <flux:error name="form.phone_number" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Identificación</flux:label>
                            <flux:input wire:model.live.debounce.500ms="form.citizen_identifier" placeholder="Ej. 8-725-927">
                                <x-slot name="append">
                                    <div wire:loading wire:target="form.citizen_identifier">
                                        <flux:icon.arrow-path class="animate-spin text-wfm-surface-muted w-4 h-4" />
                                    </div>
                                </x-slot>
                            </flux:input>
                            <flux:error name="form.citizen_identifier" />
                        </flux:field>
                    </div>
                    <flux:field>
                        <flux:label>Tipificación del Caso</flux:label>
                        <flux:select wire:model="form.case_subtype_id" placeholder="Selecciona el motivo...">
                            <flux:select.option value="0">-- Selecciona un subtipo --</flux:select.option>
                            @foreach($subtypes as $subtype)
                                <flux:select.option value="{{ $subtype->id }}">{{ $subtype->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="form.case_subtype_id" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Observaciones / Detalles</flux:label>
                        <flux:textarea wire:model="form.description" rows="4" placeholder="Describe brevemente la solicitud del ciudadano..." />
                        <flux:error name="form.description" />
                    </flux:field>
                    <div class="flex justify-end gap-3 pt-4 border-t border-wfm-surface-border">
                        <flux:button type="submit" variant="primary" icon="check">Guardar Registro</flux:button>
                    </div>
                </form>
            </x-wfm.section>
        </div>

        <div class="space-y-4">
            <x-wfm.section title="Información del Sistema">
                <p class="text-xs text-wfm-surface-muted mb-3">Estás registrando una llamada de forma manual. Asegúrate de tipificar correctamente.</p>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center gap-2 text-wfm-surface-muted">
                        <flux:icon.clock class="w-3.5 h-3.5" />
                        <span>Hora Actual: {{ now()->format('H:i') }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-wfm-surface-muted">
                        <flux:icon.user class="w-3.5 h-3.5" />
                        <span>Agente: {{ auth()->user()->name }}</span>
                    </div>
                </div>
            </x-wfm.section>

            @if(count($callScript) > 0)
                <x-wfm.section title="Script de Atención" icon="clipboard-document-list">
                    <div class="space-y-2" x-data="{ checked: [] }">
                        @foreach($callScript as $index => $step)
                            <div class="flex items-start gap-2">
                                <flux:checkbox wire:key="script-step-{{ $index }}" x-model="checked" value="{{ $index }}" id="step-{{ $index }}" />
                                <label for="step-{{ $index }}"
                                    class="text-xs cursor-pointer select-none"
                                    :class="checked.includes('{{ $index }}') ? 'line-through text-wfm-surface-muted' : 'text-wfm-navy-700 dark:text-white/80'">
                                    {{ $step }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </x-wfm.section>
            @endif

            @if($citizenData)
                @php $hasRight = ($citizenData['tiene_derecho'] ?? 'false') === 'true'; @endphp
                <x-wfm.section :title="$hasRight ? 'CON DERECHO' : 'SIN DERECHO'" :icon="$hasRight ? 'check-circle' : 'x-circle'">
                    @if(isset($citizenData['asegurado']))
                        <p class="text-xs font-medium text-wfm-navy-800 dark:text-white">{{ $citizenData['asegurado']['nombre'] ?? 'N/A' }}</p>
                        <div class="flex justify-between text-[10px] text-wfm-surface-muted mt-1">
                            <span>Cuota: {{ $citizenData['asegurado']['cuota_pagada'] ?? 'N/A' }}</span>
                            <span>Vence: {{ $citizenData['asegurado']['valido_hasta'] ?? 'N/A' }}</span>
                        </div>
                    @endif
                </x-wfm.section>
            @elseif($isValidating)
                <div class="card-wfm p-4 text-center space-y-2 motion-safe:animate-pulse">
                    <flux:icon.arrow-path class="animate-spin text-wfm-surface-muted mx-auto w-5 h-5" />
                    <p class="text-xs text-wfm-surface-muted">Validando derechos en tiempo real...</p>
                </div>
            @endif
        </div>
    </div>

    <x-wfm.section title="Historial Reciente del Ciudadano" description="Últimas 10 interacciones asociadas al número telefónico ingresado.">
        <x-wfm.table :headers="['Fecha / Hora', 'Teléfono', 'Cola', 'Tipificación', 'Estado', 'Acciones']" compact>
            @forelse($history as $record)
                <flux:table.row :key="$record->id">
                    <flux:table.cell class="text-xs">
                        <span class="font-medium">{{ $record->ivr_started_at?->format('d/m/Y') ?? 'Hoy' }}</span>
                        <span class="text-wfm-surface-muted ml-1">{{ $record->ivr_started_at?->format('H:i:s') ?? '' }}</span>
                    </flux:table.cell>
                    <flux:table.cell class="text-xs font-medium">{{ $record->phone_number }}</flux:table.cell>
                    <flux:table.cell><flux:badge size="sm" color="slate">{{ $record->queue?->name ?? 'N/A' }}</flux:badge></flux:table.cell>
                    <flux:table.cell class="text-xs">{{ $record->caseSubtype?->name ?? 'Sin tipificar' }}</flux:table.cell>
                    <flux:table.cell>
                        <x-wfm.agent-status :status="match($record->status) { 'open' => 'available', 'closed' => 'training', default => 'break' }" :label="$record->status" size="xs" />
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button wire:click="showRecordDetails({{ $record->id }})" variant="ghost" size="sm" icon="eye" />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        <x-wfm.empty icon="phone" message="No has realizado registros recientemente." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </x-wfm.table>
    </x-wfm.section>

    <flux:modal name="record-details" class="min-w-[500px]">
        @if($selectedRecord)
        <div data-tour="call-create-history" class="space-y-4">
                <div>
                    <flux:heading size="lg">Detalles del Registro #{{ $selectedRecord->id }}</flux:heading>
                    <flux:subheading>Información completa de la interacción seleccionada.</flux:subheading>
                </div>
                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <p class="kpi-label">Canal / Cola</p>
                        <p class="font-medium text-wfm-navy-800 dark:text-white">{{ $selectedRecord->queue?->channel?->name ?? 'N/A' }} - {{ $selectedRecord->queue?->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="kpi-label">Estado / Fecha</p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <x-wfm.agent-status :status="match($selectedRecord->status) { 'closed' => 'available', default => 'break' }" :label="$selectedRecord->status" size="xs" />
                            <span class="text-wfm-surface-muted">{{ $selectedRecord->ivr_started_at?->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                    <div>
                        <p class="kpi-label">Agente</p>
                        <p class="font-medium text-wfm-navy-800 dark:text-white">{{ $selectedRecord->employee?->full_name ?? $selectedRecord->raw_agent_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="kpi-label">Tipificación</p>
                        <p class="font-medium text-wfm-navy-800 dark:text-white">{{ $selectedRecord->caseSubtype?->name ?? 'Sin tipificar' }}</p>
                    </div>
                </div>
                <div>
                    <p class="kpi-label mb-1">Descripción</p>
                    <div class="p-3 bg-wfm-surface rounded-md border border-wfm-surface-border text-xs italic">
                        {{ $selectedRecord->description ?: 'No se ingresaron detalles adicionales.' }}
                    </div>
                </div>
                <div class="border-t border-wfm-surface-border pt-4">
                    <p class="text-xs font-semibold text-wfm-navy-800 dark:text-white mb-3">Tiempos de Interacción</p>
                    <div class="grid grid-cols-4 gap-2">
                        <div class="text-center p-2 bg-wfm-info/5 rounded border border-wfm-info/20">
                            <p class="text-[10px] text-wfm-info">Habla</p>
                            <p class="text-sm font-bold text-wfm-info">{{ $selectedRecord->talk_time ?? 0 }}s</p>
                        </div>
                        <div class="text-center p-2 bg-wfm-warning/5 rounded border border-wfm-warning/20">
                            <p class="text-[10px] text-wfm-warning">Espera</p>
                            <p class="text-sm font-bold text-wfm-warning">{{ $selectedRecord->ring_time ?? 0 }}s</p>
                        </div>
                        <div class="text-center p-2 bg-wfm-success/5 rounded border border-wfm-success/20">
                            <p class="text-[10px] text-wfm-success">Trabajo</p>
                            <p class="text-sm font-bold text-wfm-success">{{ $selectedRecord->work_time ?? 0 }}s</p>
                        </div>
                        <div class="text-center p-2 bg-wfm-surface rounded border border-wfm-surface-border">
                            <p class="text-[10px] text-wfm-surface-muted">Cola</p>
                            <p class="text-sm font-bold">{{ $selectedRecord->queue_time ?? 0 }}s</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <flux:modal.close>
                        <flux:button variant="primary">Entendido</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
