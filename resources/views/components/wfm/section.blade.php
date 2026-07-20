@props([
    'title' => null,
    'description' => null,
    'collapsed' => false,
])

<div {{ $attributes->merge(['class' => 'card-wfm']) }}>
    @if($title)
        <div class="flex items-center justify-between px-3 py-2.5 border-b border-wfm-surface-border">
            <div>
                <h3 class="text-sm font-semibold text-wfm-navy-800 dark:text-white">{{ $title }}</h3>
                @if($description)
                    <p class="text-xs text-wfm-surface-muted mt-0.5">{{ $description }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                {{ $actions ?? '' }}
            </div>
        </div>
    @endif

    <div class="p-3">
        {{ $slot }}
    </div>
</div>
