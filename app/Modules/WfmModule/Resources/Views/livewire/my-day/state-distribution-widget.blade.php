@if(!$isHistorical)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <x-wfm.section title="Estado Actual">
            <div class="space-y-2 text-xs">
                @foreach([['TALKING', $d['talk'] ?? 0], ['READY', $d['ready'] ?? 0],
                          ['ACW/WORK', $d['acw'] ?? 0], ['RESERVED', $d['reserved'] ?? 0],
                          ['ALMUERZO', $d['lunch'] ?? 0], ['DESCANSO', $d['break'] ?? 0],
                          ['NOT READY', $d['not_ready'] ?? 0], ['OFFLINE', $d['offline'] ?? 0]] as [$label, $seconds])
                    @php
                        $pct = ($d['total_seconds'] ?? 1) > 0 ? round(($seconds / max($d['total_seconds'], 1)) * 100, 1) : 0;
                    @endphp
                    <div class="flex items-center gap-2 p-1.5 bg-wfm-surface rounded">
                        <span class="w-16 text-wfm-navy-700 font-medium">{{ $label }}</span>
                        <div class="flex-1 h-2 bg-wfm-surface-border rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ match($label) {
                                'TALKING' => 'bg-wfm-info',
                                'READY' => 'bg-wfm-success',
                                'ACW/WORK' => 'bg-purple-500',
                                'RESERVED' => 'bg-cyan-500',
                                'ALMUERZO', 'DESCANSO' => 'bg-wfm-warning',
                                'NOT READY' => 'bg-amber-500',
                                'OFFLINE' => 'bg-wfm-danger',
                                default => 'bg-wfm-surface-muted',
                            } }}" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="font-mono text-wfm-navy-700 w-16 text-right">{{ gmdate('H:i:s', $seconds) }}</span>
                        <span class="text-wfm-surface-muted w-10 text-right">{{ $pct }}%</span>
                    </div>
                @endforeach
            </div>
        </x-wfm.section>

        <x-wfm.section title="Desglose del TMO (AHT)">
            @php
                $aht = $d['avg_handle_time'] ?? 0;
                $att = $d['avg_talk_time'] ?? 0;
                $acw = $d['avg_acw_time'] ?? 0;
                $hold = max(0, $aht - $att - $acw);
            @endphp
            @if($aht > 0)
                <div class="flex flex-col gap-4">
                    <div class="flex items-end justify-between">
                        <div class="text-3xl font-bold font-mono leading-none">{{ number_format($aht, 1) }}s</div>
                        <div class="text-xs text-wfm-surface-muted">TMO Promedio Global</div>
                    </div>
                    
                    <div class="flex h-6 rounded-md overflow-hidden bg-wfm-surface border border-wfm-surface-border">
                        @php
                            $pctAtt = ($att / $aht) * 100;
                            $pctAcw = ($acw / $aht) * 100;
                            $pctHold = ($hold / $aht) * 100;
                        @endphp
                        <div class="bg-wfm-info h-full transition-all duration-500" style="width: {{ $pctAtt }}%" title="Habla: {{ number_format($att, 1) }}s"></div>
                        <div class="bg-purple-500 h-full transition-all duration-500" style="width: {{ $pctAcw }}%" title="Trabajo (ACW): {{ number_format($acw, 1) }}s"></div>
                        @if($hold > 0)
                            <div class="bg-amber-400 h-full transition-all duration-500" style="width: {{ $pctHold }}%" title="Espera: {{ number_format($hold, 1) }}s"></div>
                        @endif
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-xs">
                        <div class="flex flex-col gap-0.5 bg-wfm-surface p-2 rounded border border-wfm-surface-border/50">
                            <div class="flex items-center gap-1.5 text-wfm-surface-muted font-medium">
                                <span class="w-2 h-2 rounded-sm bg-wfm-info"></span>
                                Habla (ATT)
                            </div>
                            <div class="font-mono text-sm font-semibold pl-3.5">{{ number_format($att, 1) }}s <span class="text-[10px] text-wfm-surface-muted font-normal ml-1">{{ round($pctAtt) }}%</span></div>
                        </div>
                        
                        <div class="flex flex-col gap-0.5 bg-wfm-surface p-2 rounded border border-wfm-surface-border/50">
                            <div class="flex items-center gap-1.5 text-wfm-surface-muted font-medium">
                                <span class="w-2 h-2 rounded-sm bg-purple-500"></span>
                                Trabajo (ACW)
                            </div>
                            <div class="font-mono text-sm font-semibold pl-3.5">{{ number_format($acw, 1) }}s <span class="text-[10px] text-wfm-surface-muted font-normal ml-1">{{ round($pctAcw) }}%</span></div>
                        </div>
                        
                        @if($hold > 0)
                        <div class="flex flex-col gap-0.5 bg-wfm-surface p-2 rounded border border-wfm-surface-border/50">
                            <div class="flex items-center gap-1.5 text-wfm-surface-muted font-medium">
                                <span class="w-2 h-2 rounded-sm bg-amber-400"></span>
                                Espera (Hold)
                            </div>
                            <div class="font-mono text-sm font-semibold pl-3.5">{{ number_format($hold, 1) }}s <span class="text-[10px] text-wfm-surface-muted font-normal ml-1">{{ round($pctHold) }}%</span></div>
                        </div>
                        @endif
                    </div>
                </div>
            @else
                <x-wfm.empty icon="phone" message="No hay llamadas atendidas" class="h-32" />
            @endif
        </x-wfm.section>
    </div>
@endif
