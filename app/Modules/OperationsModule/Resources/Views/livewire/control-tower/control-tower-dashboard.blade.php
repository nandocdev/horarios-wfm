<div>
    {{-- ROW 1: Header --}}
    <livewire:operations.control-tower.header-widget
        :selected-date="$selectedDate"
        :scope="$scope"
        :refresh-interval="$refreshInterval"
        :today-label="$todayLabel"
        :current-time="$currentTime"
        :greeting="$greeting"
        :display-name="$displayName"
        :role-label="$roleLabel"
        :role="$role"
        :teams="$teams"
        :team-id="$teamId"
        :key="'header'"
    />

    {{-- ROW 2: Hero 6-pack --}}
    <livewire:operations.control-tower.hero-stats-widget
        :employee-ids="$employeeIds"
        :selected-date="$selectedDate"
        :key="'hero'"
        wire:key="hero-widget"
    />

    {{-- ROW 3: Estado Operacional (2/3) | Alertas (1/3) --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
        <div class="xl:col-span-2">
            <livewire:operations.control-tower.operational-status-widget
                :employee-ids="$employeeIds"
                :key="'ops-status'"
                wire:key="ops-status-widget"
            />
        </div>
        <div class="xl:col-span-1">
            <livewire:operations.control-tower.alert-feed-widget
                :employee-ids="$employeeIds"
                :key="'alerts'"
                wire:key="alerts-widget"
            />
        </div>
    </div>

    {{-- ROW 4: Occupancy por Hora | SLA/ASA --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
        <livewire:operations.control-tower.occupancy-chart-widget
            :employee-ids="$employeeIds"
            :selected-date="$selectedDate"
            :key="'occupancy'"
            wire:key="occupancy-widget"
        />
        <livewire:operations.control-tower.sla-asa-chart-widget
            :employee-ids="$employeeIds"
            :selected-date="$selectedDate"
            :key="'sla-asa'"
            wire:key="sla-asa-widget"
        />
    </div>

    {{-- ROW 5: Adherencia Heatmap | Cobertura --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
        <livewire:operations.control-tower.adherence-heatmap-widget
            :employee-ids="$employeeIds"
            :selected-date="$selectedDate"
            :key="'heatmap'"
            wire:key="heatmap-widget"
        />
        <livewire:operations.control-tower.coverage-matrix-widget
            :employee-ids="$employeeIds"
            :selected-date="$selectedDate"
            :key="'coverage'"
            wire:key="coverage-widget"
        />
    </div>

    {{-- ROW 6: Forecast vs Real (2/3) | Timeline del día (1/3) --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
        <div class="xl:col-span-2">
            <livewire:operations.control-tower.forecast-comparison-widget
                :selected-date="$selectedDate"
                :key="'forecast'"
                wire:key="forecast-widget"
            />
        </div>
        <div class="xl:col-span-1">
            <livewire:operations.control-tower.timeline-widget
                :selected-date="$selectedDate"
                :key="'timeline'"
                wire:key="timeline-widget"
            />
        </div>
    </div>

    {{-- ROW 7: Equipos (1/2) | Colas (1/2) --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
        <livewire:operations.control-tower.team-performance-widget
            :employee-ids="$employeeIds"
            :selected-date="$selectedDate"
            :key="'teams'"
            wire:key="teams-widget"
        />
        <livewire:operations.control-tower.queue-table-widget
            :employee-ids="$employeeIds"
            :selected-date="$selectedDate"
            :key="'queues'"
            wire:key="queues-widget"
        />
    </div>

    {{-- ROW 8: Pendientes (1/3) | Actividad Reciente (1/3) | Notificaciones (1/3) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
        <livewire:operations.control-tower.pending-approvals-widget
            :key="'pending'"
            wire:key="pending-widget"
        />
        <livewire:operations.control-tower.activity-feed-widget
            :selected-date="$selectedDate"
            :key="'activity'"
            wire:key="activity-widget"
        />
        <livewire:operations.control-tower.notification-center-widget
            :employee-ids="$employeeIds"
            :selected-date="$selectedDate"
            :key="'notifications'"
            wire:key="notifications-widget"
        />
    </div>
</div>
