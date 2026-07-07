<div class="flex flex-col h-full space-y-6 overflow-hidden">
    {{-- Barra Visual del Día (Fija) --}}
    <div class="flex-none bg-slate-50 p-4 rounded-md border border-slate-100">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Vista Visual del Día (05:00 - 18:00)</h3>
            <div class="flex flex-wrap gap-x-4 gap-y-2 text-[10px] font-medium text-slate-400">
                <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Talking</div>
                <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-400"></span> Ready</div>
                <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-400"></span> Not Ready</div>
                <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-slate-500"></span> Reserved</div>
                <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-yellow-300"></span> Work / Break</div>
                <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-slate-400"></span> Logged-in</div>
                <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-zinc-900"></span> Logout</div>
            </div>
        </div>

        <div class="relative h-4 bg-slate-200 rounded-full overflow-hidden shadow-inner">
            @foreach($barSegments as $segment)
                <div 
                    class="absolute h-full {{ $segment['color'] }} transition-opacity hover:brightness-90 cursor-help group"
                    style="left: {{ $segment['left'] }}%; width: {{ $segment['width'] }}%;"
                >
                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block z-10">
                        <div class="bg-slate-800 text-white text-[10px] py-1 px-2 rounded shadow-md whitespace-nowrap">
                            {{ $segment['label'] }}: {{ $segment['time'] }}
                        </div>
                        <div class="w-2 h-2 bg-slate-800 rotate-45 mx-auto -mt-1"></div>
                    </div>
                </div>
            @endforeach
            
            {{-- Indicador de Hora Actual --}}
            @php
                $now = now();
                $startWin = $now->copy()->startOfDay()->addHours(5);
                $endWin = $now->copy()->startOfDay()->addHours(18);
                $isWithin = $now->between($startWin, $endWin);
                $nowPos = $isWithin ? (($now->diffInMinutes($startWin)) / $endWin->diffInMinutes($startWin)) * 100 : -1;
            @endphp
            
            @if($nowPos >= 0)
                <div class="absolute top-0 h-full border-l-2 border-white shadow-sm z-20 pointer-events-none" style="left: {{ $nowPos }}%">
                    <div class="absolute -top-1 -left-1 w-2 h-2 bg-white rounded-full"></div>
                </div>
            @endif
        </div>
        
        <div class="flex justify-between mt-2 text-[10px] text-slate-400 font-mono">
            <span>05:00</span>
            <span>08:00</span>
            <span>11:00</span>
            <span>14:00</span>
            <span>18:00</span>
        </div>
    </div>

    {{-- Lista de Actividades Agrupada (Scrollable) --}}
    <div class="flex-1 overflow-y-auto pr-4 custom-scrollbar" style="max-height: 600px;">
        <div class="relative">
            <div class="absolute left-[11px] top-0 bottom-0 w-0.5 bg-slate-100"></div>

            <div class="space-y-2">
                @php
                    $lastHour = null;
                @endphp

                @forelse($timeline as $item)
                    @php
                        $currentHour = \Carbon\Carbon::parse($item->startTime)->format('H:00');
                    @endphp

                    @if($lastHour !== $currentHour)
                        <div class="relative z-10 flex items-center gap-4 pt-4 pb-2">
                            <div class="w-6 h-6 rounded-full bg-white border-2 border-slate-200 flex items-center justify-center">
                                <span class="text-[9px] font-bold text-slate-400">{{ explode(':', $currentHour)[0] }}</span>
                            </div>
                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ $currentHour }}</span>
                        </div>
                        @php $lastHour = $currentHour; @endphp
                    @endif

                    <div class="relative z-10 flex items-start gap-4 group">
                        <div class="mt-1.5 w-6 flex justify-center">
                            <div class="w-2 h-2 rounded-full {{ $item->isCurrent ? 'bg-blue-600 animate-pulse' : ($item->isReal ? 'bg-slate-300' : 'border border-slate-300 bg-white') }}"></div>
                        </div>

                        <div class="flex-1 bg-white p-3 rounded-lg border border-slate-100 shadow-sm group-hover:border-slate-200 transition-opacity">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2">
                                    <flux:badge :color="$item->color" variant="subtle" size="sm" class="font-bold uppercase tracking-wide">
                                        {{ $item->label }}
                                    </flux:badge>
                                    
                                    @if($item->isReal)
                                        <flux:badge size="xs" color="slate" variant="subtle">Real</flux:badge>
                                    @endif
                                    
                                    @if($item->isCurrent)
                                        <span class="flex h-2 w-2 rounded-full bg-blue-600 animate-pulse" title="Actual"></span>
                                    @endif
                                </div>
                                <span class="text-[10px] font-mono font-bold text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded">
                                    {{ $item->displayTime }}
                                </span>
                            </div>

                            @if($item->description)
                                <p class="text-[11px] text-slate-500 leading-relaxed">{{ $item->description }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-20">
                        <flux:icon name="calendar-days" class="mx-auto text-slate-200 mb-4" size="lg" />
                        <p class="text-slate-400 text-sm">No hay actividades registradas para hoy</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #cbd5e1;
    }
</style>