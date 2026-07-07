<div class="p-8 space-y-8 bg-slate-50 min-h-screen">
    {{-- Header --}}
    <div>
        <flux:heading size="xl" level="1">Framework de Reporting Operacional</flux:heading>
        <flux:subheading>Estructura de análisis multinivel basada en los 5 dominios de la operación.</flux:subheading>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach($reports as $section)
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="h-1 w-8 bg-slate-400 rounded-md"></div>
                    <flux:heading size="lg">{{ $section['title'] }}</flux:heading>
                </div>
                <p class="text-sm text-slate-500 leading-relaxed">{{ $section['description'] }}</p>
                
                <div class="space-y-4">
                    @foreach($section['items'] as $item)
                        <flux:card class="hover:border-slate-300 transition-opacity duration-150 ease-out group cursor-pointer" wire:navigate href="{{ route($item['route']) }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-md group-hover:bg-slate-200 dark:group-hover:bg-slate-700 transition-colors duration-150">
                                        <flux:icon :name="$item['icon']" variant="mini" />
                                    </div>
                                    <span class="text-sm font-semibold text-slate-700 group-hover:text-slate-900">{{ $item['label'] }}</span>
                                </div>
                                @if(isset($item['badge']))
                                    <flux:badge size="sm" variant="subtle" class="rounded-md">{{ $item['badge'] }}</flux:badge>
                                @endif
                                <flux:icon name="chevron-right" variant="mini" class="text-slate-300 group-hover:text-slate-500" />
                            </div>
                        </flux:card>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Footer Info --}}
    <flux:card class="bg-slate-900 text-white border border-slate-800 shadow-sm rounded-md relative overflow-hidden">
        <div class="relative z-10 space-y-4">
            <flux:heading class="text-white">Capacidad de Trazabilidad Total</flux:heading>
            <p class="text-sm text-slate-300 max-w-2xl">
                Este framework permite cruzar datos de la <strong>Planificación (WFM)</strong> con la <strong>Realidad (Cisco Telemetry)</strong> y los <strong>Resultados (KPIs)</strong>. 
                Cada reporte está diseñado para responder una pregunta específica de la operación, desde el cumplimiento del horario hasta el retorno de inversión por Work Unit.
            </p>
        </div>
    </flux:card>
</div>
