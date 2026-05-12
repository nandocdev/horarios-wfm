<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'WFM CSS') }}</title>

    <!-- Fuentes institucionales (Inter recomendada para legibilidad UI) -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

    <!-- Assets y FluxUI -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
    <flux:header container class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:brand href="#" logo="{{ asset('img/logo.png') }}" name="{{ config('app.name') }}"
            class="max-lg:hidden dark:hidden" />
        <flux:brand href="#" logo="{{ asset('img/logo.png') }}" name="{{ config('app.name') }}"
            class="max-lg:hidden! hidden dark:flex" />

        <flux:navbar class="-mb-px max-lg:hidden">
            <flux:navbar.item icon="home" href="{{ route('dashboard') ?? '#' }}" wire:navigate current>
                Dashboard
            </flux:navbar.item>

            @can('viewAny', App\Modules\Employees\Models\Employee::class)
                <flux:navbar.item icon="users" href="{{ route('employees.index') ?? '#' }}" wire:navigate>
                    Personal
                </flux:navbar.item>
            @endcan

            @can('viewAny', App\Modules\Teams\Models\Team::class)
                <flux:navbar.item icon="rectangle-group" href="{{ route('teams.index') ?? '#' }}" wire:navigate>
                    Equipos
                </flux:navbar.item>
            @endcan

            @can('viewAny', App\Modules\Reports\Models\Report::class)
                <flux:navbar.item icon="document-chart-bar" href="{{ route('reports.index') ?? '#' }}" wire:navigate>
                    Reportes
                </flux:navbar.item>
            @endcan

            <flux:separator vertical variant="subtle" class="my-2" />

            @auth
                @can('viewAny', App\Modules\Admin\Models\User::class)
                    <flux:navbar.item icon="cog-6-tooth" href="{{ route('admin.users.index') ?? '#' }}" wire:navigate>
                        Administración
                    </flux:navbar.item>
                @endcan
            @endauth
        </flux:navbar>

        <flux:spacer />


        @auth
            <flux:dropdown position="top" align="start">
                <flux:profile initials="{{ auth()->user()->initials() }}" icon-trailing="chevron-down"
                    name="{{ auth()->user()->name }}" />

                <flux:menu>
                    <flux:menu.item icon="user" href="{{ route('profile.edit') ?? '#' }}" wire:navigate>
                        Mi Perfil
                    </flux:menu.item>

                    <flux:menu.separator />

                    <!-- SIEMPRE proteger el logout con POST y CSRF (Requisito RS-01) -->
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            Cerrar sesión
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        @endauth

        @guest
            <flux:button href="{{ route('login') }}" variant="primary" icon="arrow-right-end-on-rectangle" wire:navigate>
                Iniciar sesión
            </flux:button>
        @endguest
    </flux:header>

    <flux:sidebar sticky collapsible="mobile"
        class="lg:hidden bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.header>
            <flux:sidebar.brand href="#" logo="https://fluxui.dev/img/demo/logo.png"
                logo:dark="https://fluxui.dev/img/demo/dark-mode-logo.png" name="Acme Inc." />

            <flux:sidebar.collapse
                class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item icon="home" href="#" current>Home</flux:sidebar.item>
            <flux:sidebar.item icon="inbox" badge="12" href="#">Inbox</flux:sidebar.item>
            <flux:sidebar.item icon="document-text" href="#">Documents</flux:sidebar.item>
            <flux:sidebar.item icon="calendar" href="#">Calendar</flux:sidebar.item>

            <flux:sidebar.group expandable heading="Favorites" class="grid">
                <flux:sidebar.item href="#">Marketing site</flux:sidebar.item>
                <flux:sidebar.item href="#">Android app</flux:sidebar.item>
                <flux:sidebar.item href="#">Brand guidelines</flux:sidebar.item>
            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:sidebar.spacer />

        <flux:sidebar.nav>
            <flux:sidebar.item icon="cog-6-tooth" href="#">Settings</flux:sidebar.item>
            <flux:sidebar.item icon="information-circle" href="#">Help</flux:sidebar.item>
        </flux:sidebar.nav>
    </flux:sidebar>

    <flux:main container>
        {{ $slot }}
    </flux:main>



    @fluxScripts
</body>
