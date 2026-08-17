<div class="space-y-6">
    <x-wfm.page-header title="Calibrar Evaluación" :description="'Evaluación: ' . ($evaluation->queue?->code ?? '') . ' — Score actual: ' . ($evaluation->score ?? '—')">
        <x-slot:actions>
            <flux:button href="{{ route('quality.evaluations.show', $evaluation->id) }}" variant="ghost" icon="arrow-left" wire:navigate>Volver</flux:button>
        </x-slot:actions>
    </x-wfm.page-header>

    <x-wfm.section>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <p class="kpi-label">Score Anterior</p>
                <p class="text-2xl font-bold text-wfm-navy-800">{{ $evaluation->score ?? '—' }}</p>
            </div>
            <flux:input wire:model="form.score_nuevo" label="Nuevo Score *" type="number" min="0" max="100" />
        </div>

        <flux:textarea wire:model="form.obs" label="Observación" placeholder="Motivo de la calibración..." maxlength="2500" rows="3" />

        <div class="flex justify-end gap-2 mt-4">
            <flux:button href="{{ route('quality.evaluations.show', $evaluation->id) }}" variant="ghost" wire:navigate>Cancelar</flux:button>
            <flux:button wire:click="submit" variant="warning" icon="scale">Registrar Calibración</flux:button>
        </div>
    </x-wfm.section>
</div>
