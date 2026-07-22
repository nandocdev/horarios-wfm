<div>
    <flux:heading size="xl">Reportes</flux:heading>

    <flux:separator class="mb-6" />

    <form wire:submit="generate">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <flux:select label="Categoría" wire:model.live="category">
                <option value="attendance">Asistencia</option>
                <option value="activities">Actividades</option>
                <option value="volume">Volumen</option>
                <option value="performance">Desempeño</option>
            </flux:select>

            <flux:select label="Sub-reporte" wire:model="subReport">
                @if ($category === 'attendance')
                    <option value="absenteeism">Ausentismo</option>
                    <option value="tardiness">Tardanzas</option>
                    <option value="leaves">Permisos</option>
                    <option value="vacations">Vacaciones</option>
                    <option value="summary">Global</option>
                @elseif ($category === 'activities')
                    <option value="intraday">Intradía</option>
                    <option value="period">Por Período</option>
                @elseif ($category === 'volume')
                    <option value="queue">Por Cola</option>
                    <option value="interval">Por Intervalo</option>
                    <option value="summary">Consolidado</option>
                @elseif ($category === 'performance')
                    <option value="agent">Por Agente</option>
                    <option value="team">Por Equipo</option>
                    <option value="ranking">Ranking</option>
                @endif
            </flux:select>

            <flux:select label="Formato" wire:model="form.format">
                <option value="pdf">PDF</option>
                <option value="xls">XLS (Excel)</option>
            </flux:select>

            <flux:input type="date" label="Fecha desde" wire:model="form.dateFrom" />
            <flux:input type="date" label="Fecha hasta" wire:model="form.dateTo" />

            @if ($category !== 'activities')
                <flux:input type="number" label="ID de Equipo (opcional)" wire:model="form.teamId" placeholder="Filtrar por equipo" />
            @endif

            <flux:input type="number" label="ID de Empleado (opcional)" wire:model="form.employeeId" placeholder="Filtrar por empleado" />

            @if (in_array($category, ['volume', 'performance']))
                <flux:input type="number" label="ID de Cola (opcional)" wire:model="form.queueId" placeholder="Filtrar por cola" />
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
