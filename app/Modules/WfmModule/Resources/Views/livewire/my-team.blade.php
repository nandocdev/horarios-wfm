<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Mi Equipo') }}</flux:heading>
            <flux:subheading>{{ __('Gestión y visibilidad de horarios para supervisores') }}</flux:subheading>
        </div>

        <div class="flex items-center gap-4">
            @if($teams->count() > 1)
                <flux:select wire:model.live="selectedTeam" placeholder="{{ __('Seleccionar Equipo') }}" class="w-64">
                    @foreach($teams as $team)
                        <flux:select.option value="{{ $team->id }}">{{ $team->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

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
                                <th class="py-3 px-4 font-semibold text-sm text-center {{ $day->isToday() ? 'bg-accent/10 text-accent' : 'text-zinc-500 dark:text-zinc-400' }}">
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
                            <tr class="border-b border-zinc-100 dark:border-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
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
                                        $exception = $exceptions->get($member->id)?->filter(function($ex) use ($day) {
                                            return $day->between($ex->start_at->startOfday(), $ex->end_at->endOfDay());
                                        })->first();
                                    @endphp
                                    <td class="py-2 px-1 text-center">
                                        @if($exception)
                                            <div class="px-2 py-1 rounded text-[10px] font-bold uppercase truncate max-w-[100px] mx-auto" 
                                                 style="background-color: {{ $exception->reason?->color ?? '#ef4444' }}20; color: {{ $exception->reason?->color ?? '#ef4444' }}; border: 1px solid {{ $exception->reason?->color ?? '#ef4444' }}40;"
                                                 title="{{ $exception->reason?->name }}">
                                                {{ $exception->reason?->name }}
                                            </div>
                                        @elseif($assignment)
                                            @php
                                                $startTime = $assignment->start_time ? $assignment->start_time->format('H:i') : ($assignment->schedule?->start_time ? \Carbon\Carbon::parse($assignment->schedule->start_time)->format('H:i') : '--:--');
                                                $endTime = $assignment->end_time ? $assignment->end_time->format('H:i') : ($assignment->schedule?->end_time ? \Carbon\Carbon::parse($assignment->schedule->end_time)->format('H:i') : '--:--');
                                            @endphp
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-xs font-semibold">{{ $startTime }} - {{ $endTime }}</span>
                                                @if($assignment->schedule)
                                                    <span class="text-[9px] text-zinc-500 uppercase">{{ $assignment->schedule->name }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs text-zinc-400 italic">{{ __('Libre') }}</span>
                                        @endif
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
                        <div class="flex items-center justify-between p-3 rounded-lg border border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                            <div class="flex items-center gap-3">
                                <div class="flex -space-x-2">
                                    <flux:avatar initials="{{ $swap->requester->initials }}" size="xs" class="ring-2 ring-white dark:ring-zinc-900" />
                                    <flux:avatar initials="{{ $swap->recipient->initials }}" size="xs" class="ring-2 ring-white dark:ring-zinc-900" />
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium">{{ $swap->requester->first_name }} ⇄ {{ $swap->recipient->first_name }}</span>
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
                        <p class="text-sm text-zinc-500 italic">{{ __('No hay solicitudes de cambio recientes en tu equipo.') }}</p>
                    @endforelse
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Permisos y Ausencias Próximas') }}</flux:heading>
                <div class="space-y-4">
                    @forelse($upcomingExceptions as $leave)
                        <div class="flex items-center justify-between p-3 rounded-lg border border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                            <div class="flex items-center gap-3">
                                <flux:avatar initials="{{ $leave->employee->initials }}" size="xs" />
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium">{{ $leave->employee->full_name }}</span>
                                    <span class="text-xs text-zinc-500">{{ ucfirst($leave->type) }} ({{ $leave->start_time->format('d/m') }})</span>
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
                        <p class="text-sm text-zinc-500 italic">{{ __('No hay ausencias programadas para los próximos 7 días.') }}</p>
                    @endforelse
                </div>
            </flux:card>
        </div>
    </div>
</div>
