@props([
    'alerts' => [],
    'title' => 'Alertas',
    'empty' => 'Sin alertas activas',
])

<div {{ $attributes->merge(['class' => 'card-wfm']) }}>
    @if($title)
        <div class="flex items-center justify-between px-3 py-2.5 border-b border-wfm-surface-border">
            <h3 class="text-sm font-semibold text-wfm-navy-800 dark:text-white flex items-center gap-2">
                <flux:icon.exclamation-triangle class="w-4 h-4 text-wfm-warning" />
                {{ $title }}
                @if(count($alerts) > 0)
                    <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-wfm-danger/10 text-[10px] font-bold text-wfm-danger">
                        {{ count($alerts) }}
                    </span>
                @endif
            </h3>
            {{ $actions ?? '' }}
        </div>
    @endif

    <div class="divide-y divide-wfm-surface-border">
        @forelse($alerts as $alert)
            @php
                $level = $alert['level'] ?? 'info';
                $styles = [
                    'critical' => ['bg' => 'bg-wfm-danger/5', 'dot' => 'bg-wfm-danger', 'text' => 'text-wfm-danger'],
                    'warning'  => ['bg' => 'bg-wfm-warning/5', 'dot' => 'bg-wfm-warning', 'text' => 'text-wfm-warning'],
                    'info'     => ['bg' => 'bg-wfm-info/5', 'dot' => 'bg-wfm-info', 'text' => 'text-wfm-info'],
                    'success'  => ['bg' => 'bg-wfm-success/5', 'dot' => 'bg-wfm-success', 'text' => 'text-wfm-success'],
                ];
                $s = $styles[$level] ?? $styles['info'];
            @endphp
            <div class="{{ $s['bg'] }} px-3 py-2.5 flex items-start gap-2.5">
                <span class="mt-1 w-2 h-2 rounded-full flex-shrink-0 {{ $s['dot'] }}"></span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium {{ $s['text'] }}">{{ $alert['message'] ?? $alert }}</p>
                    @if(isset($alert['time']))
                        <p class="text-[10px] text-wfm-surface-muted mt-0.5">{{ $alert['time'] }}</p>
                    @endif
                </div>
                @if(isset($alert['action']))
                    <button class="text-[10px] font-semibold text-wfm-navy-500 hover:text-wfm-navy-700 flex-shrink-0" {{ isset($alert['actionWire']) ? "wire:click=\"{$alert['actionWire']}\"" : '' }}>
                        {{ $alert['action'] }}
                    </button>
                @endif
            </div>
        @empty
            <div class="px-3 py-6 text-center text-xs text-wfm-surface-muted">
                <flux:icon.check-circle class="w-4 h-4 mx-auto mb-1 text-wfm-success" />
                {{ $empty }}
            </div>
        @endforelse
    </div>

    {{ $slot ?? '' }}
</div>
