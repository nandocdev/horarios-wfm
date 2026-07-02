<div>
    <div class="flex items-start max-md:flex-col">
        <div class="me-10 w-full pb-4 md:w-[220px]">
            <flux:navlist aria-label="{{ __('Settings') }}">
                <flux:navlist.item href="{{ route('identity.settings.profile') }}" wire:navigate>{{ __('Perfil') }}</flux:navlist.item>
                <flux:navlist.item href="{{ route('identity.settings.security') }}" wire:navigate current>{{ __('Seguridad') }}</flux:navlist.item>
                <flux:navlist.item href="{{ route('identity.settings.appearance') }}" wire:navigate>{{ __('Apariencia') }}</flux:navlist.item>
            </flux:navlist>
        </div>

        <flux:separator class="md:hidden" />

        <div class="flex-1 self-stretch max-md:pt-6">
            <flux:heading>{{ __('Actualizar Contraseña') }}</flux:heading>
            <flux:subheading>{{ __('Asegúrate de usar una contraseña larga y aleatoria para mantener tu cuenta segura.') }}</flux:subheading>

            <div class="mt-5 w-full max-w-lg">
                <form wire:submit="updatePassword" class="mt-6 space-y-6">
                    @if(! auth()->user()->force_password_change)
                        <flux:input wire:model="currentPassword" :label="__('Contraseña Actual')" type="password" required
                            autocomplete="current-password" viewable />
                    @endif

                    <flux:input wire:model="password" :label="__('Nueva Contraseña')" type="password" required
                        autocomplete="new-password" viewable />

                    <flux:input wire:model="passwordConfirmation" :label="__('Confirmar Contraseña')" type="password" required
                        autocomplete="new-password" viewable />

                    <div class="flex items-center gap-4">
                        <flux:button variant="primary" type="submit">
                            {{ __('Guardar') }}
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
