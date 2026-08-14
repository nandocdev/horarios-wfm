@props([
    'title' => null,
    'icon' => null,
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'card-wfm']) }}>
    @if($title)
        <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 sm:py-2.5 border-b border-wfm-surface-border">
            <div class="flex items-center gap-2 min-w-0">
                @if($icon)
                    <flux:icon :name="$icon" class="w-4 h-4 text-wfm-surface-muted flex-shrink-0" />
                @endif
                <h3 class="text-sm font-semibold text-wfm-navy-800 dark:text-white truncate">{{ $title }}</h3>
            </div>
            @if(isset($actions))
                <div class="flex items-center gap-1.5 flex-wrap">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    @if($padding)
        <div class="p-2.5 sm:p-3">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</div>
