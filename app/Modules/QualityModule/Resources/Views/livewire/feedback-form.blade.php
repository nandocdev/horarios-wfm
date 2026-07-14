<div class="space-y-8">
    <div>
        <flux:heading size="xl" level="1">Agregar Feedback</flux:heading>
        <flux:subheading>Evaluación: {{ $evaluation->queue?->code }} — {{ $evaluation->dteval?->format('d/m/Y') }}</flux:subheading>
    </div>

    <flux:card>
        <div class="space-y-6">
            <flux:textarea wire:model="obsfeed" label="Observaciones *" placeholder="Escriba su retroalimentación..." maxlength="2500" rows="5" />

            <div class="flex justify-end gap-2">
                <flux:button href="{{ route('quality.evaluations.show', $evaluation->id) }}" variant="subtle">Cancelar</flux:button>
                <flux:button wire:click="submit" variant="primary" icon="check">Guardar Feedback</flux:button>
            </div>
        </div>
    </flux:card>
</div>
