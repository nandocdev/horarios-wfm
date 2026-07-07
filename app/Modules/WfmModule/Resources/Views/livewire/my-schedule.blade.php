<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Mi Horario Semanal</flux:heading>
            <flux:subheading>Visualiza tus turnos y actividades programadas</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:dropdown>
                <flux:button icon-trailing="chevron-down">
                    {{ $currentWeek ? 'Semana del ' . $currentWeek->week_start_date->format('d M') : 'Seleccionar Semana' }}
                </flux:button>

                <flux:menu>
                    @foreach($weeks as $week)
                        <flux:menu.item wire:click="selectWeek({{ $week->id }})">
                            Semana del {{ $week->week_start_date->format('d M, Y') }}
                            @if($week->status === 'published')
                                <flux:badge size="sm" variant="success" class="ml-2">Publicado</flux:badge>
                            @else
                                <flux:badge size="sm" variant="subtle" class="ml-2">Borrador</flux:badge>
                            @endif
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    @if(!$currentWeek)
        <flux:card class="flex flex-col items-center justify-center p-12 text-center">
            <flux:icon name="calendar" class="mb-4 size-12 text-slate-400" />
            <flux:heading>No hay semanas planificadas</flux:heading>
            <flux:subheading>Contacta a tu coordinador de WFM para más información.</flux:subheading>
        </flux:card>
    @else
        {{-- Fila de los 7 días --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-7">
            @foreach($days as $num => $name)
                @php
                    $assignment = $assignments[$num] ?? null;
                    $currentDate = $currentWeek->week_start_date->copy()->addDays($num - 1);
                    $dayException = $exceptions->first(function($ex) use ($currentDate) {
                        return $currentDate->between($ex->start_at->startOfDay(), $ex->end_at->endOfDay());
                    });

                    $isToday = \Illuminate\Support\Carbon::now()->toDateString() === $currentDate->toDateString();
                    $isSelected = (int) $selectedDay === (int) $num;
                @endphp
 
                <flux:card wire:click="selectDay({{ $num }})" @class([
                    'p-4 flex flex-col h-full cursor-pointer transition-opacity duration-150 ease-out',
                    'ring-2 ring-primary-500 shadow-md scale-[1.02] bg-primary-50/30 dark:bg-primary-900/10' => $isSelected,
                    'hover:bg-slate-100 dark:hover:bg-slate-800' => !$isSelected,
                    'bg-slate-50 dark:bg-slate-900/50' => !$isToday && !$assignment && !$isSelected && !$dayException,
                    'border-l-4 border-l-amber-500' => $dayException,
                ])>
                    <div class="mb-4 border-b pb-2 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <span @class([
                                'font-bold',
                                'text-primary-600 dark:text-primary-400' => $isSelected,
                                'text-slate-800 dark:text-slate-200' => !$isSelected,
                            ])>{{ $name }}</span>
                            @if($isToday)
                                <flux:badge size="sm" variant="primary">Hoy</flux:badge>
                            @endif
                        </div>
                        <span class="text-xs text-slate-500">{{ $currentDate->format('d M') }}</span>
                    </div>
 
                    <div class="flex-grow">
                        @if($dayException)
                            <div class="flex flex-col gap-1">
                                <flux:badge :color="$dayException->reason?->color ?? 'amber'" size="sm" class="font-bold text-[9px] uppercase">
                                    {{ $dayException->reason?->name ?? 'EXCEPCIÓN' }}
                                </flux:badge>
                            </div>
                        @elseif($assignment)
                            <div class="flex items-center gap-2 text-xs font-semibold text-slate-900 dark:text-slate-100">
                                <flux:icon name="clock" size="xs" />
                                <span>{{ \Illuminate\Support\Carbon::parse($assignment->start_time)->format('H:i') }}</span>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-2 text-center opacity-40">
                                <flux:icon name="moon" size="xs" />
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endforeach
        </div>
 
        {{-- Sección inferior dividida --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 mt-6">
            {{-- Izquierda: Actividades Programadas --}}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Actividades del {{ $days[$selectedDay] }}</flux:heading>
                    <flux:badge variant="neutral" size="sm">
                        {{ $currentWeek->week_start_date->copy()->addDays($selectedDay - 1)->translatedFormat('d F, Y') }}
                    </flux:badge>
                </div>
 
                <flux:card class="space-y-4">
                    @php
                        $dayAssignment = $assignments[$selectedDay] ?? null;
                        $selectedDate = $currentWeek->week_start_date->copy()->addDays($selectedDay - 1);
                        $selectedDayException = $exceptions->first(function($ex) use ($selectedDate) {
                            return $selectedDate->between($ex->start_at->startOfDay(), $ex->end_at->endOfDay());
                        });
                    @endphp
 
                    @if($selectedDayException || $dayAssignment || $intradayActivities->isNotEmpty())
                        <div class="space-y-4">
                            @if($selectedDayException)
                                <div class="flex items-start gap-4 p-4 rounded-md bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/50 shadow-sm">
                                    <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-md text-amber-600 dark:text-amber-400">
                                        <flux:icon name="exclamation-triangle" size="sm" />
                                    </div>
                                    <div class="flex-grow">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-black uppercase tracking-tight text-amber-700 dark:text-amber-500">
                                                Excepción: {{ $selectedDayException->reason?->name }}
                                            </span>
                                            <flux:badge size="sm" :color="$selectedDayException->reason?->color ?? 'amber'" variant="solid">
                                                ACTIVA
                                            </flux:badge>
                                        </div>
                                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1 font-medium italic">
                                            {{ $selectedDayException->remarks ?? 'Esta ausencia ha sido registrada y sobrepasa tu horario regular.' }}
                                        </p>
                                    </div>
                                </div>
                            @endif

                            @if($dayAssignment)
                                <div class="flex items-start gap-4 p-3 rounded-md bg-slate-50 dark:bg-slate-900/50">
                                    <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-md text-primary-600 dark:text-primary-400">
                                        <flux:icon name="clock" size="sm" />
                                    </div>
                                    <div class="flex-grow">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-bold">Turno Principal</span>
                                            <span class="text-xs text-slate-500">
                                                {{ \Illuminate\Support\Carbon::parse($dayAssignment->start_time)->format('H:i') }} - {{ \Illuminate\Support\Carbon::parse($dayAssignment->end_time)->format('H:i') }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500">{{ $dayAssignment->schedule->name }}</p>
                                    </div>
                                </div>

                                @if($dayAssignment->lunch_start_time)
                                    <div class="flex items-start gap-4 p-3 rounded-md bg-slate-100 dark:bg-slate-900/10">
                                        <div class="p-2 bg-slate-200 dark:bg-slate-900/30 rounded-md text-slate-700 dark:text-slate-400">
                                            <flux:icon name="fire" size="sm" />
                                        </div>
                                        <div class="flex-grow">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-bold">Almuerzo</span>
                                                <span class="text-xs text-slate-700">
                                                    {{ \Illuminate\Support\Carbon::parse($dayAssignment->lunch_start_time)->format('H:i') }} - {{ \Illuminate\Support\Carbon::parse($dayAssignment->lunch_end_time)->format('H:i') }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-slate-500">Receso programado</p>
                                        </div>
                                    </div>
                                @endif

                                @if($dayAssignment->break_start_time)
                                    <div class="flex items-start gap-4 p-3 rounded-md bg-blue-50 dark:bg-blue-900/10">
                                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-md text-blue-600 dark:text-blue-400">
                                            <flux:icon name="bolt" size="sm" />
                                        </div>
                                        <div class="flex-grow">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-bold">Break</span>
                                                <span class="text-xs text-blue-600">
                                                    {{ \Illuminate\Support\Carbon::parse($dayAssignment->break_start_time)->format('H:i') }} - {{ \Illuminate\Support\Carbon::parse($dayAssignment->break_end_time)->format('H:i') }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-blue-500">Receso corto</p>
                                        </div>
                                    </div>
                                @endif
                            @endif

                            {{-- Actividades Adicionales (Intraday) --}}
                            @foreach($intradayActivities as $activity)
                                <div class="flex items-start gap-4 p-3 rounded-md border border-zinc-200 dark:border-slate-700">
                                    <div class="p-2 rounded-md" style="background-color: {{ $activity->activityType->color }}20; color: {{ $activity->activityType->color }}">
                                        <flux:icon name="sparkles" size="sm" />
                                    </div>
                                    <div class="flex-grow">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-bold">{{ $activity->activityType->name }}</span>
                                            <span class="text-xs text-slate-500">
                                                {{ $activity->getRangeStart()?->format('H:i') ?? '—' }} - {{ $activity->getRangeEnd()?->format('H:i') ?? '—' }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-400">{{ $activity->activityType->is_productive ? 'Productiva' : 'No Productiva' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center opacity-50">
                            <flux:icon name="moon" size="lg" class="mb-4" />
                            <flux:heading>Día libre</flux:heading>
                            <flux:subheading>No hay actividades programadas para este día.</flux:subheading>
                        </div>
                    @endif
                </flux:card>
            </div>

            {{-- Derecha: Solicitudes --}}
            <div class="space-y-4">
                <flux:heading size="lg">Solicitudes y Trámites</flux:heading>

                <div class="grid grid-cols-1 gap-4">
                    <a href="{{ route('schedules.swap-request') }}" wire:navigate class="block">
                        <flux:card class="p-4 hover:shadow-md transition-opacity cursor-pointer group">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-md group-hover:bg-primary-100 dark:group-hover:bg-primary-900/30 group-hover:text-primary-600 transition-opacity">
                                    <flux:icon name="arrows-right-left" size="sm" />
                                </div>
                                <div class="flex-grow">
                                    <flux:heading size="sm">Cambio de Turno</flux:heading>
                                    <flux:subheading size="xs">Solicita intercambiar tu turno con un compañero</flux:subheading>
                                </div>
                                <flux:icon name="chevron-right" size="xs" class="text-slate-400" />
                            </div>
                        </flux:card>
                    </a>

                    <a href="{{ route('schedules.leave-request', ['type' => 'quarterly']) }}" wire:navigate class="block">
                        <flux:card class="p-4 hover:shadow-md transition-opacity cursor-pointer group">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-md group-hover:bg-primary-100 dark:group-hover:bg-primary-900/30 group-hover:text-primary-600 transition-opacity">
                                    <flux:icon name="calendar-days" size="sm" />
                                </div>
                                <div class="flex-grow">
                                    <flux:heading size="sm">Permiso Trimestral</flux:heading>
                                    <flux:subheading size="xs">Gestiona tu día de permiso por trimestre</flux:subheading>
                                </div>
                                <flux:icon name="chevron-right" size="xs" class="text-slate-400" />
                            </div>
                        </flux:card>
                    </a>

                    <a href="{{ route('schedules.leave-request', ['type' => 'compensatory']) }}" wire:navigate class="block">
                        <flux:card class="p-4 hover:shadow-md transition-opacity cursor-pointer group">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-md group-hover:bg-primary-100 dark:group-hover:bg-primary-900/30 group-hover:text-primary-600 transition-opacity">
                                    <flux:icon name="sparkles" size="sm" />
                                </div>
                                <div class="flex-grow">
                                    <flux:heading size="sm">Permiso Compensatorio</flux:heading>
                                    <flux:subheading size="xs">Solicita tiempo por horas extra trabajadas</flux:subheading>
                                </div>
                                <flux:icon name="chevron-right" size="xs" class="text-slate-400" />
                            </div>
                        </flux:card>
                    </a>

                    <div class="mt-4 border-t pt-4 dark:border-slate-700">
                        <p class="text-xs text-slate-500">Puedes ver el estado de tus solicitudes en el menú lateral.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
