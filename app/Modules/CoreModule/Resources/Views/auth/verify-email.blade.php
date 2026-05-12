<x-layouts::auth :title="__('Verifica tu correo')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Verifica tu correo electrónico')" :description="__('Gracias por registrarte. Por favor verifica tu cuenta haciendo clic en el enlace que te enviamos.')" />

        @if (session('status') == 'verification-link-sent')
            <x-text class="text-sm font-medium text-center text-green-600 dark:text-green-400">
                {{ __('Se ha enviado un nuevo enlace de verificación a la dirección de correo proporcionada.') }}
            </x-text>
        @endif

        <div class="flex flex-col gap-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Reenviar correo de verificación') }}
                </flux:button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button type="submit" variant="ghost" class="w-full">
                    {{ __('Cerrar sesión') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::auth>
