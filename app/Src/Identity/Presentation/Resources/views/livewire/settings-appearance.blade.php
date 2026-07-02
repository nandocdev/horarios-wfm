<div>
    <div class="flex items-start max-md:flex-col">
        <div class="me-10 w-full pb-4 md:w-[220px]">
            <flux:navlist aria-label="{{ __('Settings') }}">
                <flux:navlist.item href="{{ route('identity.settings.profile') }}" wire:navigate>{{ __('Perfil') }}</flux:navlist.item>
                <flux:navlist.item href="{{ route('identity.settings.security') }}" wire:navigate>{{ __('Seguridad') }}</flux:navlist.item>
                <flux:navlist.item href="{{ route('identity.settings.appearance') }}" wire:navigate current>{{ __('Apariencia') }}</flux:navlist.item>
            </flux:navlist>
        </div>

        <flux:separator class="md:hidden" />

        <div class="flex-1 self-stretch max-md:pt-6">
            <flux:heading>{{ __('Apariencia') }}</flux:heading>
            <flux:subheading>{{ __('Personaliza la apariencia de la aplicación.') }}</flux:subheading>

            <div class="mt-5 w-full max-w-lg">
                <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                    <flux:radio value="light" icon="sun">{{ __('Claro') }}</flux:radio>
                    <flux:radio value="dark" icon="moon">{{ __('Oscuro') }}</flux:radio>
                    <flux:radio value="system" icon="computer-desktop">{{ __('Sistema') }}</flux:radio>
                </flux:radio.group>
            </div>
        </div>
    </div>
</div>
