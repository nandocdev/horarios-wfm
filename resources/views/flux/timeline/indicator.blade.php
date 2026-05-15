@props([
    'variant' => 'default',
    'color' => 'zinc',
    'status' => null,
])

@php
// Mapeo de colores de Flux a clases de Tailwind
$colorClasses = match($color) {
    'green' => 'bg-green-500 text-white',
    'red' => 'bg-red-500 text-white',
    'amber' => 'bg-amber-500 text-white',
    'blue' => 'bg-blue-500 text-white',
    'zinc' => 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500',
    default => 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500',
};

$classes = Flux::classes('relative z-10 shrink-0 flex items-center justify-center rounded-full border-2 border-white dark:border-zinc-900')
    ->add($variant === 'bare' ? '' : 'size-[var(--flux-indicator-size)] ' . $colorClasses)
    ->add($variant === 'bare' ? 'bg-transparent border-none' : '');
@endphp

<div class="relative flex justify-center h-full">
    {{-- Línea vertical --}}
    <div class="absolute bg-zinc-200 dark:bg-zinc-700 w-[2px] h-[calc(100%+var(--flux-timeline-item-gap))] top-0 left-1/2 -translate-x-1/2 data-flux-timeline-line"></div>

    <div {{ $attributes->class($classes) }} data-flux-timeline-indicator>
        {{ $slot }}
    </div>
</div>
