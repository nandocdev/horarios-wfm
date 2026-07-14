<div class="space-y-8">
    <div>
        <flux:heading size="xl" level="1">Nueva Evaluación</flux:heading>
        <flux:subheading>Registre una evaluación de calidad para una llamada</flux:subheading>
    </div>

    <flux:card>
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <flux:select wire:model.live="form.queue_id" label="Cola *" placeholder="Seleccione una cola">
                    @foreach($queues as $queue)
                        <option value="{{ $queue->id }}">{{ $queue->code }} — {{ $queue->name }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="form.employee_id" label="ID Empleado *" type="number" />
                <flux:input wire:model="form.dtcall" label="Fecha llamada" type="date" />
                <flux:input wire:model="form.tmcall" label="Hora llamada" type="time" />
            </div>

            <flux:separator text="Criterios de Evaluación" />

            @if($criterios->isEmpty())
                <flux:text class="text-slate-400 text-center py-8">Seleccione una cola para cargar los criterios</flux:text>
            @else
                <div class="space-y-3">
                    @foreach($criterios as $index => $criterio)
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-900 rounded-md">
                            <div class="flex-1">
                                <flux:text size="sm" class="font-medium">{{ $criterio->criterio_text }}</flux:text>
                                <flux:text size="xs" class="text-slate-400">Puntaje máximo: {{ $criterio->puntaje }}</flux:text>
                            </div>
                            <div class="flex items-center gap-3">
                                <flux:input wire:model="form.scores.{{ $index }}.puntaje" type="number" min="0" max="{{ $criterio->puntaje }}" class="w-20 text-center" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <flux:separator text="Observaciones" />

            <flux:textarea wire:model="form.callobs" label="Observaciones" placeholder="Comentarios sobre la llamada..." maxlength="2500" rows="3" />

            <div class="flex justify-end gap-2 pt-4">
                <flux:button href="{{ route('quality.evaluations.index') }}" variant="subtle">Cancelar</flux:button>
                <flux:button wire:click="submit" variant="primary" icon="check">Guardar Evaluación</flux:button>
            </div>
        </div>
    </flux:card>
</div>
