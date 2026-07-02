<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Editar Registro de Llamada #{{ $callRecord->id }}</flux:heading>
            <flux:subheading>Actualiza los metadatos del registro.</flux:subheading>
        </div>
        <flux:button href="{{ route('connect.call-records') }}" wire:navigate variant="subtle" icon="arrow-left">
            Volver al Listado
        </flux:button>
    </div>

    <flux:card>
        <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
            <flux:select wire:model="queue_id" label="Cola">
                <flux:select.option value="">Seleccionar</flux:select.option>
                @foreach($queues as $queue)
                    <flux:select.option value="{{ $queue->id }}">{{ $queue->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="queue_id" />

            <flux:input wire:model="citizen_identifier" label="Identificación"
                helper="Cédula o pasaporte del ciudadano." placeholder="Ej. 8-725-927" />
            <flux:error name="citizen_identifier" />

            <flux:select wire:model="case_subtype_id" label="Tipo de consulta">
                <flux:select.option value="">Seleccionar</flux:select.option>
                @foreach($subtypes as $subtype)
                    <flux:select.option value="{{ $subtype->id }}">{{ $subtype->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="case_subtype_id" />

            <flux:textarea wire:model="description" label="Descripción" rows="4" />
            <flux:error name="description" />

            <div class="md:col-span-2 flex justify-end gap-3">
                <flux:button type="submit" variant="primary">Guardar</flux:button>
                <flux:button href="{{ route('connect.call-records') }}" variant="ghost" wire:navigate>
                    Cancelar
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
