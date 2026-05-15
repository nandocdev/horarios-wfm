@if(isset($item['submenu']) && !empty($item['submenu']))
    <div x-data="{ open: {{ $item['is_active'] ? 'true' : 'false' }} }" class="w-full">
        <button @click="open = !open"
            class="flex items-center w-full px-3 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition-colors group">
            
            @if(isset($item['icon']))
                <flux:icon :name="$item['icon']" class="w-4 h-4 mr-3 text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-200" />
            @endif

            <span class="flex-1 text-left">{{ $item['label'] }}</span>

            <flux:icon name="chevron-down"
                class="w-3.5 h-3.5 text-zinc-400 transition-transform duration-200"
                x-bind:class="{ 'rotate-180': open }" />
        </button>

        <div x-show="open" 
             class="mt-1 space-y-1 ml-4 border-l border-zinc-200 dark:border-zinc-700 pl-2">
            @foreach($item['submenu'] as $subItem)
                @include('layouts.app.partials.menu-item', ['item' => $subItem])
            @endforeach
        </div>
    </div>
@else
    @php($href = isset($item['route']) ? route($item['route'], $item['params'] ?? []) : '#')
    <a href="{{ $href }}"
       wire:navigate
       class="flex items-center justify-between gap-3 px-3 py-2 rounded-md text-sm transition-colors
              {{ $item['is_active'] 
                 ? 'bg-zinc-200 dark:bg-zinc-800 text-zinc-900 dark:text-white font-semibold' 
                 : 'text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
        
        <div class="flex items-center gap-3">
            @if(isset($item['icon']))
                <flux:icon :name="$item['icon']" class="w-4 h-4 {{ $item['is_active'] ? 'text-zinc-800 dark:text-white' : 'text-zinc-400' }}" />
            @endif

            <span>{{ $item['label'] }}</span>
        </div>

        @if(!empty($item['badge']))
            <flux:badge color="red" size="sm" class="ml-auto">{{ $item['badge'] }}</flux:badge>
        @endif
    </a>
@endif
