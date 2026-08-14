@props([
    'title',
    'description' => null,
    'search' => false,
    'searchPlaceholder' => 'Buscar...',
    'searchWire' => null,
    'divider' => true,
])

<div {{ $attributes->merge(['class' => '-mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-3 bg-wfm-surface-card'.($divider ? ' border-b border-wfm-surface-border' : '').' -mt-4 sm:-mt-6 lg:-mt-8']) }}>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0 flex-1">
            <h1 class="text-sm sm:text-base font-semibold text-wfm-navy-800 dark:text-white truncate">{{ $title }}</h1>
            @if($description)
                <p class="text-xs text-wfm-surface-muted mt-0.5">{{ $description }}</p>
            @endif
        </div>

        @if($search || isset($actions))
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full sm:w-auto sm:shrink-0">
                @if($search)
                    <div class="relative w-full sm:w-64">
                        <flux:icon.magnifying-glass class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-wfm-surface-muted pointer-events-none" />
                        <input type="search"
                            @if($searchWire) wire:model.live.debounce.300ms="{{ $searchWire }}" @endif
                            placeholder="{{ $searchPlaceholder }}"
                            class="w-full rounded border border-wfm-surface-border bg-wfm-surface py-1.5 pl-8 pr-3 text-xs text-wfm-navy-800 placeholder:text-wfm-surface-muted focus:outline-none focus:ring-1 focus:ring-wfm-navy-500 dark:bg-wfm-navy-900 dark:text-white dark:placeholder:text-wfm-surface-muted" />
                    </div>
                @endif

                @if(isset($actions))
                    <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                        {{ $actions }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if(($filters ?? false) || !empty(trim((string) ($slot ?? ''))))
        <div class="flex items-center gap-2 mt-3 flex-wrap">
            {{ $filters ?? '' }}
            {{ $slot }}
        </div>
    @endif
</div>
