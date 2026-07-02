<div>
    <div class="flex items-start max-md:flex-col">
        <div class="me-10 w-full pb-4 md:w-[220px]">
            <flux:navlist aria-label="{{ __('Settings') }}">
                <flux:navlist.item href="{{ route('identity.settings.profile') }}" wire:navigate current>{{ __('Perfil') }}</flux:navlist.item>
                <flux:navlist.item href="{{ route('identity.settings.security') }}" wire:navigate>{{ __('Seguridad') }}</flux:navlist.item>
                <flux:navlist.item href="{{ route('identity.settings.appearance') }}" wire:navigate>{{ __('Apariencia') }}</flux:navlist.item>
            </flux:navlist>
        </div>

        <flux:separator class="md:hidden" />

        <div class="flex-1 self-stretch max-md:pt-6">
            <flux:heading>{{ __('Mi Perfil') }}</flux:heading>
            <flux:subheading>{{ __('Gestiona tu información personal e institucional.') }}</flux:subheading>

            <div class="mt-5 w-full max-w-lg">
                <form wire:submit="save" class="space-y-8 mt-6">
                    <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 space-y-4">
                        <flux:heading size="lg">{{ __('Datos de Acceso') }}</flux:heading>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:input wire:model="name" :label="__('Nombre Completo')" type="text" required autofocus autocomplete="name" />
                            <flux:input wire:model="email" :label="__('Correo Electrónico')" type="email" required autocomplete="email" />
                        </div>

                        <div x-data="{ unverified: {{ Auth::user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! Auth::user()->hasVerifiedEmail() ? 'true' : 'false' }} }">
                            <template x-if="unverified">
                                <div class="bg-amber-50 dark:bg-amber-900/20 p-3 rounded border border-amber-200 dark:border-amber-800">
                                    <flux:text size="sm" class="text-amber-800 dark:text-amber-300">
                                        {{ __('Tu dirección de correo no está verificada.') }}
                                        <flux:link class="ml-2" wire:click.prevent="resendVerificationNotification">
                                            {{ __('Re-enviar correo de verificación.') }}
                                        </flux:link>
                                    </flux:text>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 space-y-4">
                        <flux:heading size="lg">{{ __('Información de Contacto') }}</flux:heading>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:input wire:model="phone" :label="__('Teléfono Fijo')" placeholder="Ej. 222-2222" />
                            <flux:input wire:model="mobilePhone" :label="__('Teléfono Móvil')" placeholder="Ej. 6666-6666" />
                        </div>
                        <flux:textarea wire:model="address" :label="__('Dirección Residencial')" placeholder="Escribe tu dirección detallada aquí..." rows="3" />
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                        <flux:button variant="primary" type="submit">
                            {{ __('Guardar Cambios') }}
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
