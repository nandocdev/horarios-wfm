<x-layouts::auth :title="__('Confirmar contraseña')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Confirmar contraseña')" :description="__('Esta es una zona segura de la aplicación. Confirma tu contraseña antes de continuar.')" />

        <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Contraseña -->
            <flux:input
                name="password"
                :label="__('Contraseña')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Contraseña')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Confirmar') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>
