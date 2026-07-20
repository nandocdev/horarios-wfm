@props([
    'title' => null,
    'icon' => null,
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'card-wfm']) }}>
    @if($title)
        <div class="flex items-center justify-between px-3 py-2.5 border-b border-wfm-surface-border {{ !$padding ? 'border-b-0' : '' }}">
            <div class="flex items-center gap-2">
                @if($icon)
                    <flux:icon :name="$icon" class="w-4 h-4 text-wfm-surface-muted" />
                @endif
                <h3 class="text-sm font-semibold text-wfm-navy-800 dark:text-white">{{ $title }}</h3>
            </div>
            {{ $actions ?? '' }}
        </div>
    @endif

    @if($padding)
        <div class="p-3">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</div>
