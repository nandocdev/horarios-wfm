<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-3 sm:p-4 mb-4 shadow-sm">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 sm:gap-4">
        <!-- Title and Time -->
        <div class="flex items-center justify-between lg:justify-start gap-4 sm:gap-6">
            <div class="flex items-center gap-2">
                <flux:icon name="squares-2x2" class="w-5 h-5 text-zinc-500 flex-shrink-0" />
                <h1 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white truncate">Dashboard Operacional</h1>
            </div>
            
            <div class="hidden md:flex items-center gap-3 text-sm text-zinc-600 dark:text-zinc-400">
                <span class="font-medium text-zinc-800 dark:text-zinc-200">Hoy: {{ $todayLabel }}</span>
                <span class="text-zinc-300 dark:text-zinc-600">|</span>
                <flux:icon name="clock" class="w-4 h-4" />
                <span class="font-medium">{{ $currentTime ?? '00:00' }}</span>
                <span class="text-zinc-300 dark:text-zinc-600">|</span>
                <span class="text-xs text-zinc-500">Última actualización hace {{ rand(5, 20) }} s</span>
            </div>

            <div class="flex md:hidden items-center gap-1.5 text-xs text-zinc-500 font-mono">
                <flux:icon name="clock" class="w-3.5 h-3.5" />
                <span>{{ $currentTime ?? '00:00' }}</span>
            </div>
        </div>

        <!-- Controls -->
        <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
            @if (in_array($role, ['wfm', 'chief', 'supervisor']))
                <div class="flex items-center gap-1.5 flex-1 sm:flex-none min-w-[120px]">
                    <span class="text-xs font-semibold text-zinc-500">Scope:</span>
                    <flux:select size="sm" wire:model.live="scope" class="w-full sm:w-28">
                        <option value="all">Todos</option>
                        <option value="team">Por equipo</option>
                    </flux:select>
                </div>
            @endif

            @if ($scope === 'team' && count($teams) > 0)
                <div class="flex items-center gap-1.5 flex-1 sm:flex-none min-w-[140px]">
                    <span class="text-xs font-semibold text-zinc-500">Equipo:</span>
                    <flux:select size="sm" wire:model.live="teamId" class="w-full sm:w-40">
                        <option value="">Seleccionar</option>
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
            @endif

            <div class="flex items-center gap-1.5 flex-1 sm:flex-none">
                <span class="text-xs font-semibold text-zinc-500">Periodo:</span>
                <flux:select size="sm" class="w-full sm:w-24">
                    <option value="hoy">Hoy</option>
                    <option value="ayer">Ayer</option>
                </flux:select>
            </div>

            <div class="flex items-center gap-1.5 flex-1 sm:flex-none">
                <span class="text-xs font-semibold text-zinc-500">Refresh:</span>
                <flux:select size="sm" wire:model.live="refreshInterval" class="w-full sm:w-24">
                    <option value="30">30 s</option>
                    <option value="60">60 s</option>
                    <option value="120">2 min</option>
                </flux:select>
            </div>
            
            <flux:button size="sm" wire:click="$dispatch('control-tower-refresh')" icon="arrow-path" class="!p-1.5" title="Actualizar ahora">
                <span class="sr-only">Actualizar</span>
            </flux:button>

            <x-wfm.tour-button :tour="'operations.control-tower'" />
        </div>
    </div>
</div>
