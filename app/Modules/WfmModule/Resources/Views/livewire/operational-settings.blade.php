<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Configuración Operativa</flux:heading>
            <flux:subheading>Define los umbrales de alerta y metas de rendimiento para el monitoreo en tiempo real.</flux:subheading>
        </div>

        <flux:button wire:click="save" variant="primary" icon="check">
            Guardar Cambios
        </flux:button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Umbrales Globales --}}
        <flux:card class="space-y-4">
            <flux:heading size="lg">Umbrales de Adherencia</flux:heading>
            <flux:separator />

            <div class="space-y-6">
                @foreach($settings as $index => $setting)
                    <div class="flex flex-col gap-2">
                        @php
                            $labels = [
                                'late_login_grace_period' => 'Tolerancia de Tardanza (Tiempo de Gracia)',
                                'personal_time_threshold' => 'Umbral Crítico de Tiempo Auxiliar',
                                'adherence_alert_threshold' => 'Tolerancia de Fuera de Adherencia',
                                'default_lunch_minutes' => 'Duración Estándar de Almuerzo',
                                'default_break_minutes' => 'Duración Estándar de Descanso',
                                'stuck_reserved_threshold' => 'Umbral de Reserved Pegado',
                                'long_talking_threshold' => 'Umbral de Llamada Prolongada',
                                'overtime_threshold' => 'Umbral de Tiempo Extra Excedido',
                            ];
                            $labelName = $labels[$setting['key']] ?? ucwords(str_replace('_', ' ', $setting['key']));
                        @endphp
                        <flux:input 
                            wire:model="settings.{{ $index }}.display_value" 
                            label="{{ $labelName }}" 
                            type="number"
                            suffix="{{ $setting['unit'] }}"
                        />
                        <flux:text size="xs" class="text-zinc-500 italic">
                            {{ $setting['description'] }}
                        </flux:text>
                    </div>
                @endforeach
            </div>
        </flux:card>

        {{-- Metas de AHT --}}
        <flux:card class="space-y-4">
            <flux:heading size="lg">Metas de AHT por Cola</flux:heading>
            <flux:separator />

            <div class="space-y-6 max-h-[500px] overflow-y-auto pr-2">
                @foreach($queues as $index => $queue)
                    <div class="flex items-center gap-4">
                        <div class="flex-grow">
                            <flux:text font="bold">{{ $queue['name'] }}</flux:text>
                        </div>
                        <div class="w-32">
                            <flux:input 
                                wire:model="queues.{{ $index }}.aht_goal" 
                                type="number"
                                size="sm"
                                suffix="s"
                            />
                        </div>
                    </div>
                @endforeach

                @if(empty($queues))
                    <div class="py-10 text-center">
                        <flux:text size="sm">No hay colas configuradas.</flux:text>
                    </div>
                @endif
            </div>
        </flux:card>
    </div>
</div>
