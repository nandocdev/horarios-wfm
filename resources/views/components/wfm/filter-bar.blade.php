@props([
    'label' => 'Filtrar por',
    'clear' => false,
    'clearWire' => null,
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 flex-wrap w-full']) }}>
    <span class="text-[10px] font-semibold uppercase tracking-wider text-wfm-surface-muted">{{ $label }}</span>

    <div class="flex items-center gap-2 flex-wrap flex-1 min-w-0">
        {{ $slot }}
    </div>

    @if($clear && $clearWire)
        <button wire:click="{{ $clearWire }}"
            class="text-xs font-medium text-wfm-surface-muted hover:text-wfm-navy-700 dark:hover:text-white transition-colors px-2 py-1 rounded hover:bg-wfm-surface-hover">
            Limpiar
        </button>
    @endif
</div>
