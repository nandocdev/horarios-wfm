<div class="card-wfm p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <div>
            <div class="text-sm font-bold text-wfm-navy-800 dark:text-white">{{ $d['name'] }}</div>
            <div class="flex items-center gap-2 flex-wrap">
                <flux:text class="font-mono text-xs {{ $isToday ? 'text-wfm-info font-semibold' : 'text-wfm-surface-muted' }}">
                    {{ $selDate->locale('es')->translatedFormat('l d F Y') }}
                    @if($isToday)
                        <span class="text-[10px] bg-wfm-info/10 text-wfm-info px-1.5 py-0.5 rounded font-mono">Hoy</span>
                    @endif
                </flux:text>
                <span class="text-xs text-wfm-surface-muted truncate">{{ $d['team'] }}</span>
            </div>
        </div>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 pt-2 sm:pt-0 border-t sm:border-t-0 border-wfm-surface-border">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full {{ $d['is_connected'] ? 'bg-wfm-success animate-pulse' : 'bg-wfm-danger' }}"></span>
            <span class="text-sm font-bold">{{ $d['current_state'] }}</span>
            @if($d['reason'])
                <span class="text-xs text-wfm-surface-muted truncate">({{ $d['reason'] }})</span>
            @endif
        </div>
        <span class="text-xs text-wfm-surface-muted sm:border-l sm:border-wfm-surface-border sm:pl-4">
            {{ gmdate('H:i:s', $d['total_seconds']) }} conectado ·
            {{ gmdate('H:i:s', $d['productive_seconds']) }} productivo
        </span>
    </div>
</div>
