<div>
    @if($saved)
        <div class="flex items-center justify-center min-h-[200px]">
            <div class="text-center space-y-3">
                <flux:icon.check-circle class="w-12 h-12 text-wfm-success mx-auto" />
                <p class="text-lg font-semibold text-wfm-navy-800 dark:text-white">Llamada Registrada</p>
                <p class="text-sm text-wfm-surface-muted">El registro se ha creado correctamente.</p>
            </div>
        </div>
    @else
        <form wire:submit="save" class="space-y-4">
            @if($isAuthenticated)
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-wfm-surface-muted">Agente: {{ auth()->user()->name }}</p>
                </div>
            @endif

            @if($errorMessage)
                <div class="p-3 bg-wfm-danger/10 border border-wfm-danger/20 rounded-md text-xs text-wfm-danger">
                    {{ $errorMessage }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4">
                <flux:field>
                    <flux:label>Cola / Servicio</flux:label>
                    <flux:select wire:model.live="queueId" placeholder="Selecciona cola...">
                        @foreach(\App\Modules\ConnectModule\Models\CallQueue::active()->orderBy('name')->get() as $q)
                            <flux:select.option value="{{ $q->id }}">{{ $q->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Teléfono</flux:label>
                    <flux:input wire:model="telefono" placeholder="Ej. 22334455" />
                </flux:field>

                @if(!auth()->check())
                    <flux:field>
                        <flux:label>Usuario (username)</flux:label>
                        <flux:input wire:model="username" placeholder="Username del agente" />
                        <p class="text-[10px] text-wfm-surface-muted mt-1">Usuario del empleado al que se asociará la llamada.</p>
                    </flux:field>
                @endif
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-wfm-surface-border">
                <flux:button type="submit" variant="primary" icon="check">Guardar Registro</flux:button>
            </div>
        </form>
    @endif
</div>
