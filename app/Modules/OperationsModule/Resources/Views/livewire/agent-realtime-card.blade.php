<div wire:poll.15s>
    {{-- Card de Estado Real --}}
    <flux:card class="relative overflow-hidden group shadow-sm border border-slate-200 bg-white rounded-md">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <flux:icon icon="signal" class="size-20" />
        </div>
        
        <div class="flex items-center justify-between mb-4">
            <flux:text size="xs" class="uppercase font-semibold text-slate-500 tracking-wider">Tiempo Real</flux:text>
            <flux:badge size="sm" :color="$adherence->color" variant="subtle" class="animate-pulse rounded-md">
                {{ $adherence->label }}
            </flux:badge>
        </div>
        
        <div class="flex items-center gap-4">
            <div class="size-12 rounded-md flex items-center justify-center shadow-inner" style="background-color: {{ ($realtime->color_hex ?? '#6b7280') . '20' }}">
                <flux:icon icon="user-circle" class="size-8" style="color: {{ $realtime->color_hex ?? '#6b7280' }}" />
            </div>
            
            <div>
                <flux:heading size="lg" class="font-bold">
                    {{ $realtime->display_name ?? 'Desconectado' }}
                </flux:heading>
                <flux:text size="sm" class="font-mono text-slate-700">
                    {{ ($realtime->last_changed_at ?? null) ? \Carbon\Carbon::parse($realtime->last_changed_at)->diffForHumans() : 'Sin actividad reciente' }}
                </flux:text>
            </div>
        </div>

        <div class="mt-8 pt-8 border-t border-slate-200 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <flux:text size="xs" class="uppercase font-bold text-slate-400">Actividad Planificada</flux:text>
                <flux:text size="xs" class="font-mono text-slate-500">{{ now()->format('H:i') }}</flux:text>
            </div>
            <flux:heading size="sm" class="mt-1 text-slate-700">
                {{ $expected['label'] ?? 'Sin actividad' }}
            </flux:heading>
            <flux:text size="xs" class="mt-1 text-slate-700">
                {{ $adherence->description }}
            </flux:text>
        </div>
    </flux:card>
</div>
