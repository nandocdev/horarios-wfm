@props([
    'status' => 'incomplete',
    'align' => 'center',
    'size' => null,
])

@php
$classes = Flux::classes()
    ->add('relative contents') // Usamos contents para que los hijos sean directos del grid padre
    ->add(match($align) {
        'start' => 'items-start',
        'baseline' => 'items-baseline',
        'center' => 'items-center',
        'end' => 'items-end',
    });
@endphp

<div {{ $attributes->class($classes) }} data-flux-timeline-item data-status="{{ $status }}">
    {{ $slot }}
</div>
