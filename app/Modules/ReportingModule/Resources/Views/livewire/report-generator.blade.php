<div>
    <flux:heading size="xl">{{ $this->reportTitle }}</flux:heading>
    <flux:separator class="mb-4" />
    <p class="mb-6 text-sm text-zinc-500">{{ $this->reportDescription }}</p>

    <form wire:submit="generate">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <flux:select label="Formato" wire:model="form.format">
                <option value="pdf">PDF</option>
                <option value="xls">XLS (Excel)</option>
            </flux:select>

            <flux:input type="date" label="Fecha desde" wire:model="form.dateFrom" />
            <flux:input type="date" label="Fecha hasta" wire:model="form.dateTo" />

            @if ($category !== 'activities')
                <flux:input type="number" label="ID de Equipo" wire:model="form.teamId" placeholder="Filtrar por equipo (opcional)" />
            @endif

            <flux:input type="number" label="ID de Empleado" wire:model="form.employeeId" placeholder="Filtrar por empleado (opcional)" />

            @if (in_array($category, ['volume', 'performance']))
                <flux:input type="number" label="ID de Cola" wire:model="form.queueId" placeholder="Filtrar por cola (opcional)" />
            @endif

            @if ($subReport === 'interval')
                <flux:select label="Intervalo" wire:model="form.interval">
                    <option value="daily">Diario</option>
                    <option value="weekly">Semanal</option>
                    <option value="monthly">Mensual</option>
                </flux:select>
            @endif
        </div>

        <div class="mt-6">
            <flux:button type="submit" variant="primary">Generar Reporte</flux:button>
        </div>

        <flux:error />
    </form>
</div>
