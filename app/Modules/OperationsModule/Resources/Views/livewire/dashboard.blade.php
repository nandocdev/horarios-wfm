<div @if(!$this->isHistorical) wire:poll.{{ $refreshInterval }}s @endif class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Dashboard Operativo</flux:heading>
            <flux:subheading>
                @if($this->isHistorical)
                    Datos históricos del día {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
                @else
                    Estado global de la operación en tiempo real.
                @endif
            </flux:subheading>
        </div>

        <div class="flex items-center gap-4">
            {{-- CTI Link Status --}}
            @if(!$this->isHistorical)
                <div class="flex items-center gap-2">
                    @if($this->ctiStatus['online'])
                        <flux:badge color="green" size="sm" class="flex items-center gap-1.5">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            CTI Online ({{ $this->ctiStatus['updated_at'] }})
                        </flux:badge>
                    @else
                        <flux:badge color="red" size="sm" class="flex items-center gap-1.5">
                            <span class="relative flex h-2 w-2">
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500 animate-pulse"></span>
                            </span>
                            CTI Offline
                        </flux:badge>
                    @endif
                </div>
            @endif

            <div class="w-48">
                <flux:input type="date" wire:model.live="selectedDate" size="sm" />
            </div>

            @if(!$this->isHistorical)
                <div class="flex items-center gap-2 text-sm text-zinc-500">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                    </span>
                    Live
                </div>
                <flux:button icon="arrow-path" size="sm" variant="ghost" wire:click="$refresh" />
            @else
                <flux:button size="sm" variant="ghost" wire:click="$set('selectedDate', '{{ now()->toDateString() }}')">
                    Volver a Hoy
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Hero KPIs Widget (Lazy) --}}
    <livewire:operations.widgets.hero-kpi-widget :selectedDate="$selectedDate" :key="'hero-'.$selectedDate" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2">
            {{-- Queue Stats Widget (Lazy) --}}
            <livewire:operations.widgets.queue-stats-widget :selectedDate="$selectedDate" :key="'queues-'.$selectedDate" />
        </div>

        <div class="lg:col-span-1">
            {{-- State Distribution Widget (Lazy) --}}
            <livewire:operations.widgets.state-distribution-widget :selectedDate="$selectedDate" :key="'states-'.$selectedDate" />
        </div>
    </div>

    {{-- Volume Comparison Widget (Lazy) --}}
    <livewire:operations.widgets.volume-comparison-widget :key="'volume-'.$selectedDate" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-1">
            {{-- Critical Alerts Widget (Lazy) --}}
            <livewire:operations.widgets.critical-alerts-widget :selectedDate="$selectedDate" :key="'alerts-'.$selectedDate" />
        </div>

        <div class="lg:col-span-2">
            {{-- Recent Incidents Widget (Lazy) --}}
            <livewire:operations.widgets.recent-incidents-widget :key="'incidents-'.$selectedDate" />
        </div>
    </div>
</div>
