<x-layouts::auth :title="__('Restablecer contraseña')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Restablecer contraseña')" :description="__('Crea una nueva contraseña para tu cuenta')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Token de restablecimiento -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <!-- Correo electrónico -->
            <flux:input name="email" :label="__('Correo electrónico')" :value="old('email', request()->email)"
                type="email" required autofocus autocomplete="username" placeholder="correo@ejemplo.com" />

            <!-- Contraseña -->
            <flux:input name="password" :label="__('Nueva contraseña')" type="password" required
                autocomplete="new-password" :placeholder="__('Nueva contraseña')" viewable />

            <!-- Confirmar contraseña -->
            <flux:input name="password_confirmation" :label="__('Confirmar nueva contraseña')" type="password" required
                autocomplete="new-password" :placeholder="__('Confirmar nueva contraseña')" viewable />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Restablecer contraseña') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>
