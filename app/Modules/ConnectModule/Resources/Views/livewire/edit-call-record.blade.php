<div class="space-y-8">
    <flux:card>
        <flux:heading size="lg">Completar registro de llamada</flux:heading>
    </flux:card>

    @if (session()->has('success'))
        <flux:card color="green" class="border-green-200 bg-green-50 text-green-600">
            <flux:text>{{ session('success') }}</flux:text>
        </flux:card>
    @endif

    <flux:card class="max-w-4xl">
        <form wire:submit.prevent="save" class="grid gap-4 md:grid-cols-2">
            <flux:select wire:model="form.channel_id" label="Canal">
                <flux:select.option value="">Seleccionar</flux:select.option>
                @foreach($channels as $channel)
                    <flux:select.option value="{{ $channel->id }}">{{ $channel->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="form.channel_id" />

            <flux:select wire:model="form.queue_id" label="Cola">
                <flux:select.option value="">Seleccionar</flux:select.option>
                @foreach($queues as $queue)
                    <flux:select.option value="{{ $queue->id }}">{{ $queue->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="form.queue_id" />

            <flux:input wire:model.defer="form.citizen_identifier" label="Identificación (Cédula o Pasaporte)"
                helper="Se aceptan guiones y caracteres alfanuméricos." placeholder="Ej. 8-725-927 o PA123456" />
            <flux:error name="form.citizen_identifier" />

            <flux:select wire:model="form.case_subtype_id" label="Tipo de consulta">
                <flux:select.option value="">Seleccionar</flux:select.option>
                @foreach($subtypes as $subtype)
                    <flux:select.option value="{{ $subtype->id }}">{{ $subtype->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="form.case_subtype_id" />

            <flux:textarea wire:model.defer="form.description" label="Descripción" rows="4" />
            <flux:error name="form.description" />

            <div class="md:col-span-2 flex justify-end">
                <flux:button type="submit" variant="primary">Guardar</flux:button>
                <flux:button href="{{ route('contact-center.calls.index') }}" variant="ghost" class="ml-3"
                    wire:navigate>Volver</flux:button>
            </div>
        </form>
    </flux:card>
</div>
