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
                @foreach($thresholds as $index => $setting)
                    <div class="flex items-center gap-4">
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
                        <div class="flex-grow">
                            <flux:text font="bold">{{ $labelName }}</flux:text>
                            <flux:text size="xs" class="text-zinc-500 italic">
                                {{ $setting['description'] }}
                            </flux:text>
                        </div>
                        <div class="w-32">
                            <flux:input 
                                wire:model="thresholds.{{ $index }}.display_value" 
                                type="number"
                                size="sm"
                                suffix="{{ $setting['unit'] === 'segundos' ? 's' : 'm' }}"
                            />
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>

        <div class="space-y-6">
            {{-- Metas de KPIs --}}
            <flux:card class="space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Metas de KPIs</flux:heading>
                    
                    <flux:modal.trigger name="add-goal">
                        <flux:button size="sm" icon="plus" variant="ghost">Agregar Meta</flux:button>
                    </flux:modal.trigger>
                </div>
                
                <flux:separator />

                <div class="space-y-6">
                    @foreach($kpiGoals as $index => $goal)
                        <div class="flex items-center gap-4">
                            @php
                                $labels = [
                                    'goal_adherence' => 'Meta de Adherencia',
                                    'goal_productivity' => 'Meta de Productividad',
                                    'goal_utilization' => 'Meta de Utilización',
                                    'goal_service_level' => 'Meta de Nivel de Servicio',
                                ];
                                $labelName = $labels[$goal['key']] ?? $goal['description'];
                                $isSystemGoal = in_array($goal['key'], ['goal_adherence', 'goal_productivity', 'goal_utilization', 'goal_service_level']);
                            @endphp
                            <div class="flex-grow">
                                <flux:text font="bold">{{ $labelName }}</flux:text>
                                <flux:text size="xs" class="text-zinc-500 italic">
                                    {{ $goal['key'] }}
                                </flux:text>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-24">
                                    <flux:input 
                                        wire:model="kpiGoals.{{ $index }}.display_value" 
                                        type="number"
                                        size="sm"
                                        suffix="{{ $goal['unit'] }}"
                                    />
                                </div>
                                
                                @if(!$isSystemGoal)
                                    <flux:button 
                                        wire:click="removeGoal({{ $goal['id'] }})"
                                        wire:confirm="¿Estás seguro de eliminar esta meta?"
                                        variant="ghost" 
                                        size="sm" 
                                        icon="trash" 
                                        inset="top bottom"
                                    />
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </flux:card>

            {{-- Metas de AHT --}}
            <flux:card class="space-y-4">
                <flux:heading size="lg">Metas de AHT por Cola</flux:heading>
                <flux:separator />

                <div class="space-y-6 max-h-[400px] overflow-y-auto pr-2">
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

    {{-- Modal para agregar meta --}}
    <flux:modal name="add-goal" class="md:w-[450px] space-y-6">
        <div>
            <flux:heading size="lg">Agregar Nueva Meta de KPI</flux:heading>
            <flux:subheading>Define un identificador único y un nombre descriptivo para la nueva meta.</flux:subheading>
        </div>

        <div class="space-y-4">
            <flux:input wire:model="newGoalKey" label="Identificador (ej: occupancy)" placeholder="occupancy" />
            <flux:input wire:model="newGoalLabel" label="Nombre de la Meta (ej: Meta de Ocupación)" placeholder="Meta de Ocupación" />
        </div>

        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Cancelar</flux:button>
            </flux:modal.close>
            <flux:button wire:click="addGoal" variant="primary">Agregar Meta</flux:button>
        </div>
    </flux:modal>
</div>
