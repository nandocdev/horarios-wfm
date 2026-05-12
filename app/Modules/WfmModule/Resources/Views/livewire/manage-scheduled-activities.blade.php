<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Definiciones de Actividades Programadas</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Nueva Definición</flux:button>
    </div>

    <flux:table :paginate="$definitions">
        <flux:table.columns>
            <flux:table.column>Nombre</flux:table.column>
            <flux:table.column>Tipo Base</flux:table.column>
            <flux:table.column>Detalles</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($definitions as $def)
                <flux:table.row :key="$def->id">
                    <flux:table.cell class="font-medium">{{ $def->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$def->activityType->color ?? 'zinc'">{{ $def->activityType->name }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="text-xs text-zinc-500">
                            Duración: {{ $def->default_duration_minutes ?? 'N/A' }} min<br>
                            Lugar: {{ $def->default_location ?? 'No especificado' }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$def->is_active ? 'green' : 'red'" >
                            {{ $def->is_active ? 'Activo' : 'Inactivo' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button wire:click="edit({{ $def->id }})" variant="ghost" size="sm" icon="pencil" />
                        <flux:button wire:click="delete({{ $def->id }})" variant="ghost" size="sm" icon="trash" color="red" wire:confirm="¿Estás seguro?" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $form->definition ? 'Editar Definición' : 'Nueva Definición' }}</flux:heading>

            <flux:input wire:model="form.name" label="Nombre de la Actividad" placeholder="Ej. Feedback Mensual de Calidad" />

            <flux:select wire:model="form.activity_type_id" label="Tipo de Actividad Base" placeholder="Seleccione un tipo...">
                @foreach ($activityTypes as $type)
                    <flux:select.option :value="$type->id">{{ $type->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="form.default_duration_minutes" type="number" label="Duración Predeterminada (Min)" />
                <flux:input wire:model="form.default_location" label="Ubicación Predeterminada" />
            </div>

            <flux:input wire:model="form.default_instructor" label="Instructor/Expositor Predeterminado" />

            <flux:checkbox wire:model="form.is_active" label="Definición activa y disponible para planificación" />

            <div class="flex justify-end gap-3 mt-6">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
