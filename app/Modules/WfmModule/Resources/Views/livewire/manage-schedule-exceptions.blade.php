<div class="p-6 lg:p-8 space-y-8 flex-1 flex flex-col">
    <div class="flex justify-between items-center mb-6">
        <div>
            <flux:heading size="xl">{{ __('Gestión de Excepciones de Horario') }}</flux:heading>
            <flux:subheading>{{ __('Vacaciones, licencias e incapacidades que sobrepasan el horario regular.') }}</flux:subheading>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">
            {{ __('Registrar Excepción') }}
        </flux:button>
    </div>

    {{-- Filtros --}}
    <flux:card class="p-4 bg-slate-50/50">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
            <flux:field class="flex-1">
                <flux:label>{{ __('Equipo') }}</flux:label>
                <flux:select wire:model.live="teamFilter" placeholder="{{ __('Todos') }}">
                    <flux:select.option value="">{{ __('Todos los equipos') }}</flux:select.option>
                    @foreach($managedTeams as $team)
                        <flux:select.option value="{{ $team->id }}">{{ $team->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field class="flex-1">
                <flux:label>{{ __('Buscar empleado') }}</flux:label>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Nombre o usuario...') }}" icon="magnifying-glass" />
            </flux:field>

            <flux:field class="flex-1">
                <flux:label>{{ __('Desde') }}</flux:label>
                <flux:input wire:model.live="dateFrom" type="date" />
            </flux:field>

            <flux:field class="flex-1">
                <flux:label>{{ __('Hasta') }}</flux:label>
                <flux:input wire:model.live="dateTo" type="date" />
            </flux:field>

            <flux:field class="flex-1">
                <flux:label>{{ __('Motivo') }}</flux:label>
                <flux:select wire:model.live="reasonFilter" placeholder="{{ __('Todos') }}">
                    <flux:select.option value="">{{ __('Todos los motivos') }}</flux:select.option>
                    @foreach($reasons as $reason)
                        <flux:select.option value="{{ $reason->id }}">{{ $reason->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field class="flex-1">
                <flux:label>{{ __('Estado') }}</flux:label>
                <div class="flex gap-2 items-center h-10">
                    <flux:checkbox wire:model.live="statusFilter" value="active" label="{{ __('Activo') }}" checked />
                    <flux:checkbox wire:model.live="statusFilter" value="pending" label="{{ __('Pendiente') }}" checked />
                    <flux:checkbox wire:model.live="statusFilter" value="completed" label="{{ __('Completado') }}" />
                </div>
            </flux:field>
        </div>
    </flux:card>

    {{-- Tabla --}}
    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns class="sticky top-0 z-10 bg-white">
                <flux:table.column sticky>{{ __('Empleado') }}</flux:table.column>
                <flux:table.column>{{ __('Motivo') }}</flux:table.column>
                <flux:table.column>{{ __('Desde') }}</flux:table.column>
                <flux:table.column>{{ __('Hasta') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
                <flux:table.column>{{ __('Cumplimiento') }}</flux:table.column>
                <flux:table.column>{{ __('Día Completo') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Acciones') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($exceptions as $exception)
                    @php
                        $start = \Carbon\Carbon::parse($exception->start_at);
                        $end = \Carbon\Carbon::parse($exception->end_at);
                        $today = $now->copy()->startOfDay();
                        $isActive = $start->lte($today) && $end->gte($today);
                        $isPending = $start->gt($today);
                        $isCompleted = $end->lt($today);

                        $statusLabel = $isActive ? __('Activo') : ($isPending ? __('Pendiente') : __('Completado'));
                        $statusColor = $isActive ? 'emerald' : ($isPending ? 'amber' : 'slate');
                        $statusIcon = $isActive ? 'check-circle' : ($isPending ? 'clock' : 'check');
                    @endphp
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
                            <flux:badge size="sm" class="font-bold"
                                style="background-color: {{ $exception->reason?->color ?? '#94a3b8' }}20; color: {{ $exception->reason?->color ?? '#94a3b8' }}; border: 1px solid {{ $exception->reason?->color ?? '#94a3b8' }}40;">
                                {{ $exception->reason?->name ?? __('N/A') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="py-2 text-sm">{{ $start->format('d M, H:i') }}</flux:table.cell>
                        <flux:table.cell class="py-2 text-sm">{{ $end->format('d M, H:i') }}</flux:table.cell>
                        <flux:table.cell class="py-2">
                            <flux:badge size="sm" :icon="$statusIcon" :color="$statusColor">{{ $statusLabel }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="py-2">
                            @if($isActive)
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-1 rounded-md">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    {{ __('En curso') }}
                                </span>
                            @elseif($isPending)
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-1 rounded-md">
                                    <flux:icon icon="clock" class="w-3 h-3" />
                                    {{ __('Pendiente') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 bg-slate-100 px-2 py-1 rounded-md">
                                    <flux:icon icon="check" class="w-3 h-3" />
                                    {{ __('Completado') }}
                                </span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="py-2">
                            <flux:badge variant="ghost" size="sm">
                                {{ $exception->is_full_day ? __('Sí') : __('No') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="py-2">
                            <flux:dropdown>
                                <flux:button variant="subtle" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="edit({{ $exception->id }})" icon="pencil-square">{{ __('Editar') }}</flux:menu.item>
                                    @php $shortCode = $exception->reason?->short_code ?? ''; @endphp
                                    @if(!in_array($shortCode, ['T.I.', 'T.J.', 'V.', 'L.', 'S.D', 'R']))
                                        <flux:menu.item wire:click="openF1Preview({{ $exception->id }})" icon="document-text">{{ __('Vista Previa F1') }}</flux:menu.item>
                                    @endif
                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="delete({{ $exception->id }})" wire:confirm="{{ __('¿Seguro que desea eliminar esta excepción?') }}" variant="danger" icon="trash">{{ __('Eliminar') }}</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="py-12 text-center text-slate-500 italic">
                            {{ __('No se encontraron excepciones con los filtros actuales.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if($exceptions->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $exceptions->links() }}
            </div>
        @endif
    </flux:card>

    {{-- Modal de Registro/Edicion --}}
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

    {{-- Modal Vista Previa F1 --}}
    <flux:modal wire:model="showF1Preview" class="w-full max-w-4xl" scrollable>
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Formulario F1 — Justificación de Inasistencia') }}</flux:heading>
                <flux:subheading>{{ __('Revise y edite los campos antes de generar el PDF.') }}</flux:subheading>
            </div>

            @php $d = $f1Data; @endphp

            <div class="border border-slate-200 dark:border-zinc-700 rounded-lg p-4 bg-white text-xs space-y-3 font-sans">
                {{-- Fecha --}}
                <div class="text-right">Fecha: <span class="border-b border-black inline-block min-w-[120px] px-1">{{ now()->format('d/m/Y') }}</span></div>

                {{-- Datos del funcionario --}}
                <table class="w-full border-collapse border border-black text-xs">
                    <tr>
                        <td class="border border-black px-2 py-1 w-[18%]">Cantidad de días</td>
                        <td class="border border-black px-2 py-1 w-[12%]"><input type="number" wire:model="f1Data.absence_total_days" class="w-full border-0 p-0 text-xs" /></td>
                        <td class="border border-black px-2 py-1 w-[22%]">Nombre del funcionario</td>
                        <td class="border border-black px-2 py-1" colspan="3"><input type="text" wire:model="f1Data.employee_name" class="w-full border-0 p-0 text-xs font-medium" /></td>
                    </tr>
                    <tr>
                        <td class="border border-black px-2 py-1">Cargo</td>
                        <td class="border border-black px-2 py-1" colspan="2"><input type="text" wire:model="f1Data.employee_position" class="w-full border-0 p-0 text-xs" /></td>
                        <td class="border border-black px-2 py-1">No. Empleado</td>
                        <td class="border border-black px-2 py-1"><input type="text" wire:model="f1Data.employee_number" class="w-full border-0 p-0 text-xs" /></td>
                        <td class="border border-black px-2 py-1">C.I.P.</td>
                    </tr>
                    <tr>
                        <td class="border border-black px-2 py-1">Salario</td>
                        <td class="border border-black px-2 py-1"><input type="number" wire:model="f1Data.base_salary" class="w-full border-0 p-0 text-xs" step="0.01" /></td>
                        <td class="border border-black px-2 py-1">Sobresueldo</td>
                        <td class="border border-black px-2 py-1"><input type="number" wire:model="f1Data.salary_supplement" class="w-full border-0 p-0 text-xs" step="0.01" /></td>
                        <td class="border border-black px-2 py-1"></td>
                        <td class="border border-black px-2 py-1"></td>
                    </tr>
                </table>

                {{-- Clasificación --}}
                <table class="w-full border-collapse border border-black text-xs">
                    <tr>
                        <td class="border border-black px-2 py-1" colspan="2">La ausencia es Justificada</td>
                        <td class="border border-black px-2 py-1 text-center w-[8%]">Sí</td>
                        <td class="border border-black px-2 py-1 text-center w-[8%]">
                            <input type="radio" wire:model="f1Data.is_justified" value="1" />
                        </td>
                        <td class="border border-black px-2 py-1 text-center w-[8%]">No</td>
                        <td class="border border-black px-2 py-1 text-center w-[8%]">
                            <input type="radio" wire:model="f1Data.is_justified" value="0" />
                        </td>
                    </tr>
                </table>

                {{-- Motivos --}}
                <table class="w-full border-collapse border border-black text-xs">
                    <tr>
                        <td class="border border-black px-2 py-1 text-center w-[8%]"><input type="radio" wire:model="f1Data.reason_type" value="comun" /></td>
                        <td class="border border-black px-2 py-1 w-[15%]">Común</td>
                        <td class="border border-black px-2 py-1 text-center w-[8%]"><input type="radio" wire:model="f1Data.reason_type" value="riesgos" /></td>
                        <td class="border border-black px-2 py-1 w-[15%]">Riesgos Prof.</td>
                        <td class="border border-black px-2 py-1 text-center w-[8%]"><input type="radio" wire:model="f1Data.reason_type" value="duelo" /></td>
                        <td class="border border-black px-2 py-1 w-[10%]">Duelo</td>
                        <td class="border border-black px-2 py-1 text-center w-[8%]"><input type="radio" wire:model="f1Data.reason_type" value="nacimiento" /></td>
                        <td class="border border-black px-2 py-1 w-[12%]">Nacimiento</td>
                        <td class="border border-black px-2 py-1 text-center w-[8%]"><input type="radio" wire:model="f1Data.reason_type" value="otro" /></td>
                        <td class="border border-black px-2 py-1">Otro</td>
                    </tr>
                </table>

                {{-- Evidencias --}}
                <table class="w-full border-collapse border border-black text-xs">
                    <tr>
                        <td class="border border-black px-2 py-1 w-[18%]">Certificado Médico</td>
                        <td class="border border-black px-2 py-1 w-[10%] text-center">
                            Sí <input type="radio" wire:model="f1Data.medical_certificate_attached" value="1" />
                            No <input type="radio" wire:model="f1Data.medical_certificate_attached" value="0" />
                        </td>
                        <td class="border border-black px-2 py-1 w-[18%]">Sustentadores</td>
                        <td class="border border-black px-2 py-1 text-center">
                            Sí <input type="radio" wire:model="f1Data.has_witnesses" value="1" />
                            No <input type="radio" wire:model="f1Data.has_witnesses" value="0" />
                        </td>
                        <td class="border border-black px-2 py-1 w-[15%]">Jefe Depto.</td>
                        <td class="border border-black px-2 py-1"><input type="text" wire:model="f1Data.department_head_name" class="w-full border-0 p-0 text-xs" /></td>
                        <td class="border border-black px-2 py-1 w-[12%]">Unidad Ejec.</td>
                        <td class="border border-black px-2 py-1"><input type="text" wire:model="f1Data.executive_unit" class="w-full border-0 p-0 text-xs" /></td>
                    </tr>
                </table>

                {{-- Observaciones --}}
                <div>
                    <strong>Observaciones:</strong>
                    <textarea wire:model="f1Data.observations" rows="2" class="w-full border border-black rounded px-2 py-1 text-xs mt-1"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-slate-200 dark:border-zinc-700">
                <flux:button variant="ghost" wire:click="$set('showF1Preview', false)">{{ __('Cancelar') }}</flux:button>
                <flux:button wire:click="generateF1" variant="primary" icon="arrow-down-tray">{{ __('Generar PDF') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
