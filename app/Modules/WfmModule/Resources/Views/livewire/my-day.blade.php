<div>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-wfm-navy-900 dark:text-white flex items-center gap-2">
                <flux:icon.sun class="w-6 h-6 text-wfm-info" />
                Mi Jornada
            </h1>
            <p class="text-sm text-wfm-surface-muted mt-1">Resumen de tu actividad y métricas del día seleccionado</p>
        </div>
        <div class="flex items-center gap-2 bg-wfm-surface p-1 rounded-lg border border-wfm-surface-border shadow-sm">
            <flux:button wire:click="previousDay" size="sm" variant="ghost" class="text-wfm-surface-muted hover:text-wfm-navy-900" icon="chevron-left" />
            <div class="flex items-center gap-2 px-3">
                <flux:icon.calendar class="w-4 h-4 text-wfm-surface-muted" />
                <span class="text-sm font-bold text-wfm-navy-900 dark:text-white min-w-[120px] text-center">
                    {{ \Carbon\Carbon::parse($selectedDate)->locale('es')->isoFormat('D MMM YYYY') }}
                </span>
            </div>
            <flux:button wire:click="nextDay" size="sm" variant="ghost" 
                         class="text-wfm-surface-muted hover:text-wfm-navy-900" 
                         icon="chevron-right" 
                         :disabled="\Carbon\Carbon::parse($selectedDate)->isToday()" />
        </div>
    </div>

    @if(!$employeeId)
        <x-wfm.empty icon="user" message="No tienes un empleado asociado a tu cuenta." class="h-64" />
    @else
        <div class="space-y-4">
            <livewire:wfm.my-day.header-widget lazy :employee-id="$employeeId" :selected-date="$selectedDate" />
            
            <livewire:wfm.my-day.timeline-widget lazy :employee-id="$employeeId" :selected-date="$selectedDate" />
            
            <livewire:wfm.my-day.kpi-widget lazy :employee-id="$employeeId" :selected-date="$selectedDate" />

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 flex flex-col gap-4">
                    <livewire:wfm.my-day.schedule-compliance-widget lazy :employee-id="$employeeId" :selected-date="$selectedDate" />
                    
                    <livewire:wfm.my-day.state-distribution-widget lazy :employee-id="$employeeId" :selected-date="$selectedDate" />
                </div>
                
                <div class="space-y-4">
                    <livewire:wfm.my-day.recent-transitions-widget lazy :employee-id="$employeeId" :selected-date="$selectedDate" />
                    
                    <livewire:wfm.my-day.not-ready-breakdown-widget lazy :employee-id="$employeeId" :selected-date="$selectedDate" />
                </div>
            </div>

            <livewire:wfm.my-day.queue-performance-widget lazy :employee-id="$employeeId" :selected-date="$selectedDate" />
        </div>
    @endif
</div>
