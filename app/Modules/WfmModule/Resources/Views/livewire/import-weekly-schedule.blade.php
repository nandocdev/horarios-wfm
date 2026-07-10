<div class="p-6 lg:p-8 max-w-4xl mx-auto flex-1 flex flex-col">
    <div class="flex items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <flux:button href="{{ route('schedules.planning.teams', ['week' => $week->id]) }}" icon="arrow-left" variant="ghost" wire:navigate />
            <div>
                <flux:heading size="xl">{{ __('Importar Horario por CSV') }}</flux:heading>
                <flux:subheading class="mt-2">
                    {{ __('Semana del') }} {{ $week->week_start_date->format('d M') }} {{ __('al') }}
                    {{ $week->week_end_date->format('d M, Y') }}
                </flux:subheading>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <flux:card class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Seleccionar Archivo') }}</flux:heading>
                <flux:subheading>
                    {{ __('Carga un archivo CSV con los horarios de los agentes y define los días correspondientes del periodo semanal.') }}
                </flux:subheading>

            </div>

            <div class="space-y-4">
                <flux:input type="file" wire:model.live="csvFile" accept=".csv" label="{{ __('Archivo CSV') }}" />

                @if(!empty($importedData))
                    <div class="mt-6 border-t pt-6 border-slate-200">
                        <flux:heading size="md" class="mb-2">{{ __('Días a aplicar (Periodo de implementación)') }}</flux:heading>
                        <flux:subheading class="mb-4">
                            {{ __('Selecciona los días específicos de la semana sobre los cuales se escribirá la planificación importada.') }}
                        </flux:subheading>
                        <div class="flex gap-4 flex-wrap">
                            @foreach($days as $dayNum => $dayName)
                                <flux:checkbox wire:model="importSelectedDays" value="{{ $dayNum }}" label="{{ $dayName }}" />
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 border-t pt-6 border-slate-200">
                        <div class="flex justify-between items-center mb-4">
                            <flux:heading size="md">{{ __('Previsualización de Datos Importados') }}</flux:heading>
                            <flux:badge size="sm" variant="neutral">
                                {{ count($importedData) }} {{ __('registros leídos') }}
                            </flux:badge>
                        </div>
                        
                        <div class="overflow-x-auto border border-slate-200 rounded-md max-h-96">
                            <flux:table class="m-4">
                                <flux:table.columns class="sticky top-0 z-10 bg-white">
                                    <flux:table.column>{{ __('Usuario') }}</flux:table.column>
                                    <flux:table.column>{{ __('Jornada') }}</flux:table.column>
                                    <flux:table.column>{{ __('Entrada') }}</flux:table.column>
                                    <flux:table.column>{{ __('Salida') }}</flux:table.column>
                                    <flux:table.column>{{ __('Ini Almuerzo') }}</flux:table.column>
                                    <flux:table.column>{{ __('Fin Almuerzo') }}</flux:table.column>
                                    <flux:table.column>{{ __('Ini Descanso') }}</flux:table.column>
                                    <flux:table.column>{{ __('Fin Descanso') }}</flux:table.column>
                                    <flux:table.column align="end">{{ __('Acciones') }}</flux:table.column>
                                </flux:table.columns>

                                <flux:table.rows>
                                    @foreach($importedData as $index => $row)
                                        <flux:table.row :key="$row['id']" class="hover:bg-slate-50" :class="!$row['user_exists'] ? 'bg-red-50/50' : ''">
                                            <flux:table.cell class="py-1">
                                                <div class="flex flex-col gap-1">
                                                    <flux:text class="w-28" size="sm">{{ strtolower($row['usuario']) }}</flux:text>
                                                    @if(!$row['user_exists'])
                                                        <flux:badge size="sm" variant="danger" icon="exclamation-triangle" class="w-fit">
                                                            {{ __('No encontrado') }}
                                                        </flux:badge>
                                                    @endif
                                                </div>
                                            </flux:table.cell>
                                            <flux:table.cell class="py-1">
                                                <flux:text class="w-32" size="sm">{{ $row['jornada'] }}</flux:text>
                                            </flux:table.cell>
                                            <flux:table.cell class="py-1">
                                                <flux:select wire:model="importedData.{{ $index }}.entrada" size="sm" class="w-44">
                                                    <option value="">{{ __('Seleccione turno...') }}</option>
                                                    @foreach($availableSchedules as $sched)
                                                        <option value="{{ $sched['start_time'] }}">
                                                            {{ $sched['name'] }} ({{ $sched['start_time'] }})
                                                        </option>
                                                    @endforeach
                                                </flux:select>
                                            </flux:table.cell>
                                            <flux:table.cell class="py-1">
                                                <flux:input type="time" wire:model="importedData.{{ $index }}.salida" size="sm" />
                                            </flux:table.cell>
                                            <flux:table.cell class="py-1">
                                                <flux:input type="time" wire:model="importedData.{{ $index }}.ini_almuerzo" size="sm" />
                                            </flux:table.cell>
                                            <flux:table.cell class="py-1">
                                                <flux:input type="time" wire:model="importedData.{{ $index }}.fin_almuerzo" size="sm" />
                                            </flux:table.cell>
                                            <flux:table.cell class="py-1">
                                                <flux:input type="time" wire:model="importedData.{{ $index }}.ini_descanso" size="sm" />
                                            </flux:table.cell>
                                            <flux:table.cell class="py-1">
                                                <flux:input type="time" wire:model="importedData.{{ $index }}.fin_descanso" size="sm" />
                                            </flux:table.cell>
                                            <flux:table.cell align="end" class="py-1">
                                                <flux:button wire:click="removeImportedRow({{ $index }})" variant="danger" size="sm" icon="trash" />
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end gap-3 mt-6 border-t pt-4 border-slate-200">
                    <flux:button variant="ghost" href="{{ route('schedules.planning.teams', ['week' => $week->id]) }}" wire:navigate>
                        {{ __('Cancelar') }}
                    </flux:button>
                    @if(!empty($importedData))
                        <flux:button wire:click="applyImport" variant="primary" icon="check">
                            {{ __('Aplicar Horario') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        </flux:card>
    </div>
</div>
