<div class="space-y-8 max-w-5xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Nuevo Registro de Llamada</flux:heading>
            <flux:subheading>Ingresa los detalles de la interacción recibida.</flux:subheading>
        </div>
        <flux:button href="{{ route('contact-center.calls.index') }}" wire:navigate variant="subtle" icon="arrow-left">
            Volver al Listado
        </flux:button>
    </div>

    {{-- Cápsulas de Calidad (Slider) --}}
    @if(count($qualityCapsules) > 0)
    <div x-data="{ current: 0, count: {{ count($qualityCapsules) }} }" 
         x-init="setInterval(() => { current = (current + 1) % count }, 8000)" 
         class="w-full relative overflow-hidden rounded-md border border-blue-200 dark:border-blue-800/50 bg-blue-50/50 dark:bg-blue-900/10">
        <div class="flex transition-transform duration-150 ease-in-out" :style="`transform: translateX(-${current * 100}%)`">
            @foreach($qualityCapsules as $index => $capsule)
            <div class="w-full flex-shrink-0 p-4">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 p-2 bg-blue-100 dark:bg-blue-800/50 rounded-md">
                        <flux:icon icon="{{ $capsule['icon'] }}" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="flex-1">
                        <flux:heading size="sm" class="text-blue-800 dark:text-blue-300">{{ $capsule['title'] }}</flux:heading>
                        <flux:text size="sm" class="text-blue-700/80 dark:text-blue-400/80">{{ $capsule['content'] }}</flux:text>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        {{-- Controles del slider --}}
        <div class="absolute bottom-2 right-4 flex items-center gap-1.5">
            @foreach($qualityCapsules as $index => $capsule)
                <button @click="current = {{ $index }}" 
                        class="h-1.5 rounded-full transition-opacity duration-150"
                        :class="current === {{ $index }} ? 'w-4 bg-blue-500' : 'w-1.5 bg-blue-200 dark:bg-blue-800'">
                </button>
            @endforeach
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Formulario --}}
        <div class="lg:col-span-2">
            <flux:card>
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
                            <flux:input wire:model.live.debounce.500ms="form.phone_number" icon="phone" placeholder="Ej. 22334455" />
                            <flux:error name="form.phone_number" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Identificación (Cédula o Pasaporte)</flux:label>
                            <flux:input 
                                wire:model.live.debounce.500ms="form.citizen_identifier" 
                                icon="identification" 
                                placeholder="Ej. 8-725-927 o PA123456" 
                                helper="Se aceptan guiones y caracteres alfanuméricos."
                            >
                                <x-slot name="append">
                                    <div wire:loading wire:target="form.citizen_identifier">
                                        <flux:icon icon="arrow-path" class="animate-spin text-zinc-400" size="sm" />
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

                    <div class="flex justify-end gap-3 pt-4 border-t dark:border-zinc-800">
                        <flux:button type="submit" variant="primary" icon="check">Guardar Registro</flux:button>
                    </div>
                </form>
            </flux:card>
        </div>

        {{-- Información Lateral / Ayuda --}}
        <div class="space-y-4">
            <flux:card class="bg-primary-50/50 dark:bg-primary-900/10 border-primary-200 dark:border-primary-800/50">
                <flux:heading size="sm" class="mb-2">Información del Sistema</flux:heading>
                <flux:text size="sm" class="mb-4">
                    Estás registrando una llamada de forma manual. Asegúrate de tipificar correctamente para mantener la calidad de los reportes.
                </flux:text>
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs">
                        <flux:icon icon="clock" size="sm" class="text-zinc-400" />
                        <span>Hora Actual: {{ now()->format('H:i') }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <flux:icon icon="user" size="sm" class="text-zinc-400" />
                        <span>Agente: {{ auth()->user()->name }}</span>
                    </div>
                </div>
            </flux:card>

            {{-- Script de Llamada --}}
            @if(count($callScript) > 0)
            <flux:card class="bg-zinc-50 dark:bg-zinc-900/50 border-zinc-200 dark:border-zinc-800">
                <div class="flex items-center gap-2 mb-4">
                    <flux:icon icon="clipboard-document-list" size="sm" class="text-zinc-500" />
                    <flux:heading size="sm">Script de Atención</flux:heading>
                </div>
                
                <div class="space-y-3" x-data="{ checked: [] }">
                    @foreach($callScript as $index => $step)
                        <div class="flex items-start gap-3 group">
                            <div class="pt-0.5">
                                <flux:checkbox wire:key="script-step-{{ $index }}" x-model="checked" value="{{ $index }}" id="step-{{ $index }}" />
                            </div>
                            <label for="step-{{ $index }}" 
                                   class="text-sm cursor-pointer select-none transition-opacity duration-200" 
                                   :class="checked.includes('{{ $index }}') ? 'line-through text-zinc-400 dark:text-zinc-500' : 'text-zinc-700 dark:text-zinc-300 group-hover:text-zinc-900 dark:group-hover:text-zinc-100'">
                                {{ $step }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </flux:card>
            @endif

            {{-- Validación de Derechos --}}
            @if($citizenData)
                <flux:card class="overflow-hidden p-0 border-{{ ($citizenData['tiene_derecho'] ?? 'false') === 'true' ? 'green' : 'red' }}-200 dark:border-{{ ($citizenData['tiene_derecho'] ?? 'false') === 'true' ? 'green' : 'red' }}-800/50">
                    <div class="p-4 bg-{{ ($citizenData['tiene_derecho'] ?? 'false') === 'true' ? 'green' : 'red' }}-50/50 dark:bg-{{ ($citizenData['tiene_derecho'] ?? 'false') === 'true' ? 'green' : 'red' }}-900/10 border-b dark:border-zinc-800">
                        <div class="flex items-center justify-between">
                            <flux:heading size="sm">Validación de Derechos</flux:heading>
                            <flux:badge size="sm" :color="($citizenData['tiene_derecho'] ?? 'false') === 'true' ? 'green' : 'red'">
                                {{ ($citizenData['tiene_derecho'] ?? 'false') === 'true' ? 'CON DERECHO' : 'SIN DERECHO' }}
                            </flux:badge>
                        </div>
                    </div>
                    
                    <div class="p-4 space-y-4">
                        {{-- Asegurado --}}
                        @if(isset($citizenData['asegurado']))
                            <div class="space-y-1">
                                <flux:text size="xs" class="uppercase font-bold text-zinc-400">Asegurado</flux:text>
                                <flux:text size="sm" class="font-medium">{{ $citizenData['asegurado']['nombre'] ?? 'N/A' }}</flux:text>
                                <div class="flex justify-between text-xs text-zinc-500">
                                    <span>Cuota: {{ $citizenData['asegurado']['cuota_pagada'] ?? 'N/A' }}</span>
                                    <span>Vence: {{ $citizenData['asegurado']['valido_hasta'] ?? 'N/A' }}</span>
                                </div>
                            </div>
                        @endif

                        {{-- Beneficiario (Solo si existe y es el que se está validando) --}}
                        @if(isset($citizenData['beneficiario']) && ($citizenData['beneficiario']['documento'] ?? '') === $form->citizen_identifier)
                            <div class="pt-3 border-t dark:border-zinc-800 space-y-1">
                                <flux:text size="xs" class="uppercase font-bold text-zinc-400">Beneficiario</flux:text>
                                <flux:text size="sm" class="font-medium text-blue-600 dark:text-blue-400">{{ $citizenData['beneficiario']['nombre'] ?? 'N/A' }}</flux:text>
                                <flux:text size="xs" class="text-zinc-500">{{ $citizenData['beneficiario']['tipo'] ?? 'N/A' }}</flux:text>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @elseif($isValidating)
                <flux:card class="animate-pulse py-8 text-center space-y-2">
                    <flux:icon icon="arrow-path" class="animate-spin text-zinc-400 mx-auto" />
                    <flux:text size="xs">Validando derechos en tiempo real...</flux:text>
                </flux:card>
            @endif
        </div>
    </div>

    {{-- Historial Reciente --}}
    <div class="space-y-4">
        <flux:heading size="lg">Historial Reciente del Ciudadano</flux:heading>
        <flux:subheading>Últimas 10 interacciones asociadas al número telefónico ingresado.</flux:subheading>
        
        <flux:card class="p-0 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b bg-zinc-50 dark:bg-zinc-900/50">
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Fecha / Hora</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Teléfono</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Cola</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Tipificación</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500 text-right">Estado</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-zinc-800">
                    @forelse($history as $record)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-opacity">
                            <td class="p-4 text-xs">
                                <div class="flex flex-col">
                                    <span class="font-bold">{{ $record->ivr_started_at?->format('d/m/Y') ?? 'Hoy' }}</span>
                                    <span class="text-zinc-500">{{ $record->ivr_started_at?->format('H:i:s') ?? now()->format('H:i:s') }}</span>
                                </div>
                            </td>
                            <td class="p-4 text-sm font-medium">{{ $record->phone_number }}</td>
                            <td class="p-4 text-xs">
                                <flux:badge size="sm" color="zinc">{{ $record->queue?->name ?? 'N/A' }}</flux:badge>
                            </td>
                            <td class="p-4 text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                {{ $record->caseSubtype?->name ?? 'Sin tipificar' }}
                            </td>
                            <td class="p-4 text-right">
                                @php
                                    $statusColor = match($record->status) {
                                        'open' => 'blue',
                                        'closed' => 'green',
                                        'pending_operator' => 'amber',
                                        default => 'zinc'
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$statusColor">{{ $record->status }}</flux:badge>
                            </td>
                            <td class="p-4 text-right">
                                <flux:button wire:click="showRecordDetails({{ $record->id }})" variant="subtle" size="sm" icon="eye" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-zinc-500 italic">
                                No has realizado registros recientemente.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </flux:card>
    </div>

    {{-- Modal de Detalles --}}
    <flux:modal name="record-details" class="min-w-[500px]">
        @if($selectedRecord)
            <div class="space-y-4">
                <div>
                    <flux:heading size="lg">Detalles del Registro #{{ $selectedRecord->id }}</flux:heading>
                    <flux:subheading>Información completa de la interacción seleccionada.</flux:subheading>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <flux:text size="xs" class="uppercase font-bold text-zinc-400">Canal / Cola</flux:text>
                        <flux:text size="sm">{{ $selectedRecord->queue?->channel?->name ?? 'N/A' }} - {{ $selectedRecord->queue?->name ?? 'N/A' }}</flux:text>
                    </div>
                    <div class="space-y-1">
                        <flux:text size="xs" class="uppercase font-bold text-zinc-400">Estado / Fecha</flux:text>
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" color="zinc">{{ $selectedRecord->status }}</flux:badge>
                            <flux:text size="sm">{{ $selectedRecord->ivr_started_at?->format('d/m/Y H:i') }}</flux:text>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <flux:text size="xs" class="uppercase font-bold text-zinc-400">Agente</flux:text>
                        <flux:text size="sm">{{ $selectedRecord->employee?->full_name ?? $selectedRecord->raw_agent_name ?? 'N/A' }}</flux:text>
                    </div>
                    <div class="space-y-1">
                        <flux:text size="xs" class="uppercase font-bold text-zinc-400">Tipificación</flux:text>
                        <flux:text size="sm">{{ $selectedRecord->caseSubtype?->name ?? 'Sin tipificar' }}</flux:text>
                    </div>
                </div>

                <div class="space-y-1">
                    <flux:text size="xs" class="uppercase font-bold text-zinc-400">Descripción / Observaciones</flux:text>
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-md border dark:border-zinc-800 text-sm italic">
                        {{ $selectedRecord->description ?: 'No se ingresaron detalles adicionales.' }}
                    </div>
                </div>

                <div class="border-t dark:border-zinc-800 pt-4">
                    <flux:heading size="sm" class="mb-3">Tiempos de Interacción (Segundos)</flux:heading>
                    <div class="grid grid-cols-4 gap-2">
                        <div class="text-center p-2 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-100 dark:border-blue-800/50">
                            <flux:text size="xs" color="blue">Habla</flux:text>
                            <div class="font-bold text-blue-700 dark:text-blue-400">{{ $selectedRecord->talk_time ?? 0 }}s</div>
                        </div>
                        <div class="text-center p-2 bg-amber-50 dark:bg-amber-900/20 rounded border border-amber-100 dark:border-amber-800/50">
                            <flux:text size="xs" color="amber">Espera</flux:text>
                            <div class="font-bold text-amber-700 dark:text-amber-400">{{ $selectedRecord->ring_time ?? 0 }}s</div>
                        </div>
                        <div class="text-center p-2 bg-green-50 dark:bg-green-900/20 rounded border border-green-100 dark:border-green-800/50">
                            <flux:text size="xs" color="green">Trabajo</flux:text>
                            <div class="font-bold text-green-700 dark:text-green-400">{{ $selectedRecord->work_time ?? 0 }}s</div>
                        </div>
                        <div class="text-center p-2 bg-zinc-50 dark:bg-zinc-900 rounded border border-zinc-100 dark:border-zinc-800">
                            <flux:text size="xs">Cola</flux:text>
                            <div class="font-bold">{{ $selectedRecord->queue_time ?? 0 }}s</div>
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
