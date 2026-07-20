@props([
    'title' => null,
    'open' => false,
    'width' => 'md',
    'position' => 'right',
])

@php
    $widths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        'full' => 'max-w-full',
    ];
    $panelWidth = $widths[$width] ?? $widths['md'];
@endphp

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex"
    x-transition:enter="transition-opacity duration-200"
    x-transition:leave="transition-opacity duration-150">

    <div x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:leave="ease-in duration-150"
        class="fixed inset-0 bg-black/40"
        @click="open = false">
    </div>

    <div x-show="open"
        x-transition:enter="transform transition ease-out duration-200"
        x-transition:enter-start="translate-x-full"
        x-transition:leave="transform transition ease-in duration-150"
        x-transition:leave-end="translate-x-full"
        class="relative ml-auto h-full w-full {{ $panelWidth }} bg-wfm-surface-card shadow-xl border-l border-wfm-surface-border overflow-y-auto">

        @if($title)
            <div class="sticky top-0 z-10 flex items-center justify-between px-4 py-3 bg-wfm-surface-card border-b border-wfm-surface-border">
                <h3 class="text-sm font-semibold text-wfm-navy-800 dark:text-white">{{ $title }}</h3>
                <button @click="open = false" class="p-1 rounded hover:bg-wfm-surface-hover text-wfm-surface-muted hover:text-wfm-navy-700">
                    <flux:icon.x-mark class="w-4 h-4" />
                </button>
            </div>
        @endif

        <div class="p-4">
            {{ $slot }}
        </div>
    </div>
</div>
