<div class="p-4 space-y-8">

    {{-- CABECERA --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">Catálogo de Actividades</flux:heading>
            <flux:subheading class="mt-1">
                Define las actividades intradía disponibles para planificación. Solo WFM puede crear o editar.
            </flux:subheading>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">Nueva Definición</flux:button>
    </div>

    {{-- TABLA --}}
    <flux:table :paginate="$definitions">
        <flux:table.columns class="sticky top-0 z-10 bg-white">
            <flux:table.column>Actividad</flux:table.column>
            <flux:table.column>Tipo Base</flux:table.column>
            <flux:table.column>Duración</flux:table.column>
            <flux:table.column>Ubicación / Instructor</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($definitions as $def)
                <flux:table.row :key="$def->id" class="hover:bg-slate-50">

                    {{-- Nombre --}}
                    <flux:table.cell class="py-1">
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded-md flex items-center justify-center shrink-0"
                                 style="background-color: {{ $def->activityType?->color ?? '#6366f1' }}20">
                                <flux:icon name="calendar" class="w-4 h-4"
                                    style="color: {{ $def->activityType?->color ?? '#6366f1' }}" />
                            </div>
                            <p class="font-medium text-sm">{{ $def->name }}</p>
                        </div>
                    </flux:table.cell>

                    {{-- Tipo --}}
                    <flux:table.cell class="py-1">
                        <flux:badge size="sm" :color="$def->activityType?->color ?? 'slate'">
                            {{ $def->activityType?->name ?? '—' }}
                        </flux:badge>
                    </flux:table.cell>

                    {{-- Duración --}}
                    <flux:table.cell class="py-1">
                        @if($def->default_duration_minutes)
                            <span class="font-mono text-sm">{{ $def->default_duration_minutes }} min</span>
                        @else
                            <span class="text-slate-400 text-sm">N/A</span>
                        @endif
                    </flux:table.cell>

                    {{-- Ubicación / Instructor --}}
                    <flux:table.cell class="py-1">
                        <div class="text-xs text-slate-500 space-y-0.5">
                            @if($def->default_location)
                                <div class="flex items-center gap-1">
                                    <flux:icon name="map-pin" class="w-3 h-3 shrink-0" />
                                    {{ $def->default_location }}
                                </div>
                            @endif
                            @if($def->default_instructor)
                                <div class="flex items-center gap-1">
                                    <flux:icon name="academic-cap" class="w-3 h-3 shrink-0" />
                                    {{ $def->default_instructor }}
                                </div>
                            @endif
                            @if(!$def->default_location && !$def->default_instructor)
                                <span class="text-slate-300">—</span>
                            @endif
                        </div>
                    </flux:table.cell>

                    {{-- Estado --}}
                    <flux:table.cell class="py-1">
                        <flux:badge :color="$def->is_active ? 'green' : 'slate'" size="sm"
                            :icon="$def->is_active ? 'check-circle' : 'minus-circle'">
                            {{ $def->is_active ? 'Activa' : 'Inactiva' }}
                        </flux:badge>
                    </flux:table.cell>

                    {{-- Acciones --}}
                    <flux:table.cell align="end" class="py-1">
                        <div class="flex items-center justify-end gap-1">
                            <flux:button wire:click="edit({{ $def->id }})" variant="ghost" size="sm" icon="pencil" />
                            <flux:button wire:click="delete({{ $def->id }})" variant="ghost" size="sm" icon="trash"
                                wire:confirm="¿Eliminar esta definición de actividad?" />
                        </div>
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center py-12">
                        <flux:icon name="no-symbol" class="mx-auto w-10 h-10 text-slate-300 dark:text-slate-600 mb-3" />
                        <flux:text class="text-slate-400">No hay definiciones de actividades registradas.</flux:text>
                        <flux:button wire:click="create" variant="ghost" size="sm" class="mt-3" icon="plus">
                            Crear primera actividad
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- MODAL CREAR / EDITAR --}}
    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-5">
            <flux:heading size="lg">
                {{ $form->definition ? 'Editar Definición' : 'Nueva Definición de Actividad' }}
            </flux:heading>

            <flux:input wire:model="form.name" label="Nombre de la Actividad"
                placeholder="Ej. Feedback Mensual de Calidad" />

            <flux:select wire:model="form.activity_type_id" label="Tipo de Actividad Base"
                placeholder="Seleccione un tipo...">
                @foreach($activityTypes as $type)
                    <flux:select.option :value="$type->id">{{ $type->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="form.default_duration_minutes"
                    type="number" label="Duración por defecto (min)" min="1" />
                <flux:input wire:model="form.default_location" label="Ubicación por defecto"
                    placeholder="Ej. Sala de Capacitación" />
            </div>

            <flux:input wire:model="form.default_instructor" label="Instructor / Expositor"
                placeholder="Nombre o cargo del facilitador" />

            <flux:checkbox wire:model="form.is_active"
                label="Activa y disponible para planificación" />

            <div class="flex justify-end gap-3 pt-2">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancelar</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>

</div>
