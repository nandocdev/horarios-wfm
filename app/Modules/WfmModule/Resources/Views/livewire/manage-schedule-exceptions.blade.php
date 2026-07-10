<div class="p-6 lg:p-8 space-y-8 flex-1 flex flex-col">
    <div class="flex justify-between items-center mb-6">
        <div>
            <flux:heading size="xl">{{ __('Gestión de Excepciones de Horario') }}</flux:heading>
            <flux:subheading>{{ __('Vacaciones, licencias e incapacidades prolongadas que sobrepasan el horario regular.') }}</flux:subheading>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">
            {{ __('Registrar Excepción') }}
        </flux:button>
    </div>

    <flux:card class="space-y-4">
        <div class="flex gap-3">
            <flux:input wire:model.live.debounce.300ms="search" 
                placeholder="{{ __('Buscar por empleado...') }}" 
                icon="magnifying-glass" 
                class="flex-1" />
        </div>

        <flux:table>
            <flux:table.columns class="sticky top-0 z-10 bg-white">
                <flux:table.column sticky>{{ __('Empleado') }}</flux:table.column>
                <flux:table.column>{{ __('Motivo') }}</flux:table.column>
                <flux:table.column>{{ __('Desde') }}</flux:table.column>
                <flux:table.column>{{ __('Hasta') }}</flux:table.column>
                <flux:table.column>{{ __('Día Completo') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Acciones') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($exceptions as $exception)
                    <flux:table.row :key="$exception->id" class="hover:bg-slate-50">
                        <flux:table.cell sticky class="py-2">
                            <div class="flex items-center gap-3">
                                <flux:avatar initials="{{ $exception->employee->initials }}" size="xs" />
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium">{{ $exception->employee->full_name }}</span>
                                    <span class="text-xs text-slate-500">{{ $exception->employee->username }}</span>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="py-2">
                            <flux:badge :color="$exception->reason?->color ?? 'slate'" size="sm" class="font-bold">
                                {{ $exception->reason?->name ?? __('N/A') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="py-2">
                            <span class="text-sm">{{ $exception->start_at->format('d M, Y H:i') }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="py-2">
                            <span class="text-sm">{{ $exception->end_at->format('d M, Y H:i') }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="py-2">
                            <flux:badge variant="ghost" size="sm">
                                {{ $exception->is_full_day ? __('Sí') : __('No') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="py-2">
                            <div class="flex justify-end gap-2">
                                <flux:button wire:click="edit({{ $exception->id }})" variant="ghost" size="sm" icon="pencil-square" />
                                <flux:button wire:confirm="{{ __('¿Seguro que desea eliminar esta excepción?') }}" 
                                    wire:click="delete({{ $exception->id }})" 
                                    variant="ghost" 
                                    size="sm" 
                                    icon="trash" 
                                    class="text-red-500 hover:text-red-600" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $exceptions->links() }}
        </div>
    </flux:card>

    <!-- Modal de Registro/Edición -->
    <flux:modal wire:model="showCreateModal" class="w-full max-w-lg">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ $selectedExceptionId ? __('Editar Excepción') : __('Nueva Excepción') }}</flux:heading>
                <flux:subheading>{{ __('Defina el periodo y motivo de la ausencia.') }}</flux:subheading>
            </div>

            <form wire:submit="save" class="space-y-4">
                <flux:select wire:model="form.employee_id" label="{{ __('Empleado') }}" filterable>
                    <option value="">{{ __('Seleccione un empleado...') }}</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->username }})</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="form.absence_reason_code_id" label="{{ __('Motivo de Ausencia') }}">
                    <option value="">{{ __('Seleccione un motivo...') }}</option>
                    @foreach($reasons as $reason)
                        <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="form.start_at" type="datetime-local" label="{{ __('Inicio') }}" />
                    <flux:input wire:model="form.end_at" type="datetime-local" label="{{ __('Fin') }}" />
                </div>

                <flux:checkbox wire:model="form.is_full_day" label="{{ __('Cubre día completo') }}" />

                <flux:textarea wire:model="form.remarks" label="{{ __('Observaciones / Notas') }}" placeholder="{{ __('Detalles adicionales...') }}" />

                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">{{ __('Cancelar') }}</flux:button>
                    <flux:button type="submit" variant="primary" icon="check">
                        {{ $selectedExceptionId ? __('Actualizar Excepción') : __('Guardar Excepción') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
