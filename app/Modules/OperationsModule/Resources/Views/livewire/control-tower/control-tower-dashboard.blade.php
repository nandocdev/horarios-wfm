<div>
    {{-- Header --}}
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

    {{-- Hero KPIs --}}
    <livewire:operations.control-tower.hero-stats-widget
        :employee-ids="$employeeIds"
        :selected-date="$selectedDate"
        :key="'hero'"
        wire:key="hero-widget"
    />

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
        {{-- Estado Operacional --}}
        <div class="xl:col-span-2">
            <livewire:operations.control-tower.operational-status-widget
                :employee-ids="$employeeIds"
                :key="'ops-status'"
                wire:key="ops-status-widget"
            />
        </div>

        {{-- Alertas --}}
        <div class="xl:col-span-1">
            <livewire:operations.control-tower.alert-feed-widget
                :employee-ids="$employeeIds"
                :key="'alerts'"
                wire:key="alerts-widget"
            />
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
        {{-- Occupancy por hora --}}
        <livewire:operations.control-tower.occupancy-chart-widget
            :employee-ids="$employeeIds"
            :selected-date="$selectedDate"
            :key="'occupancy-chart'"
            wire:key="occupancy-chart-widget"
        />

        {{-- SLA + ASA combinado --}}
        <livewire:operations.control-tower.sla-asa-chart-widget
            :employee-ids="$employeeIds"
            :selected-date="$selectedDate"
            :key="'sla-asa'"
            wire:key="sla-asa-widget"
        />
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
        {{-- Cobertura --}}
        <livewire:operations.control-tower.coverage-matrix-widget
            :employee-ids="$employeeIds"
            :selected-date="$selectedDate"
            :key="'coverage'"
            wire:key="coverage-widget"
        />

        {{-- Forecast vs Real --}}
        <livewire:operations.control-tower.forecast-comparison-widget
            :selected-date="$selectedDate"
            :key="'forecast'"
            wire:key="forecast-widget"
        />
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
        {{-- Equipos --}}
        <livewire:operations.control-tower.team-performance-widget
            :employee-ids="$employeeIds"
            :selected-date="$selectedDate"
            :key="'teams'"
            wire:key="teams-widget"
        />

        {{-- Pendientes + Actividad Reciente --}}
        <div class="grid grid-cols-1 gap-6">
            <livewire:operations.control-tower.pending-approvals-widget
                :key="'pending'"
                wire:key="pending-widget"
            />
            <livewire:operations.control-tower.activity-feed-widget
                :selected-date="$selectedDate"
                :key="'activity'"
                wire:key="activity-widget"
            />
        </div>
    </div>
</div>
