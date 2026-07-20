@props([
    'label' => null,
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 flex-wrap']) }}>
    @if($label)
        <span class="kpi-label mr-1">{{ $label }}</span>
    @endif
    {{ $slot }}
</div>
