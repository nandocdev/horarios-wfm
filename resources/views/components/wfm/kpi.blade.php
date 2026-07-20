@props([
    'value',
    'label',
    'comparison' => null,
    'trend' => null,
    'trendDirection' => null,
    'target' => null,
    'color' => 'default',
    'icon' => null,
    'link' => null,
])

@php
    $colors = [
        'default' => '',
        'success' => 'border-l-wfm-success',
        'warning' => 'border-l-wfm-warning',
        'danger' => 'border-l-wfm-danger',
        'info' => 'border-l-wfm-info',
    ];
    $borderColor = $colors[$color] ?? $colors['default'];
@endphp

<div class="card-wfm border-l-2 {{ $borderColor }} p-3 @if($link) cursor-pointer hover:shadow-md transition-shadow @endif"
    @if($link) onclick="window.location='{{ $link }}'" @endif>

    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0 flex-1">
            <div class="kpi-value truncate">{{ $value }}</div>
            <div class="kpi-label mt-1 truncate">{{ $label }}</div>

            @if($comparison || $trend)
                <div class="flex items-center gap-2 mt-1.5">
                    @if($trend && $trendDirection)
                        @if($trendDirection === 'up')
                            <span class="kpi-trend-up">▲ {{ $trend }}</span>
                        @elseif($trendDirection === 'down')
                            <span class="kpi-trend-down">▼ {{ $trend }}</span>
                        @else
                            <span class="text-xs text-wfm-surface-muted">— {{ $trend }}</span>
                        @endif
                    @endif
                    @if($comparison)
                        <span class="kpi-target">{{ $comparison }}</span>
                    @endif
                </div>
            @endif

            @if($target)
                <div class="kpi-target mt-0.5">Meta: {{ $target }}</div>
            @endif
        </div>

        @if($icon)
            <div class="flex-shrink-0 p-1.5 rounded bg-wfm-navy-50 dark:bg-wfm-navy-800 text-wfm-navy-500 dark:text-wfm-blue-300">
                <flux:icon :name="$icon" class="w-4 h-4" />
            </div>
        @endif
    </div>

    {{ $slot ?? '' }}
</div>
