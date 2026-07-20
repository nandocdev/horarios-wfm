<div class="space-y-6">
    <x-wfm.page-header :title="$isEditing ? 'Editar Criterio' : 'Nuevo Criterio'" :description="$isEditing ? 'Se creará una nueva versión del criterio' : 'Registre un nuevo criterio de evaluación'" />

    <x-wfm.section>
        <form wire:submit="submit" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="form.name" label="Código *" placeholder="SALUDO" maxlength="50" :readonly="$isEditing" />
                <flux:input wire:model="form.max_score" label="Puntaje máximo *" type="number" min="1" max="100" />
            </div>

            <flux:textarea wire:model="form.name" label="Texto del Criterio *" placeholder="Nombre del criterio..." maxlength="500" rows="3" />
            <flux:error name="form.name" />

            <flux:textarea wire:model="form.description" label="Descripción (opcional)" placeholder="Instrucciones o ejemplos para el evaluador..." maxlength="1000" rows="2" />
            <flux:error name="form.description" />

            <div class="flex justify-end gap-2">
                <flux:button href="{{ route('quality.criteria.index') }}" variant="ghost" wire:navigate>Cancelar</flux:button>
                <flux:button type="submit" variant="primary" icon="check">
                    {{ $isEditing ? 'Guardar Versión' : 'Crear Criterio' }}
                </flux:button>
            </div>
        </form>
    </x-wfm.section>
</div>
