<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-dvh bg-wfm-surface dark:bg-zinc-950">
    @php($authUser = auth()->user())

    <div class="flex min-h-dvh">
        <flux:sidebar sticky collapsible="mobile"
            class="border-e border-wfm-surface-border bg-wfm-surface-card dark:bg-zinc-900 text-xs">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ $authUser ? route('dashboard') : route('home') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav class="space-y-0!">
                @if($authUser)
                    @foreach(\App\Helpers\MenuHelper::getSidebarItems($authUser, $menuCounts ?? []) as $item)
                        @include('layouts.app.partials.menu-item', ['item' => $item])
                    @endforeach
                @else
                    <a href="{{ route('home') }}" wire:navigate
                        class="wfm-sidebar-item {{ request()->routeIs('home') ? 'wfm-sidebar-item-active' : 'wfm-sidebar-item-inactive' }}">
                        <flux:icon name="home" class="w-3.5 h-3.5 flex-shrink-0" />
                        <span class="flex-1 truncate">Inicio</span>
                    </a>
                    <a href="{{ route('login') }}" class="wfm-sidebar-item wfm-sidebar-item-inactive">
                        <flux:icon name="arrow-right-end-on-rectangle" class="w-3.5 h-3.5 flex-shrink-0" />
                        <span class="flex-1 truncate">Iniciar sesión</span>
                    </a>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            @if($authUser)
                <flux:sidebar.nav class="space-y-0!">
                    @foreach(\App\Helpers\MenuHelper::getFooterItems($authUser) as $item)
                        @if(($item['route'] ?? '') === 'logout')
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <button type="submit" class="wfm-sidebar-item w-full text-left wfm-sidebar-item-inactive">
                                    <flux:icon name="arrow-right-start-on-rectangle" class="w-3.5 h-3.5 flex-shrink-0" />
                                    <span class="flex-1 truncate">{{ __('Cerrar Sesión') }}</span>
                                </button>
                            </form>
                        @else
                            <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}" wire:navigate
                                class="wfm-sidebar-item {{ $item['is_active'] ? 'wfm-sidebar-item-active' : 'wfm-sidebar-item-inactive' }}">
                                <flux:icon :name="$item['icon']" class="w-3.5 h-3.5 flex-shrink-0" />
                                <span class="flex-1 truncate">{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach

                    <livewire:core.shared.notification-bell />
                </flux:sidebar.nav>

                <div class="px-2.5 py-2 border-t border-wfm-surface-border">
                    <flux:dropdown position="bottom" align="start">
                        <flux:sidebar.profile :name="$authUser->name" :initials="$authUser->initials()"
                            icon:trailing="chevrons-up-down" data-test="sidebar-menu-button" />

                        <flux:menu>
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-xs">
                                <flux:avatar :name="$authUser->name" :initials="$authUser->initials()" class="!size-7" />
                                <div class="grid flex-1 text-start text-xs leading-tight">
                                    <flux:heading class="truncate text-xs">{{ $authUser->name }}</flux:heading>
                                    <flux:text class="truncate text-[10px]">{{ $authUser->email }}</flux:text>
                                </div>
                            </div>
                            <flux:menu.separator />
                            <flux:menu.radio.group>
                                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="!text-xs">
                                    {{ __('Configuración') }}
                                </flux:menu.item>
                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                        class="w-full cursor-pointer !text-xs" data-test="logout-button">
                                        {{ __('Cerrar Sesión') }}
                                    </flux:menu.item>
                                </form>
                            </flux:menu.radio.group>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            @endif
        </flux:sidebar>

        <div class="flex-1 flex flex-col min-h-dvh">

            <!-- Mobile Header -->
            <flux:header class="lg:hidden border-b border-wfm-surface-border bg-wfm-surface-card dark:bg-zinc-900 px-3 sm:px-4 py-2">
                <div class="flex items-center gap-2">
                    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
                    <x-app-logo href="{{ $authUser ? route('dashboard') : route('home') }}" wire:navigate />
                </div>
                <flux:spacer />
                <div class="flex items-center gap-2">
                    <livewire:core.shared.notification-bell />
                    <flux:dropdown position="bottom" align="end">
                        <flux:profile :initials="$authUser?->initials() ?? 'NA'" icon-trailing="chevron-down" />
                        <flux:menu>
                            <flux:menu.radio.group>
                                <div class="p-0 text-xs font-normal">
                                    <div class="flex items-center gap-2 px-2 py-2 text-start text-xs">
                                        <flux:avatar :name="$authUser?->name ?? 'Invitado'"
                                            :initials="$authUser?->initials() ?? 'NA'" class="!size-7" />
                                        <div class="grid flex-1 text-start text-xs leading-tight">
                                            <flux:heading class="truncate text-xs">{{ $authUser?->name ?? 'Invitado' }}
                                            </flux:heading>
                                            <flux:text class="truncate text-[10px]">
                                                {{ $authUser?->email ?? 'Sin sesión' }}
                                            </flux:text>
                                        </div>
                                    </div>
                                </div>
                            </flux:menu.radio.group>
                            @if($authUser)
                                <flux:menu.separator />
                                <flux:menu.radio.group>
                                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="!text-xs">
                                        {{ __('Configuración') }}
                                    </flux:menu.item>
                                </flux:menu.radio.group>
                                <flux:menu.separator />
                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                        class="w-full cursor-pointer !text-xs" data-test="logout-button">
                                        {{ __('Cerrar Sesión') }}
                                    </flux:menu.item>
                                </form>
                            @endif
                        </flux:menu>
                    </flux:dropdown>
                </div>

            </flux:header>





            {{ $slot }}
        <livewire:core.toast />
        <livewire:core.shared.user-tour-progress />
        @fluxScripts
        </div>
    </div>
</body>

</html>