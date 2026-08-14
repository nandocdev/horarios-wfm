<div class="max-w-2xl mx-auto space-y-8 flex-1 flex flex-col">
    <div>
        @php
            $cuatriMonth = (int) now()->month;
            $cuatriStart = (int) (floor(($cuatriMonth - 1) / 4) * 4 + 1);
            $cuatriLabelStart = now()->startOfYear()->addMonths($cuatriStart - 1);
            $cuatriLabelEnd = $cuatriLabelStart->copy()->addMonths(4)->subDay();
        @endphp
        <flux:heading size="xl">Solicitar Permiso {{ $form->type === 'cuatrimestral' ? 'Cuatrimestral' : 'Compensatorio' }}</flux:heading>
        @if($form->type === 'cuatrimestral')
            <flux:subheading>Puedes solicitar hasta 8 horas por cuatrimestre (Ene-Abr, May-Ago, Sep-Dic).</flux:subheading>
        @else
            <flux:subheading>Solicita tiempo libre a cambio de tus horas extra trabajadas.</flux:subheading>
        @endif
    </div>

    @if($form->type === 'cuatrimestral')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="flex flex-col items-center justify-center p-4 text-center">
                <span class="text-xs text-slate-500 uppercase font-bold mb-1">Saldo Disponible</span>
                <span class="text-3xl font-black text-primary-600">{{ round($availableMinutes / 60, 1) }}h</span>
                <span class="text-[10px] text-slate-400">de 8.0h totales</span>
            </flux:card>

            <flux:card class="flex flex-col items-center justify-center p-4 text-center">
                <span class="text-xs text-slate-500 uppercase font-bold mb-1">Uso en el Cuatrimestre</span>
                <span class="text-3xl font-black text-slate-400">{{ round($usedMinutes / 60, 1) }}h</span>
                <span class="text-[10px] text-slate-400">{{ $cuatriLabelStart->format('M') }} - {{ $cuatriLabelEnd->format('M Y') }}</span>
            </flux:card>
        </div>
    @endif

    <flux:card>
        <form wire:submit="submit" class="space-y-4">
            @php
                $remaining = $this->remainingAfterRequest();
                $exceeds = $form->type === 'cuatrimestral' && $remaining < 0;
            @endphp

            @if($exceeds)
                <flux:callout variant="danger" icon="exclamation-triangle">
                    La solicitud excede el saldo disponible de {{ round($availableMinutes / 60, 1) }} horas. Reduce la duración o elige otra fecha.
                </flux:callout>
            @elseif($errors->has('general'))
                <flux:callout variant="danger">
                    {{ $errors->first('general') }}
                </flux:callout>
            @endif

            <flux:field>
                <flux:label>Tipo de Permiso</flux:label>
                <flux:select wire:model.live="form.type">
                    <flux:select.option value="cuatrimestral">Permiso Cuatrimestral (8h)</flux:select.option>
                    <flux:select.option value="compensatory">Permiso Compensatorio</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Fecha del Permiso</flux:label>
                <flux:input type="date" wire:model.live="form.date" />
                <flux:error name="form.date" />
            </flux:field>

            <div class="flex items-center gap-4 py-2">
                <flux:checkbox wire:model.live="form.isFullDay" label="Permiso por jornada completa" />
            </div>

            @if(!$form->isFullDay)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <flux:field>
                        <flux:label>Hora Inicio</flux:label>
                        <flux:input type="time" wire:model="form.startTime" />
                        <flux:error name="form.startTime" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Hora Fin</flux:label>
                        <flux:input type="time" wire:model="form.endTime" />
                        <flux:error name="form.endTime" />
                    </flux:field>
                </div>
            @endif

            <flux:field>
                <flux:label>Motivo</flux:label>
                <flux:textarea wire:model="form.reason" placeholder="Justifica brevemente tu solicitud..." />
                <flux:error name="form.reason" />
            </flux:field>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 pt-4 border-t dark:border-slate-700">
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
