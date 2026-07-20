@props([
    'label' => 'Filtrar por',
    'clear' => false,
    'clearWire' => null,
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 flex-wrap']) }}>
    <span class="text-[10px] font-semibold uppercase tracking-wider text-wfm-surface-muted">{{ $label }}</span>

    {{ $slot }}

    @if($clear && $clearWire)
        <button wire:click="{{ $clearWire }}"
            class="text-[10px] font-medium text-wfm-surface-muted hover:text-wfm-navy-700 transition-colors">
            Limpiar
        </button>
    @endif
</div>
