<div class="space-y-6">
    <x-wfm.page-header title="Completar Registro de Llamada #{{ $callRecord->id }}">
        <x-slot:actions>
            <flux:button href="{{ route('contact-center.calls.index') }}" variant="ghost" wire:navigate>Volver</flux:button>
        </x-slot:actions>
    </x-wfm.page-header>

    @if (session()->has('success'))
        <div class="rounded-md bg-wfm-success/10 border border-wfm-success/20 px-3 py-2 text-xs text-wfm-success">
            {{ session('success') }}
        </div>
    @endif

    <x-wfm.section>
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

            <flux:input wire:model.defer="form.citizen_identifier" label="Identificación" placeholder="Ej. 8-725-927" />
            <flux:error name="form.citizen_identifier" />

            <flux:select wire:model="form.case_subtype_id" label="Tipo de consulta">
                <flux:select.option value="">Seleccionar</flux:select.option>
                @foreach($subtypes as $subtype)
                    <flux:select.option value="{{ $subtype->id }}">{{ $subtype->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="form.case_subtype_id" />

            <flux:textarea wire:model.defer="form.description" label="Descripción" rows="4" class="md:col-span-2" />
            <flux:error name="form.description" />

            <div class="md:col-span-2 flex justify-end gap-2">
                <flux:button href="{{ route('contact-center.calls.index') }}" variant="ghost" wire:navigate>Volver</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </x-wfm.section>
</div>
