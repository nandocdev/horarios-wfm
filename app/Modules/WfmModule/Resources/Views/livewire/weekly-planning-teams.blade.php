<div class="p-6">
    <div class="flex items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <flux:button href="{{ route('schedules.planning') }}" icon="arrow-left" variant="ghost" wire:navigate />
            <div>
                <flux:heading size="xl">{{ __('Gestión de Equipos') }}</flux:heading>
                <flux:subheading class="mt-2">
                    {{ __('Semana del') }} {{ $week->week_start_date->format('d M') }} {{ __('al') }}
                    {{ $week->week_end_date->format('d M, Y') }}
                </flux:subheading>
            </div>
        </div>
        <flux:button wire:click="$set('showImportModal', true)" variant="primary" icon="document-arrow-up">
            {{ __('Importar Horario') }}
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Equipo') }}</flux:table.column>
            <flux:table.column>{{ __('Turno Base') }}</flux:table.column>
            <flux:table.column>{{ __('Entrada') }}</flux:table.column>
            <flux:table.column>{{ __('Salida') }}</flux:table.column>
            <flux:table.column>{{ __('Lunch') }}</flux:table.column>
            <flux:table.column>{{ __('Break') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Acciones') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($teams as $team)
                <flux:table.row :key="$team->id">
                    <flux:table.cell>
                        <div class="flex flex-col">
                            <span class="font-medium">{{ $team->name }}</span>
                            <span class="text-xs text-zinc-500">{{ $team->supervisor?->full_name }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:select wire:model.live="teamSchedules.{{ $team->id }}"
                            placeholder="{{ __('Seleccionar turno...') }}">
                            <flux:select.option selected readonly value="">{{ __('Sin asignar') }}</flux:select.option>
                            @foreach($schedules as $sched)
                                <flux:select.option value="{{ $sched->id }}">
                                    {{ $sched->name }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:input wire:model="teamStart.{{ $team->id }}" type="time" size="sm" icon="clock" />
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:input wire:model="teamEnd.{{ $team->id }}" type="time" size="sm" icon="clock" />
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:input wire:model="teamLunch.{{ $team->id }}" type="time" size="sm" icon="clock" />
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:input wire:model="teamBreak.{{ $team->id }}" type="time" size="sm" icon="clock" />
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-2">
                            <flux:button wire:click="assignToTeam({{ $team->id }})" variant="primary" color="emerald"
                                size="sm" icon="check">
                                {{ __('Asignar') }}
                            </flux:button>
                            <flux:button
                                href="{{ route('schedules.planning.team', ['week' => $week->id, 'team' => $team->id]) }}"
                                variant="primary" color="blue" size="sm" icon="eye" wire:navigate>
                                {{ __('Ver Detalle') }}
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <!-- Modal de Importación de Horarios -->
    <flux:modal wire:model="showImportModal"
        class="{{ empty($importedData) ? 'min-w-[500px]' : 'min-w-[95vw] lg:min-w-[90vw]' }}">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Importar Horario por CSV') }}</flux:heading>
                <flux:subheading>
                    {{ __('Carga un archivo CSV con los horarios y selecciona los días a aplicar.') }}
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input type="file" wire:model.live="csvFile" accept=".csv" label="{{ __('Archivo CSV') }}" />

                @if(!empty($importedData))
                    <div class="mt-4">
                        <flux:heading size="sm">{{ __('Días a aplicar (Periodo de implementación)') }}</flux:heading>
                        <div class="flex gap-4 mt-2 flex-wrap">
                            @foreach($days as $dayNum => $dayName)
                                <flux:checkbox wire:model="importSelectedDays" value="{{ $dayNum }}" label="{{ $dayName }}" />
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 max-h-96 overflow-y-auto">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>{{ __('Usuario') }}</flux:table.column>
                                <flux:table.column>{{ __('Jornada') }}</flux:table.column>
                                <flux:table.column>{{ __('Entrada') }}</flux:table.column>
                                <flux:table.column>{{ __('Salida') }}</flux:table.column>
                                <flux:table.column>{{ __('Ini Almuerzo') }}</flux:table.column>
                                <flux:table.column>{{ __('Fin Almuerzo') }}</flux:table.column>
                                <flux:table.column>{{ __('Ini Descanso') }}</flux:table.column>
                                <flux:table.column>{{ __('Fin Descanso') }}</flux:table.column>
                                <flux:table.column>{{ __('Acciones') }}</flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach($importedData as $index => $row)
                                    <flux:table.row :key="$row['id']" :class="!$row['user_exists'] ? 'bg-red-50' : ''">
                                        <flux:table.cell>
                                            <div class="flex flex-col gap-1">
                                                <flux:input wire:model="importedData.{{ $index }}.usuario" class="w-24"
                                                    size="sm" />
                                                @if(!$row['user_exists'])
                                                    <flux:badge size="sm" variant="danger" icon="exclamation-triangle">
                                                        {{ __('No encontrado') }}
                                                    </flux:badge>
                                                @endif
                                            </div>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:input wire:model="importedData.{{ $index }}.jornada" class="w-32" size="sm" />
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:input type="time" wire:model="importedData.{{ $index }}.entrada" size="sm" />
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:input type="time" wire:model="importedData.{{ $index }}.salida" size="sm" />
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:input type="time" wire:model="importedData.{{ $index }}.ini_almuerzo"
                                                size="sm" />
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:input type="time" wire:model="importedData.{{ $index }}.fin_almuerzo"
                                                size="sm" />
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:input type="time" wire:model="importedData.{{ $index }}.ini_descanso"
                                                size="sm" />
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:input type="time" wire:model="importedData.{{ $index }}.fin_descanso"
                                                size="sm" />
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:button wire:click="removeImportedRow({{ $index }})" variant="danger" size="sm"
                                                icon="trash" />
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                @endif

                <div class="flex justify-end gap-3 mt-4">
                    <flux:button variant="ghost" wire:click="$set('showImportModal', false)">{{ __('Cancelar') }}
                    </flux:button>
                    @if(!empty($importedData))
                        <flux:button wire:click="applyImport" variant="primary" icon="check">{{ __('Aplicar Horario') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        </div>
    </flux:modal>
</div>