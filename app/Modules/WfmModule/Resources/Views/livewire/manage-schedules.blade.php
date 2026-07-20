<div class="space-y-6">
    <x-wfm.page-header title="Administración de Turnos" description="Gestión de los turnos base del sistema.">
        <x-slot:actions>
            <flux:button wire:click="create" variant="primary" icon="plus">Nuevo Turno</flux:button>
        </x-slot:actions>
    </x-wfm.page-header>

    <x-wfm.table :headers="['Nombre', 'Horario', 'Días', 'Duración', 'Break/Lunch', 'Estado', 'Acciones']" compact>
        @foreach ($schedules as $schedule)
            @php
                $dayLabels = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];
                $allowed = $schedule->allowed_days ?? [];
            @endphp
            <flux:table.row :key="$schedule->id">
                <flux:table.cell class="font-medium">{{ $schedule->name }}</flux:table.cell>
                <flux:table.cell><span class="font-mono">{{ $schedule->start_time }} - {{ $schedule->end_time }}</span></flux:table.cell>
                <flux:table.cell>
                    <div class="flex gap-0.5">
                        @foreach($dayLabels as $dayNum => $label)
                            <span class="text-[10px] font-bold {{ in_array($dayNum, $allowed) ? 'text-wfm-info' : 'text-wfm-surface-muted/40' }}">{{ $label }}</span>
                        @endforeach
                    </div>
                </flux:table.cell>
                <flux:table.cell>{{ $schedule->total_minutes }} min</flux:table.cell>
                <flux:table.cell class="text-xs text-wfm-surface-muted">B: {{ $schedule->break_minutes }}m / L: {{ $schedule->lunch_minutes }}m</flux:table.cell>
                <flux:table.cell>
                    <x-wfm.agent-status :status="$schedule->is_active ? 'available' : 'offline'" :label="$schedule->is_active ? 'Activo' : 'Inactivo'" size="xs" />
                </flux:table.cell>
                <flux:table.cell class="text-right">
                    <flux:button wire:click="edit({{ $schedule->id }})" variant="ghost" size="sm" icon="pencil" />
                    <flux:button wire:click="delete({{ $schedule->id }})" variant="ghost" size="sm" icon="trash" wire:confirm="¿Eliminar este turno?" />
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </x-wfm.table>

    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $form->schedule ? 'Editar Turno' : 'Nuevo Turno' }}</flux:heading>

            <flux:input wire:model="form.name" label="Nombre del Turno" placeholder="Ej. Mañana 08-17" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="form.start_time" type="time" label="Hora Inicio" />
                <flux:input wire:model="form.end_time" type="time" label="Hora Fin" />
            </div>

            <div class="grid grid-cols-3 gap-4">
                <flux:input wire:model="form.total_minutes" type="number" label="Minutos Totales" readonly />
                <flux:input wire:model="form.break_minutes" type="number" label="Minutos Break" />
                <flux:input wire:model="form.lunch_minutes" type="number" label="Minutos Lunch" />
            </div>

            <flux:field label="Días con alcance">
                <div class="flex flex-wrap gap-4">
                    @foreach([1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'] as $num => $label)
                        <flux:checkbox wire:model="form.allowed_days" value="{{ $num }}" label="{{ $label }}" />
                    @endforeach
                </div>
            </flux:field>

            <div class="space-y-2">
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
