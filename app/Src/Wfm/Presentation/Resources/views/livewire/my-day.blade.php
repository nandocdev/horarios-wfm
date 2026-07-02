<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Mi Día</flux:heading>
            <flux:subheading>Estado actual y actividades del agente.</flux:subheading>
        </div>
        <flux:button wire:click="refreshState" variant="ghost" icon="arrow-path" size="sm">
            Actualizar
        </flux:button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <flux:card>
            <div class="text-center py-4">
                <flux:heading size="2xl" style="color: {{ $state['color'] ?? '#6b7280' }}">
                    ●
                </flux:heading>
                <flux:heading size="lg" class="mt-2">{{ $state['label'] ?? '—' }}</flux:heading>
                <flux:text size="sm" class="text-zinc-500">{{ $state['type'] ?? 'OFF' }}</flux:text>
            </div>
        </flux:card>

        <flux:card>
            <div class="text-center py-4">
                <flux:heading size="lg">{{ $employee?->full_name ?? '—' }}</flux:heading>
                <flux:text size="sm" class="text-zinc-500">{{ $employee?->employee_number ?? '—' }}</flux:text>
            </div>
        </flux:card>
    </div>
</div>
