<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <flux:heading size="xl">Solicitar Cambio de Turno</flux:heading>
        <flux:subheading>Completa los detalles para intercambiar tu turno con un compañero de otro equipo.
        </flux:subheading>
    </div>

    <flux:card>
        <form wire:submit="submit" class="space-y-6">
            @if($errors->has('general'))
                <flux:callout variant="danger">
                    {{ $errors->first('general') }}
                </flux:callout>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Fecha del Cambio</flux:label>
                    <flux:input type="date" wire:model.live="requestedDate" />
                    <flux:error name="requestedDate" />
                </flux:field>

                <flux:field>
                    <flux:label>Compañero (Swap with)</flux:label>
                    <flux:select wire:model.live="recipientId" placeholder="Selecciona un compañero...">
                        @foreach($peers as $peer)
                            <flux:select.option value="{{ $peer->id }}">
                                {{ $peer->first_name }} {{ $peer->last_name }} ({{ $peer->team->name ?? 'Sin Equipo' }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="recipientId" />
                    <flux:description>Solo se muestran Operadores I de otros equipos.</flux:description>
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Motivo (Opcional)</flux:label>
                <flux:textarea wire:model="reason" placeholder="Describe brevemente el motivo del cambio..." />
                <flux:error name="reason" />
            </flux:field>

            {{-- Cards de Horario --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                {{-- Mi Horario --}}
                <div class="space-y-2">
                    <flux:label>Tu Horario</flux:label>
                    <flux:card @class([
                        'p-4 bg-zinc-50 dark:bg-zinc-900/50',
                        'border-red-200 dark:border-red-900/50' => !$requesterAssignment && $requestedDate
                    ])>
                        @if($requesterAssignment)
                            <div class="space-y-3">
                                <div class="flex items-center gap-2 text-sm font-bold text-primary-600">
                                    <flux:icon name="clock" size="sm" />
                                    <span>{{ \Illuminate\Support\Carbon::parse($requesterAssignment->start_time)->format('H:i') }}
                                        -
                                        {{ \Illuminate\Support\Carbon::parse($requesterAssignment->end_time)->format('H:i') }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div
                                        class="text-xs p-2 rounded bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300">
                                        <div class="flex items-center gap-1 font-semibold mb-1">
                                            <flux:icon name="fire" size="xs" />
                                            <span>Almuerzo</span>
                                        </div>
                                        {{ \Illuminate\Support\Carbon::parse($requesterAssignment->lunch_start_time)->format('H:i') }}
                                    </div>
                                    <div
                                        class="text-xs p-2 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300">
                                        <div class="flex items-center gap-1 font-semibold mb-1">
                                            <flux:icon name="bolt" size="xs" />
                                            <span>Break</span>
                                        </div>
                                        {{ \Illuminate\Support\Carbon::parse($requesterAssignment->break_start_time)->format('H:i') }}
                                    </div>
                                </div>
                                <p class="text-[10px] text-zinc-500 uppercase font-bold">
                                    {{ $requesterAssignment->schedule->name }}</p>
                            </div>
                        @else
                            <div class="py-4 text-center opacity-50">
                                <flux:icon name="calendar" size="sm" class="mx-auto mb-2" />
                                <p class="text-xs">No tienes turno este día</p>
                            </div>
                        @endif
                    </flux:card>
                </div>

                {{-- Horario del Compañero --}}
                <div class="space-y-2">
                    <flux:label>Horario del Compañero</flux:label>
                    <flux:card @class([
                        'p-4 bg-zinc-50 dark:bg-zinc-900/50',
                        'border-red-200 dark:border-red-900/50' => !$recipientAssignment && $recipientId
                    ])>
                        @if($recipientAssignment)
                            <div class="space-y-3">
                                <div class="flex items-center gap-2 text-sm font-bold text-zinc-700 dark:text-zinc-300">
                                    <flux:icon name="clock" size="sm" />
                                    <span>{{ \Illuminate\Support\Carbon::parse($recipientAssignment->start_time)->format('H:i') }}
                                        -
                                        {{ \Illuminate\Support\Carbon::parse($recipientAssignment->end_time)->format('H:i') }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div
                                        class="text-xs p-2 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                        <div class="flex items-center gap-1 font-semibold mb-1">
                                            <flux:icon name="fire" size="xs" />
                                            <span>Almuerzo</span>
                                        </div>
                                        {{ \Illuminate\Support\Carbon::parse($recipientAssignment->lunch_start_time)->format('H:i') }}
                                    </div>
                                    <div
                                        class="text-xs p-2 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                        <div class="flex items-center gap-1 font-semibold mb-1">
                                            <flux:icon name="bolt" size="xs" />
                                            <span>Break</span>
                                        </div>
                                        {{ \Illuminate\Support\Carbon::parse($recipientAssignment->break_start_time)->format('H:i') }}
                                    </div>
                                </div>
                                <p class="text-[10px] text-zinc-500 uppercase font-bold">
                                    {{ $recipientAssignment->schedule->name }}</p>
                            </div>
                        @else
                            <div class="py-4 text-center opacity-50">
                                <flux:icon name="user" size="sm" class="mx-auto mb-2" />
                                <p class="text-xs">
                                    {{ $recipientId ? 'El compañero no tiene turno este día' : 'Selecciona un compañero' }}
                                </p>
                            </div>
                        @endif
                    </flux:card>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-4 border-t dark:border-zinc-700">
                <flux:button type="submit" variant="primary">Enviar Solicitud</flux:button>
                <flux:button href="{{ route('schedules.my-schedule') }}" wire:navigate variant="subtle">Cancelar
                </flux:button>
            </div>
        </form>
    </flux:card>

    <flux:callout variant="warning" class="mt-4">
        <flux:heading>Importante</flux:heading>
        Esta solicitud debe ser aceptada por tu compañero y posteriormente aprobada por un coordinador para hacerse
        efectiva. Ambos deben tener turnos programados y distintos para que el swap sea válido.
    </flux:callout>
</div>