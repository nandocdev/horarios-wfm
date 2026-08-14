@props([
    'tour',
    'label' => 'Guía',
    'iconOnly' => false,
])

<button type="button"
    onclick="window.startWfmTour('{{ $tour }}')"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2 py-1 rounded text-xs font-medium text-wfm-surface-muted hover:text-wfm-navy-800 dark:hover:text-white bg-wfm-surface hover:bg-wfm-surface-hover border border-wfm-surface-border transition-colors cursor-pointer select-none active:scale-95']) }}
    title="Iniciar guía interactiva de esta vista">
    <flux:icon.question-mark-circle class="w-3.5 h-3.5 text-wfm-blue-500" />
    @if(!$iconOnly)
        <span>{{ $label }}</span>
    @endif
</button>
