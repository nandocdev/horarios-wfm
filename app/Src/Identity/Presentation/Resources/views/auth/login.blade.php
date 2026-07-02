<x-layouts::auth :title="__('Iniciar sesión')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Inicia sesión en tu cuenta')" :description="__('Ingresa tu correo y contraseña para acceder')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('identity.login.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="email"
                :label="__('Correo electrónico')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="correo@ejemplo.com"
            />

            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Contraseña')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Contraseña')"
                    viewable
                />

                @if (Route::has('identity.password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('identity.password.request')" wire:navigate>
                        {{ __('¿Olvidaste tu contraseña?') }}
                    </flux:link>
                @endif
            </div>

            <flux:checkbox name="remember" :label="__('Recordarme')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Iniciar sesión') }}
                </flux:button>
            </div>
        </form>

        @if (Route::has('identity.register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
                <span>{{ __('¿No tienes una cuenta?') }}</span>
                <flux:link :href="route('identity.register')" wire:navigate>{{ __('Regístrate') }}</flux:link>
            </div>
        @endif
    </div>
</x-layouts::auth>
