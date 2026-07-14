<div class="space-y-8">
    <div>
        <flux:heading size="xl" level="1">{{ $isEditing ? 'Editar Criterio' : 'Nuevo Criterio' }}</flux:heading>
        <flux:subheading>{{ $isEditing ? 'Se creará una nueva versión del criterio' : 'Registre un nuevo criterio de evaluación' }}</flux:subheading>
    </div>

    <flux:card>
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="code" label="Código *" placeholder="SALUDO" maxlength="50" :readonly="$isEditing" />
                <flux:input wire:model="puntaje" label="Puntaje máximo *" type="number" min="1" max="100" />
            </div>

            <flux:textarea wire:model="criterio_text" label="Texto del Criterio *" placeholder="Descripción del criterio a evaluar..." maxlength="500" rows="3" />

            <flux:textarea wire:model="descripcion" label="Descripción (opcional)" placeholder="Instrucciones o ejemplos para el evaluador..." maxlength="1000" rows="2" />

            <div class="flex justify-end gap-2">
                <flux:button href="{{ route('quality.criteria.index') }}" variant="subtle">Cancelar</flux:button>
                <flux:button wire:click="submit" variant="primary" icon="check">
                    {{ $isEditing ? 'Guardar Versión' : 'Crear Criterio' }}
                </flux:button>
            </div>
        </div>
    </flux:card>
</div>
