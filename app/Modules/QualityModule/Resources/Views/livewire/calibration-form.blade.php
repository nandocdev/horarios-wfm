<div class="space-y-8">
    <div>
        <flux:heading size="xl" level="1">Calibrar Evaluación</flux:heading>
        <flux:subheading>Evaluación: {{ $evaluation->queue?->code }} — Score actual: {{ $evaluation->score ?? '—' }}</flux:subheading>
    </div>

    <flux:card>
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <flux:text size="xs" class="text-slate-500 uppercase font-bold">Score Anterior</flux:text>
                    <flux:heading size="xl" class="mt-1">{{ $evaluation->score ?? '—' }}</flux:heading>
                </div>
                <flux:input wire:model="score_nuevo" label="Nuevo Score *" type="number" min="0" max="100" />
            </div>

            <flux:textarea wire:model="obs" label="Observación" placeholder="Motivo de la calibración..." maxlength="2500" rows="3" />

            <div class="flex justify-end gap-2">
                <flux:button href="{{ route('quality.evaluations.show', $evaluation->id) }}" variant="subtle">Cancelar</flux:button>
                <flux:button wire:click="submit" variant="warning" icon="adjustments">Registrar Calibración</flux:button>
            </div>
        </div>
    </flux:card>
</div>
