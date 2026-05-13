<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    @php($authUser = auth()->user())

    <flux:sidebar sticky collapsible="mobile"
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ $authUser ? route('dashboard') : route('home') }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            @if($authUser)
                @foreach(\App\Helpers\MenuHelper::getSidebarItems($authUser, $menuCounts ?? []) as $item)
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
                                    <a href="{{ isset($subItem['route']) ? route($subItem['route'], $subItem['params'] ?? []) : '#' }}"
                                       wire:navigate
                                       class="flex items-center justify-between gap-3 px-3 py-2 rounded-md text-sm transition-colors
                                              {{ $subItem['is_active'] 
                                                 ? 'bg-zinc-200 dark:bg-zinc-800 text-zinc-900 dark:text-white font-semibold' 
                                                 : 'text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                                        
                                        <div class="flex items-center gap-3">
                                            @if(isset($subItem['icon']))
                                                <flux:icon :name="$subItem['icon']" class="w-4 h-4 {{ $subItem['is_active'] ? 'text-zinc-800 dark:text-white' : 'text-zinc-400' }}" />
                                            @endif
    
                                            <span>{{ $subItem['label'] }}</span>
                                        </div>

                                        @if(!empty($subItem['badge']))
                                            <flux:badge color="red" size="sm" class="ml-auto">{{ $subItem['badge'] }}</flux:badge>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <flux:sidebar.item :icon="$item['icon']" :href="isset($item['route']) ? route($item['route'], $item['params'] ?? []) : '#'"
                            :current="$item['is_active']" wire:navigate>
                            {{ $item['label'] }}
                        </flux:sidebar.item>
                    @endif
                @endforeach
            @else
                <flux:sidebar.item icon="home" :href="route('home')" :current="request()->routeIs('home')" wire:navigate>
                    Inicio
                </flux:sidebar.item>
                <flux:sidebar.item icon="arrow-right-end-on-rectangle" :href="route('login')" wire:navigate>
                    Iniciar sesión
                </flux:sidebar.item>
            @endif
        </flux:sidebar.nav>
        <flux:spacer />
        @if($authUser)
            <flux:sidebar.nav>
                @foreach(\App\Helpers\MenuHelper::getFooterItems($authUser) as $item)
                    <flux:sidebar.item 
                        :icon="$item['icon']" 
                        :href="isset($item['route']) ? route($item['route']) : '#'"
                        :current="$item['is_active']" 
                        wire:navigate>
                        {{ $item['label'] }}
                    </flux:sidebar.item>
                @endforeach

                <livewire:core.shared.notification-bell />
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="$authUser->name" />
        @endif
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <div class="flex items-center gap-2">
            <livewire:core.shared.notification-bell />
            <flux:dropdown position="top" align="end">
                <flux:profile :initials="$authUser?->initials() ?? 'NA'" icon-trailing="chevron-down" />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar :name="$authUser?->name ?? 'Invitado'"
                                    :initials="$authUser?->initials() ?? 'NA'" />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ $authUser?->name ?? 'Invitado' }}</flux:heading>
                                    <flux:text class="truncate">{{ $authUser?->email ?? 'Sin sesión' }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    @if($authUser)
                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                {{ __('Settings') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer" data-test="logout-button">
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    @else
                        <flux:menu.separator />

                        <flux:menu.item :href="route('login')" icon="arrow-right-end-on-rectangle" wire:navigate>
                            Iniciar sesión
                        </flux:menu.item>
                    @endif
                </flux:menu>
            </flux:dropdown>
    </flux:header>

    {{ $slot }}

    <livewire:core.toast />
    @fluxScripts
</body>

</html>