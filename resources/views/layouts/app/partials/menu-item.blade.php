@if(isset($item['submenu']) && !empty($item['submenu']))
    <div x-data="{ open: {{ $item['is_active'] ? 'true' : 'false' }} }" class="w-full">
        <button @click="open = !open"
            class="wfm-sidebar-item w-full text-left {{ $item['is_active'] ? 'wfm-sidebar-item-active' : 'wfm-sidebar-item-inactive' }}">
            @if(isset($item['icon']))
                <flux:icon :name="$item['icon']" class="w-3.5 h-3.5 flex-shrink-0" />
            @endif
            <span class="flex-1 truncate text-left">{{ $item['label'] }}</span>
            <flux:icon name="chevron-down" class="w-3 h-3 text-wfm-surface-muted transition-transform duration-200" x-bind:class="{ 'rotate-180': open }" />
        </button>

        <div x-show="open"
             class="mt-0.5 space-y-0.5 ml-4 border-l border-wfm-surface-border pl-2">
            @foreach($item['submenu'] as $subItem)
                @if(isset($subItem['submenu']) && !empty($subItem['submenu']))
                    {{-- Nested submenu (like Administración > Empleados) --}}
                    <div x-data="{ open: {{ $subItem['is_active'] ? 'true' : 'false' }} }" class="w-full">
                        <button @click="open = !open"
                            class="wfm-sidebar-item w-full text-left {{ $subItem['is_active'] ? 'wfm-sidebar-item-active' : 'wfm-sidebar-item-inactive' }}">
                            @if(isset($subItem['icon']))
                                <flux:icon :name="$subItem['icon']" class="w-3.5 h-3.5 flex-shrink-0" />
                            @endif
                            <span class="flex-1 truncate text-left">{{ $subItem['label'] }}</span>
                            <flux:icon name="chevron-down" class="w-3 h-3 text-wfm-surface-muted transition-transform duration-200" x-bind:class="{ 'rotate-180': open }" />
                        </button>
                        <div x-show="open" class="mt-0.5 space-y-0.5 ml-3 border-l border-wfm-surface-border pl-1.5">
                            @foreach($subItem['submenu'] as $nestedItem)
                                @include('layouts.app.partials.menu-item', ['item' => $nestedItem])
                            @endforeach
                        </div>
                    </div>
                @else
                    @php($href = isset($subItem['route']) ? route($subItem['route'], $subItem['params'] ?? []) : '#')
                    <a href="{{ $href }}"
                       wire:navigate
                       class="wfm-sidebar-item {{ $subItem['is_active'] ? 'wfm-sidebar-item-active' : 'wfm-sidebar-item-inactive' }}">
                        @if(isset($subItem['icon']))
                            <flux:icon :name="$subItem['icon']" class="w-3.5 h-3.5 flex-shrink-0" />
                        @else
                            <span class="w-3.5 flex-shrink-0"></span>
                        @endif
                        <span class="flex-1 truncate">{{ $subItem['label'] }}</span>
                        @if(!empty($subItem['badge']))
                            <span class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 rounded-full bg-wfm-danger/10 text-[10px] font-bold text-wfm-danger">{{ $subItem['badge'] }}</span>
                        @endif
                    </a>
                @endif
            @endforeach
        </div>
    </div>
@else
    @php($href = isset($item['route']) ? route($item['route'], $item['params'] ?? []) : '#')
    <a href="{{ $href }}"
       wire:navigate
       class="wfm-sidebar-item {{ $item['is_active'] ? 'wfm-sidebar-item-active' : 'wfm-sidebar-item-inactive' }}">
        @if(isset($item['icon']))
            <flux:icon :name="$item['icon']" class="w-3.5 h-3.5 flex-shrink-0" />
        @endif
        <span class="flex-1 truncate">{{ $item['label'] }}</span>
        @if(!empty($item['badge']))
            <span class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 rounded-full bg-wfm-danger/10 text-[10px] font-bold text-wfm-danger">{{ $item['badge'] }}</span>
        @endif
    </a>
@endif
