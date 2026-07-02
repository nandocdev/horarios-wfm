<div class="space-y-8 max-w-5xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Nuevo Registro de Llamada</flux:heading>
            <flux:subheading>Ingresa los detalles de la interacción.</flux:subheading>
        </div>
        <flux:button href="{{ route('connect.call-records') }}" wire:navigate variant="subtle" icon="arrow-left">
            Volver al Listado
        </flux:button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <flux:card>
                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <flux:field>
                            <flux:label>Canal</flux:label>
                            <flux:select wire:model.live="channel_id" placeholder="Selecciona canal...">
                                @foreach($channels as $channel)
                                    <flux:select.option value="{{ $channel->id }}">{{ $channel->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="channel_id" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Cola / Servicio</flux:label>
                            <flux:select wire:model.live="queue_id" placeholder="Selecciona cola...">
                                @foreach($queues as $queue)
                                    <flux:select.option value="{{ $queue->id }}">{{ $queue->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="queue_id" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <flux:field>
                            <flux:label>Número de Teléfono</flux:label>
                            <flux:input wire:model="phone_number" icon="phone" placeholder="Ej. 22334455" />
                            <flux:error name="phone_number" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Identificación</flux:label>
                            <flux:input wire:model="citizen_identifier" icon="identification"
                                placeholder="Ej. 8-725-927" />
                            <flux:error name="citizen_identifier" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Tipificación</flux:label>
                        <flux:select wire:model="case_subtype_id" placeholder="Selecciona el motivo...">
                            <flux:select.option value="0">-- Selecciona un subtipo --</flux:select.option>
                            @foreach($subtypes as $subtype)
                                <flux:select.option value="{{ $subtype->id }}">{{ $subtype->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="case_subtype_id" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Observaciones</flux:label>
                        <flux:textarea wire:model="description" rows="4"
                            placeholder="Describe brevemente la solicitud..." />
                        <flux:error name="description" />
                    </flux:field>

                    <div class="flex justify-end gap-3 pt-4 border-t dark:border-zinc-800">
                        <flux:button type="submit" variant="primary" icon="check">Guardar Registro</flux:button>
                    </div>
                </form>
            </flux:card>
        </div>

        <div class="space-y-6">
            <flux:card class="bg-primary-50/50 dark:bg-primary-900/10 border-primary-200 dark:border-primary-800/50">
                <flux:heading size="sm" class="mb-2">Información del Sistema</flux:heading>
                <flux:text size="sm" class="mb-4">
                    Estás registrando una llamada de forma manual.
                </flux:text>
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs">
                        <flux:icon.clock size="sm" class="text-zinc-400" />
                        <span>Hora Actual: {{ now()->format('H:i') }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <flux:icon.user size="sm" class="text-zinc-400" />
                        <span>Agente: {{ auth()->user()->name }}</span>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</div>
