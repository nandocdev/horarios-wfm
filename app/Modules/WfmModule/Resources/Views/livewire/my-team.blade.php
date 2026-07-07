<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Mi Equipo') }}</flux:heading>
            <flux:subheading>{{ __('Gestión y visibilidad de horarios para supervisores') }}</flux:subheading>
        </div>

        <div class="flex items-center gap-4">
            <flux:select wire:model.live="selectedTeam" placeholder="{{ __('Filtrar por Equipo') }}" class="w-64">
                <flux:select.option value="">{{ __('Todos los equipos') }}</flux:select.option>
                @foreach($teams as $team)
                    <flux:select.option value="{{ $team->id }}">
                        {{ $team->name }} - {{ $team->supervisor?->full_name ?? __('Sin Coordinador') }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input type="date" wire:model.live="date" class="w-48" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <flux:card>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-800">
                            <th class="py-3 px-4 font-semibold text-sm text-zinc-500 dark:text-zinc-400 min-w-[200px]">
                                {{ __('Empleado') }}
                            </th>
                            @foreach($days as $day)
                                <th
                                    class="py-3 px-4 font-semibold text-sm text-center {{ $day->isToday() ? 'bg-accent/10 text-accent' : 'text-zinc-500 dark:text-zinc-400' }}">
                                    <div class="flex flex-col">
                                        <span>{{ $day->isoFormat('ddd') }}</span>
                                        <span class="text-xs">{{ $day->format('d/m') }}</span>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $member)
                            <tr
                                class="border-b border-zinc-100 dark:border-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-opacity">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <flux:avatar initials="{{ $member->initials }}" size="sm" />
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium">{{ $member->full_name }}</span>
                                            <span class="text-xs text-zinc-500">{{ $member->position?->name }}</span>
                                        </div>
                                    </div>
                                </td>
                                @foreach($days as $day)
                                    @php
                                        $dayOfWeek = $day->dayOfWeekIso;
                                        $assignment = $assignments->get($member->id)?->firstWhere('day_of_week', $dayOfWeek);
                                        $dayExceptions = $exceptions->get($member->id)?->filter(function ($ex) use ($day) {
                                            return $day->between($ex->start_at->startOfday(), $ex->end_at->endOfDay());
                                        }) ?? collect();
                                        $dayPending = ($pendingRequests[$member->id] ?? collect())->filter(function ($req) use ($day) {
                                            return $day->between($req->start_time->startOfDay(), $req->end_time->endOfDay());
                                        });
                                    @endphp
                                    <td class="py-2 px-1 text-center cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-opacity"
                                        wire:click="openIncidentModal({{ $member->id }}, '{{ $day->toDateString() }}')">
                                        <div class="flex flex-col gap-1">
                                            @foreach($dayExceptions as $ex)
                                                <div wire:click.stop="editIncident({{ $ex->id }})"
                                                    class="px-2 py-1 rounded text-[10px] font-bold uppercase truncate max-w-[100px] mx-auto hover:brightness-95 transition-opacity shadow-sm"
                                                    style="background-color: {{ $ex->reason?->color ?? '#ef4444' }}20; color: {{ $ex->reason?->color ?? '#ef4444' }}; border: 1px solid {{ $ex->reason?->color ?? '#ef4444' }}40;"
                                                    title="{{ $ex->reason?->name }}: {{ $ex->remarks }}">
                                                    {{ $ex->reason?->name }}
                                                </div>
                                            @endforeach

                                            @foreach($dayPending as $pending)
                                                <div class="px-2 py-1 rounded text-[10px] font-bold uppercase truncate max-w-[100px] mx-auto opacity-60 border border-dashed border-zinc-400 bg-zinc-100 dark:bg-zinc-800"
                                                    title="{{ __('Solicitud Pendiente') }}: {{ $pending->type }}">
                                                    {{ $pending->type }}
                                                    <span class="block text-[8px] italic text-zinc-500">{{ __('Pendiente') }}</span>
                                                </div>
                                            @endforeach

                                            @if($dayExceptions->isEmpty() && $dayPending->isEmpty())
                                                @if($assignment)
                                                    @php
                                                        $startTime = $assignment->start_time ? $assignment->start_time->format('H:i') : ($assignment->schedule?->start_time ? \Carbon\Carbon::parse($assignment->schedule->start_time)->format('H:i') : '--:--');
                                                        $endTime = $assignment->end_time ? $assignment->end_time->format('H:i') : ($assignment->schedule?->end_time ? \Carbon\Carbon::parse($assignment->schedule->end_time)->format('H:i') : '--:--');
                                                    @endphp
                                                    <div class="flex flex-col gap-0.5">
                                                        <span class="text-xs font-semibold">{{ $startTime }} - {{ $endTime }}</span>
                                                        @if($assignment->schedule)
                                                            <span
                                                                class="text-[9px] text-zinc-500 uppercase">{{ $assignment->schedule->name }}</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-xs text-zinc-400 italic">{{ __('Libre') }}</span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-zinc-500 italic">
                                    {{ __('No se encontraron miembros en este equipo o alcance.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Solicitudes de Cambio (Swaps)') }}</flux:heading>
                <div class="space-y-4">
                    @forelse($recentSwaps as $swap)
                        <div
                            class="flex items-center justify-between p-3 rounded-md border border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                            <div class="flex items-center gap-3">
                                <div class="flex -space-x-2">
                                    <flux:avatar initials="{{ $swap->requester->initials }}" size="xs"
                                        class="ring-2 ring-white dark:ring-zinc-900" />
                                    <flux:avatar initials="{{ $swap->recipient->initials }}" size="xs"
                                        class="ring-2 ring-white dark:ring-zinc-900" />
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium">{{ $swap->requester->first_name }} ⇄
                                        {{ $swap->recipient->first_name }}</span>
                                    <span class="text-xs text-zinc-500">{{ $swap->requested_date->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            <flux:badge size="sm" :variant="match($swap->status) {
                                            'pending' => 'warning',
                                            'accepted' => 'success',
                                            'approved' => 'primary',
                                            'rejected' => 'danger',
                                            default => 'zinc'
                                        }">
                                {{ __($swap->status) }}
                            </flux:badge>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 italic">
                            {{ __('No hay solicitudes de cambio recientes en tu equipo.') }}
                        </p>
                    @endforelse
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Permisos y Ausencias Próximas') }}</flux:heading>
                <div class="space-y-4">
                    @forelse($upcomingExceptions as $leave)
                        <div
                            class="flex items-center justify-between p-3 rounded-md border border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                            <div class="flex items-center gap-3">
                                <flux:avatar initials="{{ $leave->employee->initials }}" size="xs" />
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium">{{ $leave->employee->full_name }}</span>
                                    <span class="text-xs text-zinc-500">{{ ucfirst($leave->type) }}
                                        ({{ $leave->start_time->format('d/m') }})</span>
                                </div>
                            </div>
                            <flux:badge size="sm" :variant="match($leave->status) {
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            default => 'zinc'
                                        }">
                                {{ __($leave->status) }}
                            </flux:badge>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 italic">
                            {{ __('No hay ausencias programadas para los próximos 7 días.') }}
                        </p>
                    @endforelse
                </div>
            </flux:card>
        </div>
    </div>

    <flux:modal name="incident-modal" class="md:w-[500px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Registrar Incidente / Novedad') }}</flux:heading>
                <flux:subheading>{{ __('Modificar el estado del turno para el día seleccionado.') }}</flux:subheading>
            </div>

            <form wire:submit="saveIncident" class="space-y-4">
                <flux:select wire:model="incidentForm.reason_id" label="{{ __('Motivo / Incidente') }}"
                    placeholder="{{ __('Seleccionar motivo') }}">
                    @foreach($reasons as $reason)
                        <flux:select.option value="{{ $reason->id }}">{{ $reason->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:checkbox wire:model.live="incidentForm.is_full_day" label="{{ __('Día Completo') }}" />

                @if(!$incidentForm['is_full_day'])
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input type="time" wire:model="incidentForm.start_time" label="{{ __('Hora Inicio') }}" />
                        <flux:input type="time" wire:model="incidentForm.end_time" label="{{ __('Hora Fin') }}" />
                    </div>
                @endif

                <flux:textarea wire:model="incidentForm.remarks" label="{{ __('Observaciones / Justificación') }}"
                    placeholder="{{ __('Detalles adicionales...') }}" />

                <div class="flex gap-2 justify-end">
                    @if($incidentForm['id'])
                        <flux:spacer />
                        <flux:button variant="danger" icon="trash" wire:click="deleteIncident"
                            wire:confirm="{{ __('¿Estás seguro de eliminar este registro?') }}">
                            {{ __('Eliminar') }}
                        </flux:button>
                    @endif
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancelar') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">{{ __('Guardar Novedad') }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>