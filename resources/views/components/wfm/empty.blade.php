@props([
    'icon' => 'inbox',
    'message' => 'Sin datos disponibles',
    'description' => null,
    'action' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-12 px-4']) }}>
    <div class="p-3 rounded-full bg-wfm-surface mb-3 text-wfm-surface-muted">
        <flux:icon :name="$icon" class="w-6 h-6" />
    </div>
    <p class="text-sm font-medium text-wfm-navy-800 dark:text-white">{{ $message }}</p>
    @if($description)
        <p class="text-xs text-wfm-surface-muted mt-1 text-center max-w-xs">{{ $description }}</p>
    @endif
    @if($action && $actionLabel)
        <div class="mt-4">
            {{ $action }}
        </div>
    @endif
</div>
