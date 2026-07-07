<div class="p-4 max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Tipos de Actividad</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Nuevo Tipo</flux:button>
    </div>

    <flux:table :paginate="$activityTypes">
        <flux:table.columns class="sticky top-0 z-10 bg-white">
            <flux:table.column>Nombre</flux:table.column>
            <flux:table.column>Color</flux:table.column>
            <flux:table.column>Indicadores</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($activityTypes as $type)
                <flux:table.row :key="$type->id" class="hover:bg-slate-50">
                    <flux:table.cell class="py-2">{{ $type->name }}</flux:table.cell>
                    <flux:table.cell class="py-2">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded" style="background-color: {{ $type->color }}"></div>
                            <span class="text-xs">{{ $type->color }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="py-2">
                        <div class="flex gap-2">
                            <flux:badge :color="$type->is_productive ? 'blue' : 'zinc'">{{ $type->is_productive ? 'Productivo' : 'No Prod.' }}</flux:badge>
                            <flux:badge :color="$type->is_paid ? 'green' : 'red'">{{ $type->is_paid ? 'Pagado' : 'No Pagado' }}</flux:badge>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell align="end" class="py-2">
                        <flux:button wire:click="edit({{ $type->id }})" variant="ghost" size="sm" icon="pencil" />
                        <flux:button wire:click="delete({{ $type->id }})" variant="ghost" size="sm" icon="trash" color="red" wire:confirm="¿Estás seguro?" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model="showModal" class="w-full max-w-sm">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $form->activityType ? 'Editar Tipo' : 'Nuevo Tipo' }}</flux:heading>

            <flux:input wire:model="form.name" label="Nombre" placeholder="Ej. Reunión de Equipo" />
            
            <flux:input wire:model="form.color" type="color" label="Color de Identificación" />

            <div class="space-y-3">
                <flux:checkbox wire:model="form.is_productive" label="¿Es tiempo productivo?" />
                <flux:checkbox wire:model="form.is_paid" label="¿Es tiempo pagado?" />
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
