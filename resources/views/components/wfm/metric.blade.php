@props([
    'value',
    'label',
    'size' => 'sm',
])

@php
    $sizes = [
        'xs' => 'text-lg',
        'sm' => 'text-2xl',
        'md' => 'text-3xl',
        'lg' => 'text-4xl',
    ];
    $valueSize = $sizes[$size] ?? $sizes['sm'];
@endphp

<div {{ $attributes->merge(['class' => '']) }}>
    <div class="{{ $valueSize }} font-bold tracking-tight text-wfm-navy-800 dark:text-white leading-none">
        {{ $value }}
    </div>
    <div class="kpi-label mt-1">{{ $label }}</div>
</div>
