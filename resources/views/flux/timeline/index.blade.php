@props([
    'horizontal' => false,
    'align' => 'center',
    'size' => null,
])

@php
$classes = Flux::classes()
    ->add('grid')
    ->add($horizontal ? 'grid-flow-col auto-cols-fr' : 'grid-cols-[var(--flux-indicator-size)_1fr]')
    ->add($size === 'lg' ? '[--flux-indicator-size:2.5rem]' : '[--flux-indicator-size:1.5rem]')
    ->add('[--flux-timeline-item-gap:1.5rem] [--flux-timeline-content-gap:1rem]')
    ->add('gap-y-[var(--flux-timeline-item-gap)]');
@endphp

<div {{ $attributes->class($classes) }} data-flux-timeline>
    {{ $slot }}
</div>
