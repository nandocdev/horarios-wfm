@props([
    'category' => 'attendance',
    'subReport' => 'absenteeism',
    'employeeOptions' => [],
])

<div class="rounded-lg border border-zinc-200 bg-white p-5">
    <div class="flex items-center gap-2 mb-4">
        <flux:icon name="funnel" variant="micro" class="text-zinc-400" />
        <span class="text-sm font-semibold text-zinc-700">Filtros</span>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <div>
            <label class="mb-1 block text-xs font-medium text-zinc-500">Fecha desde</label>
            <flux:input type="date" wire:model="form.dateFrom" />
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-zinc-500">Fecha hasta</label>
            <flux:input type="date" wire:model="form.dateTo" />
        </div>

        @if ($category !== 'activities')
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-500">Equipo</label>
                <flux:select wire:model.live="form.teamId" placeholder="Seleccionar equipo">
                    @foreach ($this->teams as $team)
                        <option value="{{ $team['id'] }}">{{ $team['name'] }}</option>
                    @endforeach
                </flux:select>
            </div>
        @endif

        <div>
            <label class="mb-1 block text-xs font-medium text-zinc-500">Empleado</label>
            <flux:select wire:model="form.employeeId" placeholder="Seleccionar empleado">
                @foreach ($employeeOptions as $emp)
                    <option value="{{ $emp['id'] }}">{{ $emp['name'] }}</option>
                @endforeach
            </flux:select>
        </div>

        @if (in_array($category, ['volume', 'performance']))
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-500">Cola</label>
                <flux:input type="number" wire:model="form.queueId" placeholder="Filtrar por cola" />
            </div>
        @endif

        @if ($subReport === 'interval')
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-500">Intervalo</label>
                <flux:select wire:model="form.interval">
                    <option value="daily">Diario</option>
                    <option value="weekly">Semanal</option>
                    <option value="monthly">Mensual</option>
                </flux:select>
            </div>
        @endif
    </div>

    <flux:error class="mt-3" />
</div>
