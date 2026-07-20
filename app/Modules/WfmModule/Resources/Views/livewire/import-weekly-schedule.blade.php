<div class="space-y-6">
    <x-wfm.page-header title="Importar Horario por CSV" description="Semana del {{ $week->week_start_date->format('d M') }} al {{ $week->week_end_date->format('d M, Y') }}">
        <x-slot:actions>
            <flux:button href="{{ route('schedules.planning.teams', ['week' => $week->id]) }}" icon="arrow-left" variant="ghost" wire:navigate>Volver</flux:button>
        </x-slot:actions>
    </x-wfm.page-header>

    <x-wfm.section title="Seleccionar Archivo" description="Carga un archivo CSV con los horarios de los agentes y define los días correspondientes del periodo semanal.">
        <div class="space-y-4">
            <flux:input type="file" wire:model.live="csvFile" accept=".csv" label="Archivo CSV" />

            @if(!empty($importedData))
                <div class="border-t border-wfm-surface-border pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-semibold text-wfm-navy-800 dark:text-white">Días a aplicar</p>
                            <p class="text-xs text-wfm-surface-muted">Selecciona los días sobre los cuales se escribirá la planificación.</p>
                        </div>
                    </div>
                    <div class="flex gap-4 flex-wrap mb-4">
                        @foreach($days as $dayNum => $dayName)
                            <flux:checkbox wire:model="importSelectedDays" value="{{ $dayNum }}" label="{{ $dayName }}" />
                        @endforeach
                    </div>

                    <div class="border-t border-wfm-surface-border pt-4">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-sm font-semibold text-wfm-navy-800 dark:text-white">Previsualización</p>
                            <x-wfm.adherence-badge :value="count($importedData)" target="0" size="xs" />
                        </div>

                        <div class="overflow-x-auto border border-wfm-surface-border rounded-md max-h-96">
                            <x-wfm.table :headers="['Usuario', 'Jornada', 'Entrada', 'Salida', 'Ini Almuerzo', 'Fin Almuerzo', 'Ini Descanso', 'Fin Descanso', 'Acciones']" compact>
                                @foreach($importedData as $index => $row)
                                    <flux:table.row :key="$row['id']" :class="!$row['user_exists'] ? 'bg-wfm-danger/5' : ''">
                                        <flux:table.cell>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-xs">{{ strtolower($row['usuario']) }}</span>
                                                @if(!$row['user_exists'])
                                                    <x-wfm.agent-status status="busy" label="No encontrado" size="xs" />
                                                @endif
                                            </div>
                                        </flux:table.cell>
                                        <flux:table.cell class="text-xs">{{ $row['jornada'] }}</flux:table.cell>
                                        <flux:table.cell>
                                            <flux:select wire:model="importedData.{{ $index }}.entrada" size="sm" class="!w-36">
                                                <option value="">Seleccione...</option>
                                                @foreach($availableSchedules as $sched)
                                                    <option value="{{ $sched['start_time'] }}">{{ $sched['name'] }} ({{ $sched['start_time'] }})</option>
                                                @endforeach
                                            </flux:select>
                                        </flux:table.cell>
                                        <flux:table.cell><flux:input type="time" wire:model="importedData.{{ $index }}.salida" size="sm" class="!w-24" /></flux:table.cell>
                                        <flux:table.cell><flux:input type="time" wire:model="importedData.{{ $index }}.ini_almuerzo" size="sm" class="!w-24" /></flux:table.cell>
                                        <flux:table.cell><flux:input type="time" wire:model="importedData.{{ $index }}.fin_almuerzo" size="sm" class="!w-24" /></flux:table.cell>
                                        <flux:table.cell><flux:input type="time" wire:model="importedData.{{ $index }}.ini_descanso" size="sm" class="!w-24" /></flux:table.cell>
                                        <flux:table.cell><flux:input type="time" wire:model="importedData.{{ $index }}.fin_descanso" size="sm" class="!w-24" /></flux:table.cell>
                                        <flux:table.cell>
                                            <flux:button wire:click="removeImportedRow({{ $index }})" variant="ghost" size="sm" icon="trash" />
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </x-wfm.table>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-3 pt-4 border-t border-wfm-surface-border">
                <flux:button variant="ghost" href="{{ route('schedules.planning.teams', ['week' => $week->id]) }}" wire:navigate>
                    Cancelar
                </flux:button>
                @if(!empty($importedData))
                    <flux:button wire:click="applyImport" variant="primary" icon="check" wire:loading.attr="disabled">
                        <span wire:loading.remove>Aplicar Horario</span>
                        <span wire:loading>Aplicando...</span>
                    </flux:button>
                @endif
            </div>
        </div>
    </x-wfm.section>
</div>
