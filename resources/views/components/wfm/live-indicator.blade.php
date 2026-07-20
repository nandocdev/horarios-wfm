@props([
    'label' => 'En Vivo',
    'color' => 'success',
    'pulse' => true,
])

@php
    $dotColors = [
        'success' => 'bg-wfm-success',
        'danger' => 'bg-wfm-danger',
        'warning' => 'bg-wfm-warning',
        'info' => 'bg-wfm-info',
    ];
    $dotColor = $dotColors[$color] ?? $dotColors['success'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-wfm-surface-muted']) }}>
    @if($pulse)
        <span class="live-pulse">
            <span class="live-pulse-dot" style="background-color: {{ $dotColor }}"></span>
            <span class="live-pulse-ring" style="background-color: {{ $dotColor }}"></span>
        </span>
    @else
        <span class="status-dot {{ $dotColor }}"></span>
    @endif
    {{ $label }}
</span>
