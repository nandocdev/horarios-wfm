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
                    <div class="h-1 w-8 bg-indigo-500 rounded-full"></div>
                    <flux:heading size="lg">{{ $section['title'] }}</flux:heading>
                </div>
                <p class="text-sm text-slate-500 leading-relaxed">{{ $section['description'] }}</p>
                
                <div class="space-y-3">
                    @foreach($section['items'] as $item)
                        <flux:card class="hover:border-indigo-300 transition-all group cursor-pointer" wire:navigate href="{{ route($item['route']) }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-lg group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                                        <flux:icon :name="$item['icon']" variant="mini" />
                                    </div>
                                    <span class="text-sm font-bold text-slate-700 group-hover:text-indigo-700">{{ $item['label'] }}</span>
                                </div>
                                @if(isset($item['badge']))
                                    <flux:badge size="sm" variant="subtle" color="indigo">{{ $item['badge'] }}</flux:badge>
                                @endif
                                <flux:icon name="chevron-right" variant="mini" class="text-slate-300 group-hover:text-indigo-400" />
                            </div>
                        </flux:card>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Footer Info --}}
    <flux:card class="bg-indigo-900 text-white border-none shadow-md relative overflow-hidden">
        <div class="absolute right-0 top-0 opacity-10">
            <flux:icon name="magnifying-glass-circle" class="w-32 h-32" />
        </div>
        <div class="relative z-10 space-y-4">
            <flux:heading class="text-white">Capacidad de Trazabilidad Total</flux:heading>
            <p class="text-sm text-indigo-100 max-w-2xl">
                Este framework permite cruzar datos de la <strong>Planificación (WFM)</strong> con la <strong>Realidad (Cisco Telemetry)</strong> y los <strong>Resultados (KPIs)</strong>. 
                Cada reporte está diseñado para responder una pregunta específica de la operación, desde el cumplimiento del horario hasta el retorno de inversión por Work Unit.
            </p>
        </div>
    </flux:card>
</div>
