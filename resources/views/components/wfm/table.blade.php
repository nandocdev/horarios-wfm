@props([
    'headers' => [],
    'rows' => [],
    'paginate' => null,
    'striped' => false,
    'hover' => true,
    'compact' => false,
    'empty' => 'Sin datos disponibles',
    'loading' => false,
    'custom' => false,
])

@php
    if (! is_null($paginate)) {
        $rows = $paginate->items();
    }
@endphp

<div {{ $attributes->merge(['class' => 'card-wfm overflow-hidden']) }}>
    <div class="overflow-x-auto touch-scroll p-2 sm:p-3">
        <table class="w-full divide-y divide-wfm-surface-border">
            @if(count($headers) > 0)
                <thead>
                    <tr class="bg-wfm-surface">
                        @foreach($headers as $header)
                            <th scope="col"
                                class="px-2.5 sm:px-3 py-2 sm:py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-wfm-surface-muted whitespace-nowrap">
                                @if(is_array($header))
                                    {{ $header['label'] }}
                                @else
                                    {{ $header }}
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif

            @if($custom)
                {{ $slot }}
            @elseif($slot->isNotEmpty())
                <tbody class="divide-y divide-wfm-surface-border bg-wfm-surface-card">
                    {{ $slot }}
                </tbody>
            @else
                <tbody class="divide-y divide-wfm-surface-border bg-wfm-surface-card">
                    @if($loading)
                        <tr>
                            <td colspan="{{ count($headers) ?: 1 }}" class="p-3 text-center">
                                <div class="flex items-center justify-center gap-2 text-xs sm:text-sm text-wfm-surface-muted">
                                    <flux:icon.arrow-path class="w-4 h-4 motion-safe:animate-spin" />
                                    Cargando...
                                </div>
                            </td>
                        </tr>
                    @elseif(count($rows) === 0)
                        <tr>
                            <td colspan="{{ count($headers) ?: 1 }}">
                                <x-wfm.empty :message="$empty" />
                            </td>
                        </tr>
                    @else
                        @foreach($rows as $index => $row)
                            <tr class="{{ $striped && $index % 2 === 1 ? 'bg-wfm-surface/50' : '' }} {{ $hover ? 'hover:bg-wfm-surface-hover' : '' }} transition-colors">
                                @if(is_array($row))
                                    @foreach($row as $cell)
                                        <td class="{{ $compact ? 'px-2 py-1.5 sm:px-2.5 sm:py-2' : 'px-2.5 py-2 sm:px-3 sm:py-2.5' }} whitespace-nowrap text-xs">
                                            {{ $cell }}
                                        </td>
                                    @endforeach
                                @else
                                    <td class="{{ $compact ? 'px-2 py-1.5 sm:px-2.5 sm:py-2' : 'px-2.5 py-2 sm:px-3 sm:py-2.5' }} text-xs">
                                        {{ $row }}
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            @endif
        </table>
    </div>

    @if(($footer ?? false))
        <div class="px-2.5 sm:px-3 py-2 sm:py-2.5 border-t border-wfm-surface-border bg-wfm-surface text-xs text-wfm-surface-muted">
            {{ $footer }}
        </div>
    @elseif(! is_null($paginate) && $paginate->hasPages())
        <div class="px-2.5 sm:px-3 py-2 sm:py-2.5 border-t border-wfm-surface-border bg-wfm-surface">
            {{ $paginate->links() }}
        </div>
    @endif
</div>
