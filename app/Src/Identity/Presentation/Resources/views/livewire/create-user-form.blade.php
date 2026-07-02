<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
        <flux:button href="{{ route('identity.users.index') }}" variant="ghost" icon="chevron-left" wire:navigate />
        <div>
            <flux:heading size="xl">Nuevo Usuario</flux:heading>
            <flux:subheading>Registra una nueva identidad institucional en el sistema.</flux:subheading>
        </div>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:input wire:model="name" :label="__('Nombre Completo')" placeholder="Ej. Juan Pérez" required />
                <flux:input wire:model="email" type="email" :label="__('Correo Electrónico')" placeholder="juan.perez@css.gob.pa" required />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:input wire:model="password" type="password" :label="__('Contraseña')" placeholder="Mínimo 8 caracteres" required viewable />
                <flux:input wire:model="password_confirmation" type="password" :label="__('Confirmar Contraseña')" placeholder="Repite la contraseña" required viewable />
            </div>

            <div class="flex gap-4">
                <flux:checkbox wire:model="isActive" :label="__('Usuario Activo')" />
                <flux:checkbox wire:model="forcePasswordChange" :label="__('Forzar cambio de contraseña')" />
            </div>

            <flux:separator />

            <div class="space-y-4">
                <flux:heading size="md">{{ __('Roles de Sistema') }}</flux:heading>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($allRoles as $role)
                        <flux:checkbox wire:model="selectedRoles" :value="$role->name" :label="$role->name" size="sm" />
                    @endforeach
                </div>
                @error('selectedRoles') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <flux:button href="{{ route('identity.users.index') }}" variant="ghost" wire:navigate>
                    {{ __('Cancelar') }}
                </flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">{{ __('Registrar Usuario') }}</span>
                    <span wire:loading wire:target="save">{{ __('Procesando...') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
