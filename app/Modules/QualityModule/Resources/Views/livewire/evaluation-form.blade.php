<div class="space-y-8">
    <div data-tour="quality-form-header" class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Nueva Evaluación</flux:heading>
            <flux:subheading>Registre una evaluación de calidad para una llamada</flux:subheading>
        </div>
        <x-wfm.tour-button :tour="'quality.evaluation-form'" />
    </div>

    <flux:card>
        <div class="space-y-6">
            <div data-tour="quality-form-context" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <flux:select wire:model.live="form.queue_id" label="Cola *" placeholder="Seleccione una cola">
                    @foreach($queues as $queue)
                        <option value="{{ $queue->id }}">{{ $queue->code }} — {{ $queue->name }}</option>
                    @endforeach
                </flux:select>
                <flux:input value="{{ $employee->username }} — {{ $employee->full_name }}" label="Empleado *" disabled />
                <flux:input wire:model="form.dtcall" label="Fecha llamada" type="date" />
                <flux:input wire:model="form.tmcall" label="Hora llamada" type="time" />
            </div>

            <flux:separator text="Criterios de Evaluación" />

            @if($criterios->isEmpty())
                <flux:text class="text-slate-400 text-center py-8">Seleccione una cola para cargar los criterios</flux:text>
            @else
                <div data-tour="quality-form-criteria" class="space-y-3" x-data="{ showDesc: null }">
                    @foreach($criterios as $index => $criterio)
                        <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-md">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <flux:text size="sm" class="font-medium">{{ $criterio->criterio_text }}</flux:text>
                                        @if($criterio->descripcion)
                                            <button type="button" @click="showDesc = showDesc === {{ $index }} ? null : {{ $index }}" class="text-slate-400 hover:text-slate-600 transition-colors">
                                                <flux:icon name="information-circle" size="sm" />
                                            </button>
                                        @endif
                                    </div>
                                    <flux:text size="xs" class="text-slate-400">{{ $criterio->puntaje }} pts</flux:text>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <flux:text size="xs" class="text-slate-400">No cumple</flux:text>
                                        <flux:switch wire:model.live="form.scores.{{ $index }}.cumple" />
                                        <flux:text size="xs" class="text-slate-400">Cumple</flux:text>
                                    </div>
                                </div>
                            </div>
                            @if($criterio->descripcion)
                                <div x-show="showDesc === {{ $index }}" x-cloak class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                                    <flux:text size="xs" class="text-slate-500 leading-relaxed whitespace-pre-line">{{ $criterio->descripcion }}</flux:text>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if($redFlagCriteria->isNotEmpty())
                <flux:separator text="Red Flags" />

                <div data-tour="quality-form-redflags" class="space-y-2">
                    <flux:text size="sm" class="text-slate-500 mb-2">
                        Seleccione las incidencias graves aplicables (restan puntos automáticamente)
                    </flux:text>
                    @foreach($redFlagCriteria as $rf)
                        <label class="flex items-start gap-3 p-2 rounded-md hover:bg-red-50 dark:hover:bg-red-950 cursor-pointer">
                            <flux:checkbox wire:model="form.red_flags.{{ $rf->id }}" />
                            <div class="flex-1">
                                <flux:text size="sm">{{ $rf->criterio_text }}</flux:text>
                                <flux:text size="xs" class="text-red-500">-{{ $rf->perdida }} puntos</flux:text>
                            </div>
                        </label>
                    @endforeach
                </div>
            @endif

            <flux:separator text="Observaciones" />

            <div data-tour="quality-form-obs">
                <flux:textarea wire:model="form.callobs" label="Observaciones" placeholder="Comentarios sobre la llamada..." maxlength="2500" rows="3" />
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <flux:button href="{{ route('quality.evaluations.index') }}" variant="subtle">Cancelar</flux:button>
                <flux:button wire:click="submit" variant="primary" icon="check">Guardar Evaluación</flux:button>
            </div>
        </div>
    </flux:card>
</div>
