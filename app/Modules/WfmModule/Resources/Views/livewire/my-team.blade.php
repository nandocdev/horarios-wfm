<div class="space-y-6">
    <x-wfm.page-header title="Mi Equipo" description="Gestión y visibilidad de horarios para supervisores.">
        <x-slot:actions>
            <flux:select wire:model.live="selectedTeam" placeholder="Filtrar por Equipo" class="!w-56">
                <flux:select.option value="">Todos los equipos</flux:select.option>
                @foreach($teams as $team)
                    <flux:select.option value="{{ $team->id }}">
                        {{ $team->name }} - {{ $team->supervisor?->full_name ?? 'Sin Coordinador' }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:input type="date" wire:model.live="date" class="!w-40" />
        </x-slot:actions>
    </x-wfm.page-header>

    <x-wfm.section title="Planilla Semanal">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-wfm-surface-border">
                        <th class="py-2 px-3 font-semibold text-[10px] uppercase tracking-wider text-wfm-surface-muted min-w-[180px]">Empleado</th>
                        @foreach($days as $day)
                            <th class="py-2 px-3 font-semibold text-[10px] uppercase tracking-wider text-center {{ $day->isToday() ? 'text-wfm-info' : 'text-wfm-surface-muted' }}">
                                <div class="flex flex-col">
                                    <span>{{ $day->isoFormat('ddd') }}</span>
                                    <span class="text-[9px]">{{ $day->format('d/m') }}</span>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                        <tr class="border-b border-wfm-surface-border hover:bg-wfm-surface-hover transition-colors">
                            <td class="py-2 px-3">
                                <div class="flex items-center gap-2">
                                    <flux:avatar :name="$member->full_name" :initials="$member->initials" size="xs" />
                                    <div>
                                        <p class="text-xs font-medium text-wfm-navy-800 dark:text-white">{{ $member->full_name }}</p>
                                        <p class="text-[10px] text-wfm-surface-muted">{{ $member->position?->name }}</p>
                                    </div>
                                </div>
                            </td>
                            @foreach($days as $day)
                                @php
                                    $dayOfWeek = $day->dayOfWeekIso;
                                    $assignment = $assignments->get($member->id)?->firstWhere('day_of_week', $dayOfWeek);
                                    $dayExceptions = $exceptions->get($member->id)?->filter(fn ($ex) => $day->between($ex->start_at->startOfDay(), $ex->end_at->endOfDay())) ?? collect();
                                    $dayPending = ($pendingRequests[$member->id] ?? collect())->filter(fn ($req) => $day->between($req->start_time->startOfDay(), $req->end_time->endOfDay()));
                                @endphp
                                <td class="py-1.5 px-1 text-center cursor-pointer hover:bg-wfm-surface-hover"
                                    wire:click="openIncidentModal({{ $member->id }}, '{{ $day->toDateString() }}')">
                                    <div class="flex flex-col gap-0.5 items-center">
                                        @foreach($dayExceptions as $ex)
                                            <div wire:click.stop="editIncident({{ $ex->id }})"
                                                class="px-2 py-0.5 rounded text-[9px] font-bold uppercase truncate max-w-[90px] cursor-pointer hover:brightness-95"
                                                style="background-color: {{ $ex->reason?->color ?? '#ef4444' }}20; color: {{ $ex->reason?->color ?? '#ef4444' }}; border: 1px solid {{ $ex->reason?->color ?? '#ef4444' }}40;"
                                                title="{{ $ex->reason?->name }}: {{ $ex->remarks }}">
                                                {{ $ex->reason?->name }}
                                            </div>
                                        @endforeach

                                        @foreach($dayPending as $pending)
                                            <div class="px-2 py-0.5 rounded text-[9px] font-bold uppercase truncate max-w-[90px] border border-dashed border-wfm-surface-muted bg-wfm-surface/50"
                                                title="Solicitud Pendiente: {{ $pending->type }}">
                                                {{ $pending->type }}
                                                <span class="block text-[7px] italic text-wfm-surface-muted">Pendiente</span>
                                            </div>
                                        @endforeach

                                        @if($dayExceptions->isEmpty() && $dayPending->isEmpty())
                                            @if($assignment)
                                                @php
                                                    $startTime = $assignment->start_time ? $assignment->start_time->format('H:i') : ($assignment->schedule?->start_time ? Carbon\Carbon::parse($assignment->schedule->start_time)->format('H:i') : '--:--');
                                                    $endTime = $assignment->end_time ? $assignment->end_time->format('H:i') : ($assignment->schedule?->end_time ? Carbon\Carbon::parse($assignment->schedule->end_time)->format('H:i') : '--:--');
                                                @endphp
                                                <div>
                                                    <span class="text-[11px] font-semibold">{{ $startTime }} - {{ $endTime }}</span>
                                                    @if($assignment->schedule)
                                                        <p class="text-[8px] text-wfm-surface-muted uppercase">{{ $assignment->schedule->name }}</p>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-[10px] text-wfm-surface-muted italic">Libre</span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12">
                                <x-wfm.empty icon="users" message="No se encontraron miembros en este equipo o alcance." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-wfm.section>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-wfm.section title="Solicitudes de Cambio (Swaps)">
            @forelse($recentSwaps as $swap)
                <div class="flex items-center justify-between p-3 rounded border border-wfm-surface-border bg-wfm-surface/50 mb-2 last:mb-0">
                    <div class="flex items-center gap-3">
                        <div class="flex -space-x-2">
                            <flux:avatar initials="{{ $swap->requester->initials }}" size="xs" class="ring-2 ring-white" />
                            <flux:avatar initials="{{ $swap->recipient->initials }}" size="xs" class="ring-2 ring-white" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-wfm-navy-800 dark:text-white">{{ $swap->requester->first_name }} ⇄ {{ $swap->recipient->first_name }}</p>
                            <p class="text-[10px] text-wfm-surface-muted">{{ $swap->requested_date->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    <x-wfm.agent-status :status="match($swap->status) { 'pending' => 'break', 'accepted' => 'available', 'approved' => 'available', 'rejected' => 'busy', default => 'offline' }" :label="$swap->status" size="xs" />
                </div>
            @empty
                <p class="text-xs text-wfm-surface-muted italic">No hay solicitudes de cambio recientes en tu equipo.</p>
            @endforelse
        </x-wfm.section>

        <x-wfm.section title="Permisos y Ausencias Próximas">
            @forelse($upcomingExceptions as $leave)
                <div class="flex items-center justify-between p-3 rounded border border-wfm-surface-border bg-wfm-surface/50 mb-2 last:mb-0">
                    <div class="flex items-center gap-3">
                        <flux:avatar :name="$leave->employee->full_name" :initials="$leave->employee->initials" size="xs" />
                        <div>
                            <p class="text-xs font-medium text-wfm-navy-800 dark:text-white">{{ $leave->employee->full_name }}</p>
                            <p class="text-[10px] text-wfm-surface-muted">{{ ucfirst($leave->type) }} ({{ $leave->start_time->format('d/m') }})</p>
                        </div>
                    </div>
                    <x-wfm.agent-status :status="match($leave->status) { 'pending' => 'break', 'approved' => 'available', 'rejected' => 'busy', default => 'offline' }" :label="$leave->status" size="xs" />
                </div>
            @empty
                <p class="text-xs text-wfm-surface-muted italic">No hay ausencias programadas para los próximos 7 días.</p>
            @endforelse
        </x-wfm.section>
    </div>

    <flux:modal name="incident-modal" class="w-full max-w-lg">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Registrar Incidente / Novedad') }}</flux:heading>
                <flux:subheading>{{ __('Modificar el estado del turno para el día seleccionado.') }}</flux:subheading>
            </div>

            <form wire:submit="saveIncident" class="space-y-4">
                <flux:select wire:model="incidentForm.reason_id" label="{{ __('Motivo / Incidente') }}" placeholder="{{ __('Seleccionar motivo') }}">
                    @foreach($reasons as $reason)
                        <flux:select.option value="{{ $reason->id }}">{{ $reason->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="incidentForm.reason_id" />

                <flux:checkbox wire:model.live="incidentForm.is_full_day" label="{{ __('Día Completo') }}" />

                @if(!$incidentForm->is_full_day)
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input type="time" wire:model="incidentForm.start_time" label="{{ __('Hora Inicio') }}" />
                        <flux:input type="time" wire:model="incidentForm.end_time" label="{{ __('Hora Fin') }}" />
                    </div>
                @endif

                <flux:textarea wire:model="incidentForm.remarks" label="{{ __('Observaciones / Justificación') }}" placeholder="{{ __('Detalles adicionales...') }}" />

                <div class="flex gap-2 justify-end">
                    @if($incidentForm->id)
                        <flux:spacer />
                        <flux:button variant="danger" icon="trash" wire:click="deleteIncident" wire:confirm="{{ __('¿Estás seguro de eliminar este registro?') }}">
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
