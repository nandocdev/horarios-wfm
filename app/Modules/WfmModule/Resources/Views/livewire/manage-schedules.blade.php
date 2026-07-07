<div class="p-4">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Administración de Turnos</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Nuevo Turno</flux:button>
    </div>

    <flux:table :paginate="$schedules">
        <flux:table.columns class="sticky top-0 z-10 bg-white">
            <flux:table.column>Nombre</flux:table.column>
            <flux:table.column>Horario</flux:table.column>
            <flux:table.column>Días</flux:table.column>
            <flux:table.column>Duración (Min)</flux:table.column>
            <flux:table.column>Break/Lunch</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($schedules as $schedule)
                <flux:table.row :key="$schedule->id" class="hover:bg-slate-50">
                    <flux:table.cell class="py-2" class="font-medium">{{ $schedule->name }}</flux:table.cell>
                    <flux:table.cell class="py-2">{{ $schedule->start_time }} - {{ $schedule->end_time }}</flux:table.cell>
                    <flux:table.cell class="py-2">
                        <div class="flex gap-1">
                            @php
                                $dayLabels = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];
                                $allowed = $schedule->allowed_days ?? [];
                            @endphp
                            @foreach($dayLabels as $dayNum => $label)
                                <span class="text-xs font-bold {{ in_array($dayNum, $allowed) ? 'text-blue-600 dark:text-blue-400' : 'text-slate-300 dark:text-slate-700' }}">
                                    {{ $label }}
                                </span>
                            @endforeach
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="py-2">{{ $schedule->total_minutes }}</flux:table.cell>
                    <flux:table.cell class="py-2">
                        <div class="text-xs text-slate-500">
                            B: {{ $schedule->break_minutes }}m ({{ $schedule->is_break_paid ? 'P' : 'NP' }}) / 
                            L: {{ $schedule->lunch_minutes }}m ({{ $schedule->is_lunch_paid ? 'P' : 'NP' }})
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="py-2">
                        <flux:badge :color="$schedule->is_active ? 'green' : 'red'" >
                            {{ $schedule->is_active ? 'Activo' : 'Inactivo' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="py-2" align="end">
                        <flux:button wire:click="edit({{ $schedule->id }})" variant="ghost" size="sm" icon="pencil" />
                        <flux:button wire:click="delete({{ $schedule->id }})" variant="ghost" size="sm" icon="trash" color="red" wire:confirm="¿Estás seguro de eliminar este turno?" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $form->schedule ? 'Editar Turno' : 'Nuevo Turno' }}</flux:heading>

            <flux:input wire:model="form.name" label="Nombre del Turno" placeholder="Ej. Mañana 08-17" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="form.start_time" type="time" label="Hora Inicio" />
                <flux:input wire:model="form.end_time" type="time" label="Hora Fin" />
            </div>

            <div class="grid grid-cols-3 gap-4">
                <flux:input wire:model="form.total_minutes" type="number" label="Minutos Totales" readonly help="Calculado automáticamente" />
                <flux:input wire:model="form.break_minutes" type="number" label="Minutos Break" />
                <flux:input wire:model="form.lunch_minutes" type="number" label="Minutos Lunch" />
            </div>

            <div class="space-y-3">
                <flux:label>Días con alcance</flux:label>
                <div class="flex flex-wrap gap-4">
                    <flux:checkbox wire:model="form.allowed_days" value="1" label="L" />
                    <flux:checkbox wire:model="form.allowed_days" value="2" label="M" />
                    <flux:checkbox wire:model="form.allowed_days" value="3" label="X" />
                    <flux:checkbox wire:model="form.allowed_days" value="4" label="J" />
                    <flux:checkbox wire:model="form.allowed_days" value="5" label="V" />
                    <flux:checkbox wire:model="form.allowed_days" value="6" label="S" />
                    <flux:checkbox wire:model="form.allowed_days" value="7" label="D" />
                </div>
            </div>

            <div class="space-y-3">
                <flux:checkbox wire:model="form.is_break_paid" label="¿Break pagado?" />
                <flux:checkbox wire:model="form.is_lunch_paid" label="¿Lunch pagado?" />
                <flux:checkbox wire:model="form.is_active" label="¿Turno activo?" />
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
