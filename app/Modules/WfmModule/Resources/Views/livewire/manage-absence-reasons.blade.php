<div class="p-4">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Motivos de Ausencia / Justificación</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Nuevo Motivo</flux:button>
    </div>

    <flux:table :paginate="$reasons">
        <flux:table.columns>
            <flux:table.column>Código</flux:table.column>
            <flux:table.column>Nombre</flux:table.column>
            <flux:table.column>Adjunto Obligatorio</flux:table.column>
            <flux:table.column>Justificada</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($reasons as $reason)
                <flux:table.row :key="$reason->id">
                    <flux:table.cell class="font-mono text-xs">{{ $reason->short_code }}</flux:table.cell>
                    <flux:table.cell>{{ $reason->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$reason->requires_attachment ? 'blue' : 'zinc'">{{ $reason->requires_attachment ? 'Si' : 'No' }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$reason->is_excused ? 'green' : 'red'">{{ $reason->is_excused ? 'Si' : 'No' }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button wire:click="edit({{ $reason->id }})" variant="ghost" size="sm" icon="pencil" />
                        <flux:button wire:click="delete({{ $reason->id }})" variant="ghost" size="sm" icon="trash" color="red" wire:confirm="¿Estás seguro?" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model="showModal" class="w-full max-w-sm">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $form->absenceReasonCode ? 'Editar Motivo' : 'Nuevo Motivo' }}</flux:heading>

            <flux:input wire:model="form.short_code" label="Código Corto" placeholder="Ej. MED" />
            <flux:input wire:model="form.name" label="Descripción" placeholder="Ej. Cita Médica" />

            <div class="space-y-3">
                <flux:checkbox wire:model="form.requires_attachment" label="¿Requiere adjunto/comprobante?" />
                <flux:checkbox wire:model="form.is_excused" label="¿Se considera falta justificada?" />
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
