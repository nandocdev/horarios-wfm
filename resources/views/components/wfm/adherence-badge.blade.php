@props([
    'value',
    'target' => 90,
    'size' => 'sm',
])

@php
    $pct = (float) $value;
    if ($pct >= $target) {
        $color = 'bg-wfm-success/10 text-wfm-success border-wfm-success/20';
    } elseif ($pct >= $target - 10) {
        $color = 'bg-wfm-warning/10 text-wfm-warning border-wfm-warning/20';
    } else {
        $color = 'bg-wfm-danger/10 text-wfm-danger border-wfm-danger/20';
    }
    $sizeClass = $size === 'xs' ? 'px-1.5 py-0.5 text-[10px]' : 'px-2 py-0.5 text-xs';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded border font-semibold {$color} {$sizeClass}"]) }}>
    <span class="kpi-trend-{{ $pct >= $target ? 'up' : 'down' }}">
        @if($pct >= $target)▲ @else▼ @endif
    </span>
    {{ number_format($pct, 1) }}%
</span>
