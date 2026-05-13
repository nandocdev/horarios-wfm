<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        @php
            $maintenance = \App\Modules\CoreModule\Models\AppSetting::get('maintenance_mode');
        @endphp
        
        @if($maintenance && ($maintenance['enabled'] ?? false))
            <div class="mb-6 p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-center gap-3 animate-pulse">
                <flux:icon.exclamation-triangle class="text-amber-500 w-4 h-4" />
                <flux:text class="text-amber-600 dark:text-amber-400 font-black text-[10px] uppercase tracking-widest">
                    Modo Mantenimiento Activo - Acceso Público Restringido
                </flux:text>
            </div>
        @endif

        {{ $slot ?? '' }}
        @yield('content')
    </flux:main>
</x-layouts::app.sidebar>
