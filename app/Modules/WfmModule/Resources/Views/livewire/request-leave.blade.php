<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <flux:heading size="xl">Solicitar Permiso {{ $type === 'quarterly' ? 'Trimestral' : 'Compensatorio' }}</flux:heading>
        @if($type === 'quarterly')
            <flux:subheading>Puedes solicitar hasta 8 horas por trimestre (Ene-Mar, Abr-Jun, Jul-Sep, Oct-Dic).</flux:subheading>
        @else
            <flux:subheading>Solicita tiempo libre a cambio de tus horas extra trabajadas.</flux:subheading>
        @endif
    </div>

    @if($type === 'quarterly')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="flex flex-col items-center justify-center p-4 text-center">
                <span class="text-xs text-zinc-500 uppercase font-bold mb-1">Saldo Disponible</span>
                <span class="text-3xl font-black text-primary-600">{{ round($availableMinutes / 60, 1) }}h</span>
                <span class="text-[10px] text-zinc-400">de 8.0h totales</span>
            </flux:card>

            <flux:card class="flex flex-col items-center justify-center p-4 text-center">
                <span class="text-xs text-zinc-500 uppercase font-bold mb-1">Uso en el Trimestre</span>
                <span class="text-3xl font-black text-zinc-400">{{ round($usedMinutes / 60, 1) }}h</span>
                <span class="text-[10px] text-zinc-400">Trimestre: {{ now()->startOfQuarter()->format('M') }} - {{ now()->endOfQuarter()->format('M Y') }}</span>
            </flux:card>
        </div>
    @endif

    <flux:card>
        <form wire:submit="submit" class="space-y-6">
            @if($errors->has('general'))
                <flux:callout variant="danger">
                    {{ $errors->first('general') }}
                </flux:callout>
            @endif

            <flux:field>
                <flux:label>Tipo de Permiso</flux:label>
                <flux:select wire:model.live="type">
                    <flux:select.option value="quarterly">Permiso Trimestral (8h)</flux:select.option>
                    <flux:select.option value="compensatory">Permiso Compensatorio</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Fecha del Permiso</flux:label>
                <flux:input type="date" wire:model.live="date" />
                <flux:error name="date" />
            </flux:field>

            <div class="flex items-center gap-4 py-2">
                <flux:checkbox wire:model.live="isFullDay" label="Permiso por jornada completa" />
            </div>

            @if(!$isFullDay)
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Hora Inicio</flux:label>
                        <flux:input type="time" wire:model="startTime" />
                        <flux:error name="startTime" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Hora Fin</flux:label>
                        <flux:input type="time" wire:model="endTime" />
                        <flux:error name="endTime" />
                    </flux:field>
                </div>
            @endif

            <flux:field>
                <flux:label>Motivo</flux:label>
                <flux:textarea wire:model="reason" placeholder="Justifica brevemente tu solicitud..." />
                <flux:error name="reason" />
            </flux:field>

            <div class="flex items-center gap-2 pt-4 border-t dark:border-zinc-700">
                <flux:button type="submit" variant="primary">Enviar Solicitud</flux:button>
                <flux:button href="{{ route('schedules.my-schedule') }}" wire:navigate variant="subtle">Cancelar</flux:button>
            </div>
        </form>
    </flux:card>

    <flux:callout variant="secondary" class="mt-4">
        <flux:heading>Proceso de Aprobación</flux:heading>
        Esta solicitud será enviada a tu jefe inmediato para su revisión. Una vez aprobada, se reflejará en tu horario.
    </flux:callout>
</div>
