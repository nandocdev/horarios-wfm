<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-dvh bg-white dark:bg-zinc-800">
    @php($authUser = auth()->user())

    <div class="flex min-h-dvh">
    <flux:sidebar sticky collapsible="mobile"
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ $authUser ? route('dashboard') : route('home') }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            @if($authUser)
                @foreach(\App\Helpers\MenuHelper::getSidebarItems($authUser, $menuCounts ?? []) as $item)
                    @include('layouts.app.partials.menu-item', ['item' => $item])
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

    <div class="flex-1 flex flex-col min-h-dvh">
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
                                    <flux:avatar :name="$authUser?->name ?? 'Invitado'" :initials="$authUser?->initials() ?? 'NA'" />
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
                                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                            </flux:menu.radio.group>
                            <flux:menu.separator />
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">{{ __('Log out') }}</flux:menu.item>
                            </form>
                        @else
                            <flux:menu.separator />
                            <flux:menu.item :href="route('login')" icon="arrow-right-end-on-rectangle" wire:navigate>Iniciar sesión</flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>
        </flux:header>

        {{ $slot }}
    </div>
    </div>

    <livewire:core.toast />
    @fluxScripts
</body>

</html>