<div>
    <flux:heading size="xl">Generador de Reportes</flux:heading>

    <flux:separator class="mb-6" />

    <form wire:submit="generate">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <flux:select label="Tipo de reporte" wire:model="form.reportType">
                <option value="absenteeism-raw">Ausentismo — Detalle</option>
                <option value="absenteeism-exceptions">Ausentismo — Resumen por Causa</option>
                <option value="aht-detail">AHT — Detallado por Agente</option>
                <option value="aht-summary">AHT — Resumen por Cola</option>
                <option value="volume-detail">Volumen — Detalle por Cola</option>
                <option value="volume-summary">Volumen — Resumen por Cola</option>
            </flux:select>

            <flux:select label="Formato" wire:model="form.format">
                <option value="pdf">PDF</option>
                <option value="xls">XLS (Excel)</option>
            </flux:select>

            <flux:input type="date" label="Fecha desde" wire:model="form.dateFrom" />

            <flux:input type="date" label="Fecha hasta" wire:model="form.dateTo" />

            <flux:input type="number" label="ID de Equipo (opcional)" wire:model="form.teamId" placeholder="Filtrar por equipo" />

            <flux:input type="number" label="ID de Empleado (opcional)" wire:model="form.employeeId" placeholder="Filtrar por empleado" />

            <flux:input type="number" label="ID de Cola (opcional)" wire:model="form.queueId" placeholder="Filtrar por cola" />

            <flux:select label="Intervalo (opcional)" wire:model="form.interval">
                <option value="daily">Diario</option>
                <option value="weekly">Semanal</option>
                <option value="monthly">Mensual</option>
            </flux:select>
        </div>

        <div class="mt-6">
            <flux:button type="submit" variant="primary">
                Generar Reporte
            </flux:button>
        </div>

        <flux:error />
    </form>
</div>
