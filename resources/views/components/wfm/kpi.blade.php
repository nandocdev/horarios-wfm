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

<div class="card-wfm border-l-2 {{ $borderColor }} p-2.5 sm:p-3 @if($link) cursor-pointer hover:shadow-md transition-shadow @endif"
    @if($link) onclick="window.location='{{ $link }}'" @endif>

    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0 flex-1">
            <div class="text-lg sm:text-2xl font-bold tracking-tight text-wfm-navy-800 dark:text-white truncate font-mono sm:font-sans">{{ $value }}</div>
            <div class="kpi-label mt-0.5 sm:mt-1 truncate text-[10px] sm:text-xs">{{ $label }}</div>

            @if($comparison || $trend)
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mt-1 sm:mt-1.5">
                    @if($trend && $trendDirection)
                        @if($trendDirection === 'up')
                            <span class="kpi-trend-up text-[10px] sm:text-xs">▲ {{ $trend }}</span>
                        @elseif($trendDirection === 'down')
                            <span class="kpi-trend-down text-[10px] sm:text-xs">▼ {{ $trend }}</span>
                        @else
                            <span class="text-[10px] sm:text-xs text-wfm-surface-muted">— {{ $trend }}</span>
                        @endif
                    @endif
                    @if($comparison)
                        <span class="kpi-target text-[10px] sm:text-xs truncate">{{ $comparison }}</span>
                    @endif
                </div>
            @endif

            @if($target)
                <div class="kpi-target mt-0.5 text-[10px] sm:text-xs">Meta: {{ $target }}</div>
            @endif
        </div>

        @if($icon)
            <div class="flex-shrink-0 p-1 sm:p-1.5 rounded bg-wfm-navy-50 dark:bg-wfm-navy-800 text-wfm-navy-500 dark:text-wfm-blue-300">
                <flux:icon :name="$icon" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
            </div>
        @endif
    </div>

    {{ $slot ?? '' }}
</div>
