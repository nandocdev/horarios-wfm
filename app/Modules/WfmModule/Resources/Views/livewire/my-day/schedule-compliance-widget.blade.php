<x-wfm.section title="Cumplimiento del Horario">
    @php
        $entryDiff = $d['entry_diff'] ?? null;
        $entryLabel = $entryDiff !== null ? ($entryDiff <= 0 ? (string) $entryDiff : '+' . $entryDiff) . ' min' : '—';
        $lunchDiff = $d['lunch_diff'] ?? null;
        $lunchLabel = $lunchDiff !== null ? ($lunchDiff <= 0 ? (string) $lunchDiff : '+' . $lunchDiff) . ' min' : '—';
        $breakDiff = $d['break_diff'] ?? null;
        $breakLabel = $breakDiff !== null ? ($breakDiff <= 0 ? (string) $breakDiff : '+' . $breakDiff) . ' min' : '—';
        $endDiff = $d['end_diff'] ?? null;
        $endLabel = $endDiff !== null ? ($endDiff <= 0 ? (string) $endDiff : '+' . $endDiff) . ' min' : '—';
        $totalAcum = ($d['total_seconds'] ?? 0) > 0 ? gmdate('H:i', $d['total_seconds']) : '—';
        $productiveAcum = ($d['productive_seconds'] ?? 0) > 0 ? gmdate('H:i', $d['productive_seconds']) : '—';
        $lunchAcum = ($d['lunch'] ?? 0) > 0 ? gmdate('H:i', $d['lunch']) : '—';
        $breakAcum = ($d['break'] ?? 0) > 0 ? gmdate('H:i', $d['break']) : '—';
        $compliance = [
            ['label' => 'Entrada', 'sched' => $d['scheduled_entry'] ?? '--:--', 'real' => $d['real_entry'] ?? '--:--', 'acum' => $productiveAcum, 'diff' => $entryLabel, 'ok' => ($entryDiff !== null && $entryDiff <= 5)],
            ['label' => 'Almuerzo', 'sched' => $d['lunch_start'] ?? '--:--', 'real' => $d['first_lunch_time'] ?? '—', 'acum' => $lunchAcum, 'diff' => $lunchLabel, 'ok' => ($lunchDiff !== null && $lunchDiff <= 5)],
            ['label' => 'Descanso', 'sched' => $d['break_start'] ?? '--:--', 'real' => $d['first_break_time'] ?? '—', 'acum' => $breakAcum, 'diff' => $breakLabel, 'ok' => ($breakDiff !== null && $breakDiff <= 5)],
            ['label' => 'Salida', 'sched' => $d['scheduled_end'] ?? '--:--', 'real' => $d['real_end'] ?? '—', 'acum' => $totalAcum, 'diff' => $endLabel, 'ok' => ($endDiff !== null && $endDiff >= -5)],
        ];
    @endphp
    <x-wfm.table :headers="['', 'Programado', 'Real', 'Acumulado', 'Estado']" compact>
        @foreach($compliance as $c)
            <flux:table.row>
                <flux:table.cell class="font-medium text-xs">{{ $c['label'] }}</flux:table.cell>
                <flux:table.cell><span class="font-mono text-xs">{{ $c['sched'] }}</span></flux:table.cell>
                <flux:table.cell><span class="font-mono text-xs">{{ $c['real'] }}</span></flux:table.cell>
                <flux:table.cell><span class="font-mono text-xs">{{ $c['acum'] }}</span></flux:table.cell>
                <flux:table.cell>
                    <x-wfm.agent-status :status="$c['ok'] ? 'available' : 'busy'" :label="$c['diff']" size="xs" />
                </flux:table.cell>
            </flux:table.row>
        @endforeach
        @if(!empty($d['intraday_activities']))
            @foreach($d['intraday_activities'] as $ia)
                <flux:table.row>
                    <flux:table.cell class="font-medium text-xs">{{ $ia['name'] }}</flux:table.cell>
                    <flux:table.cell><span class="font-mono text-xs">{{ $ia['start'] }} - {{ $ia['end'] }}</span></flux:table.cell>
                    <flux:table.cell colspan="2" class="text-xs text-wfm-surface-muted">Actividad intradía</flux:table.cell>
                    <flux:table.cell><x-wfm.agent-status status="available" label="Programada" size="xs" /></flux:table.cell>
                </flux:table.row>
            @endforeach
        @endif
    </x-wfm.table>
    @if($d['has_exceptions'] ?? false)
        <div class="mt-2 flex items-center gap-1.5 text-xs text-wfm-warning">
            <flux:icon.exclamation-triangle class="w-3.5 h-3.5" />
            <span>Con excepción de horario</span>
        </div>
    @endif
</x-wfm.section>
