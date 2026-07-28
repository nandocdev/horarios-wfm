<div>
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $greeting }}, {{ $displayName }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $roleLabel }} · {{ $todayLabel }}</p>
            </div>
        </div>

        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                <flux:icon name="clock" class="w-4 h-4" />
                <span>{{ $serverTime }}</span>
                <span class="text-zinc-300 dark:text-zinc-600">|</span>
                <span class="text-xs text-zinc-400">Actualizado hace {{ rand(3, 15) }}s</span>
            </div>

            @if (in_array($role, ['wfm', 'chief']))
                <flux:select size="sm" wire:model.live="scope" class="w-32">
                    <option value="all">Todos</option>
                    <option value="team">Por equipo</option>
                </flux:select>
            @endif

            @if ($scope === 'team' && count($teams) > 0)
                <flux:select size="sm" wire:model.live="teamId" class="w-44">
                    <option value="">Seleccionar equipo</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </flux:select>
            @endif

            <flux:select size="sm" wire:model.live="refreshInterval" class="w-28">
                <option value="30">30 s</option>
                <option value="60">60 s</option>
                <option value="120">2 min</option>
                <option value="300">5 min</option>
            </flux:select>

            <flux:button size="sm" wire:click="$dispatch('control-tower-refresh')" icon="arrow-path" class="!p-2">
                <span class="sr-only">Actualizar</span>
            </flux:button>
        </div>
    </div>
</div>
