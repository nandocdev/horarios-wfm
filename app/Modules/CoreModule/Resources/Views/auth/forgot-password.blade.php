<x-layouts::auth :title="__('¿Olvidaste tu contraseña?')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('¿Olvidaste tu contraseña?')" :description="__('Dinos tu correo y te enviaremos un enlace para restablecerla')" />

        <!-- Estado de la sesión -->
        <x-auth-session-status class="text-sm" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Correo electrónico -->
            <flux:input
                name="email"
                :label="__('Correo electrónico')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="username"
                placeholder="correo@ejemplo.com"
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Enviar enlace de restablecimiento') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('¿Recordaste tu contraseña?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Inicia sesión') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
