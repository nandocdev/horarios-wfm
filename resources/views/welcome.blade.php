<x-layouts.guest>
    @php
        $shoutoutItems = $shoutouts ?? [];
        $employeeName = $employee->name ?? 'Colaborador';
        $employeeDesc = $employee->description ?? 'Anuncio del empleado destacado del mes.';
        $employeeImg = $employee->image ?? asset('img/placeholder.png');
        $newsItems = $news ?? [];
    @endphp

    <div class="flex w-full flex-col">

        <!-- Hero Section (Se mantiene intacto según requerimiento) -->
        <section class="relative overflow-hidden border-b border-zinc-200 dark:border-zinc-800">
            <div class="absolute inset-x-0 top-0 h-48 bg-gradient-to-r from-cyan-800 to-cyan-500 to-transparent">
            </div>

            <div class="relative mx-auto max-w-[85rem] px-4 py-8 sm:px-6 lg:px-8 sm:py-10 lg:py-12">
                <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
                    <flux:card class="shadow-sm">
                        <flux:badge color="blue" size="sm" inset="top bottom" class="uppercase tracking-[0.18em]">
                            Reconocimiento destacado
                        </flux:badge>

                        <flux:heading size="xl" level="2" class="mt-4">
                            ¡Felicidades, {{ $employeeName }}!
                        </flux:heading>

                        <flux:text class="mt-4 max-w-2xl text-base">
                            {{ $employeeDesc }}
                        </flux:text>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <flux:button variant="primary" href="#">
                                Ver publicación completa
                            </flux:button>
                            <flux:button variant="outline" href="#">
                                Explorar reconocimientos
                            </flux:button>
                        </div>
                    </flux:card>

                    <flux:card class="p-2 shadow-sm">
                        <img src="{{ $employeeImg }}" alt="Reconocimiento de {{ $employeeName }}"
                            class="h-full min-h-[18rem] w-full rounded-xl object-cover">
                    </flux:card>
                </div>
            </div>
        </section>

        <!-- Contenido Principal: Grid de 2 Columnas -->
        <div class="mx-auto w-full max-w-[85rem] px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-3">

                <!-- COLUMNA IZQUIERDA: Noticias y Slot -->
                <div class="space-y-10 lg:col-span-2">

                    <!-- Sección de Noticias -->
                    <section class="space-y-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <flux:heading size="xl" level="2">Noticias Internas</flux:heading>
                                <flux:subheading>Actualizaciones y novedades del Centro de Contactos.</flux:subheading>
                            </div>

                            <flux:button href="#" variant="ghost" icon-trailing="chevron-right" size="sm" wire:navigate>
                                Ver todas
                            </flux:button>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            @forelse ($newsItems as $item)
                                <flux:card
                                    class="group flex flex-col overflow-hidden p-0 transition hover:-translate-y-0.5 hover:shadow-md">
                                    <div
                                        class="h-48 w-full overflow-hidden border-b border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                                        <img src="{{ data_get($item, 'image') }}" alt="{{ data_get($item, 'title') }}"
                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]">
                                    </div>
                                    <div class="flex flex-1 flex-col p-5">
                                        <div class="mb-3">
                                            <flux:badge color="blue" size="sm" inset="top bottom">Actualización</flux:badge>
                                        </div>
                                        <flux:heading size="lg">{{ data_get($item, 'title') }}</flux:heading>
                                        <flux:text class="mt-2 line-clamp-2 flex-1">{{ data_get($item, 'excerpt') }}
                                        </flux:text>
                                        <div class="mt-5 flex items-center justify-between gap-3">
                                            <flux:button href="#" variant="ghost" icon-trailing="arrow-right" size="sm"
                                                class="-ml-3" wire:navigate>
                                                Leer más
                                            </flux:button>
                                            <flux:text class="text-xs">Hoy</flux:text>
                                        </div>
                                    </div>
                                </flux:card>
                            @empty
                                <flux:card
                                    class="col-span-full flex flex-col items-center justify-center border-dashed py-12 text-center">
                                    <flux:heading size="md">No hay noticias recientes</flux:heading>
                                    <flux:subheading class="mt-1">Las novedades operativas aparecerán aquí.
                                    </flux:subheading>
                                </flux:card>
                            @endforelse
                        </div>
                    </section>

                    <!-- Main Content Slot (Si aplica) -->
                    @if(isset($slot) && $slot->toHtml() !== '')
                        <main id="content" role="main">
                            {{ $slot }}
                        </main>
                    @endif
                </div>

                <!-- COLUMNA DERECHA: Sidebar -->
                <aside class="space-y-6">
                    <!-- Accesos Rápidos (Adaptados a menú vertical) -->
                    <flux:card class="shadow-sm">
                        <flux:heading size="lg">Accesos rápidos</flux:heading>
                        <flux:subheading class="mb-4">Atajos frecuentes operativos.</flux:subheading>

                        <div class="flex flex-col gap-1">
                            @foreach ([['icon' => 'users', 'label' => 'Proyectos'],
                                    ['icon' => 'map', 'label' => 'Sistemas Internos'],
                                    ['icon' => 'lifebuoy', 'label' => 'Soporte IT'], ['icon' => 'chart-bar', 'label' => 'Reportes WFM'], ['icon' => 'folder', 'label' => 'Directorio'], ['icon' => 'calendar', 'label' => 'Gestión de Turnos'],
                                ] as $item)
                                    <flux:button variant="subtle" icon="{{ $item['icon'] }}" class="w-full justify-start" href="#">
                                        {{ $item['label'] }}
                                    </flux:button>
                            @endforeach
                        </div>
                    </flux:card>

                    <!--  Encuesta (Interactiva Livewire) -->
                <flux:card>
                        <flux:heading size="lg">Encuesta Rápida</flux:heading>
                        <flux:subheading class="mb-4">Comparte tu opinión operativa.</flux:subheading>

                           <form wire:submit="submitPoll" class="space-y-4">
                        <flux:radio.group wire:model="pollForm.answer" variant="cards" class="flex-col">
                                <flux:radio value="in_favor" label="A favor" />
                                <flux:radio value="against" label="En contra" />
                                <flux:radio value="unsure" label="No estoy seguro" />
                            </flux:radio.group>
                            <flux:button type="submit" variant="primary" class="w-full">
                                Votar
                        </flux:button>
                        </form>
                    </flux:card>

                    <!--     Tip Operativo -->
                <flux:card class="border-blue-100 bg-blue-50/50 dark:border-blue-900/50 dark:bg-blue-900/20">

                                                   <flux:badge color="blue" size="sm" inset="top bottom">Tip WFM</flux:badge>
                        <flux:heading size="md" class="mt-3">Navegación ágil</flux:heading>
                    <flux:text class="mt-1">Utiliza los accesos rápidos para entrar a tu gestión de turnos sin depender del menú superior.</flux:text>
                    </flux:card>
                </aside>

            </div>
        </div>

        <!-- Abajo: Shoutouts -->
        <section class="mx-auto w-full max-w-[85rem] px-4 pb-16 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-end justify-between gap-4">
                <div>
                    <flux:heading size="lg">Shout-outs</flux:heading>
                    <flux:subheading>Mensajes rápidos de reconocimiento entre equipos operativos.</flux:subheading>
                </div>
            </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($shoutoutItems as $item)

            <flux:card class="transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="mb-4 flex items-center gap-3">
                    <flux:avatar src="{{ data_get($item, 'avatar') }}" name="{{ data_get($item, 'name') }}" class="ring-2 ring-blue-500/10" />
                    <div>
                        <flux:heading size="sm">{{ data_get($item, 'name') }}</flux:heading>
                        <flux:text class="text-xs uppercase tracking-[0.16em]">Reconocimiento</flux:text>
                    </div>
                </div>

                <flux:text class="line-clamp-3">
                    {{ data_get($item, 'message') }}
                </flux:text>
            </flux:card>
            @empty

            <flux:card class="col-span-full border-dashed bg-zinc-50/50 py-8 text-center dark:bg-zinc-800/50">
                <flux:heading size="sm">Todavía no hay shout-outs publicados.</flux:heading>
                <flux:subheading class="mt-1">Cuando alguien reconozca a un compañero, aparecerá aquí.</flux:subheading>
            </flux:card>
            @endforelse
            </div>
    </section>

</div>
</x-layouts.guest>
