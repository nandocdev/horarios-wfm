<div class="space-y-6">
    <x-wfm.page-header title="Agregar Feedback" :description="'Evaluación: ' . ($evaluation->queue?->code ?? '') . ' — ' . ($evaluation->dteval?->format('d/m/Y') ?? '')">
        <x-slot:actions>
            <flux:button href="{{ route('quality.evaluations.show', $evaluation->id) }}" variant="ghost" icon="arrow-left" wire:navigate>Volver</flux:button>
        </x-slot:actions>
    </x-wfm.page-header>

    <x-wfm.section>
        <flux:textarea wire:model="form.obsfeed" label="Observaciones *" placeholder="Escriba su retroalimentación..." maxlength="2500" rows="5" />

        <div class="flex justify-end gap-2 mt-4">
            <flux:button href="{{ route('quality.evaluations.show', $evaluation->id) }}" variant="ghost" wire:navigate>Cancelar</flux:button>
            <flux:button wire:click="submit" variant="primary" icon="check">Guardar Feedback</flux:button>
        </div>
    </x-wfm.section>
</div>
