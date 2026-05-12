<div class="fixed top-5 right-5 z-[100] flex flex-col gap-3 w-full max-w-sm pointer-events-none" x-data="{
        remove(id) {
            $wire.removeToast(id);
        }
    }" @auto-hide-toast.window="setTimeout(() => remove($event.detail.id), 5000)">
    @foreach ($toasts as $toast)
        <div wire:key="{{ $toast['id'] }}" x-data="{ show: true }" x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-10 scale-95"
            x-transition:enter-end="opacity-100 translate-x-0 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-20"
            class="pointer-events-auto group relative flex items-start gap-3 overflow-hidden rounded-xl border border-zinc-200 bg-white/80 p-4 shadow-lg backdrop-blur-md dark:border-zinc-700 dark:bg-zinc-900/80">
            {{-- Icon indicator based on variant --}}
            <div @class([
                'mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full',
                'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' => $toast['variant'] === 'success',
                'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' => $toast['variant'] === 'danger',
                'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' => $toast['variant'] === 'warning',
                'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' => $toast['variant'] === 'info',
            ])>
                @if($toast['variant'] === 'success')
                    <flux:icon.check-circle variant="mini" />
                @elseif($toast['variant'] === 'danger')
                    <flux:icon.x-circle variant="mini" />
                @elseif($toast['variant'] === 'warning')
                    <flux:icon.exclamation-triangle variant="mini" />
                @else
                    <flux:icon.information-circle variant="mini" />
                @endif
            </div>

            <div class="flex-1">
                @if($toast['heading'])
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">
                        {{ $toast['heading'] }}
                    </h4>
                @endif
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $toast['message'] }}
                </p>
            </div>

            <button type="button" @click="show = false; setTimeout(() => remove('{{ $toast['id'] }}'), 300)"
                class="ml-auto shrink-0 text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300">
                <flux:icon.x-mark variant="mini" />
            </button>

            {{-- Progress bar for auto-hide --}}
            <div class="absolute bottom-0 left-0 h-1 bg-zinc-100 dark:bg-zinc-800 w-full overflow-hidden">
                <div class="h-full bg-current transition-all duration-5000 linear" @class([
                    'bg-green-500' => $toast['variant'] === 'success',
                    'bg-red-500' => $toast['variant'] === 'danger',
                    'bg-amber-500' => $toast['variant'] === 'warning',
                    'bg-blue-500' => $toast['variant'] === 'info',
                ])
                    id="progress-{{ $toast['id'] }}" style="animation: progress-shrink 5s linear forwards"></div>
            </div>
        </div>
    @endforeach

    <style>
        @keyframes progress-shrink {
            from {
                width: 100%;
            }

            to {
                width: 0%;
            }
        }
    </style>
</div>
