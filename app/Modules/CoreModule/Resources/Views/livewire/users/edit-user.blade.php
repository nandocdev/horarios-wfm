<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
        <flux:button href="{{ route('users.index') }}" variant="ghost" icon="chevron-left" wire:navigate />
        <div>
            <flux:heading size="xl">Editar Usuario</flux:heading>
            <flux:subheading>Actualiza el perfil y los permisos de {{ $user->name }}.</flux:subheading>
        </div>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:input 
                    wire:model="form.name" 
                    :label="__('Nombre Completo')" 
                    required 
                />

                <flux:input 
                    wire:model="form.email" 
                    type="email" 
                    :label="__('Correo Electrónico')" 
                    required 
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:input 
                    wire:model="form.password" 
                    type="password" 
                    :label="__('Nueva Contraseña (Opcional)')" 
                    placeholder="Dejar vacío para no cambiar" 
                    viewable
                />

                <div class="flex flex-col gap-3">
                    <flux:checkbox wire:model="form.is_active" :label="__('Usuario Activo')" />
                    <flux:checkbox wire:model="form.force_password_change" :label="__('Forzar cambio de contraseña')" />
                </div>
            </div>

            <flux:separator />

            <div class="space-y-4">
                <flux:heading size="md">{{ __('Roles de Sistema') }}</flux:heading>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($available_roles as $role)
                        <flux:checkbox 
                            wire:model="form.roles" 
                            :value="$role->name" 
                            :label="$role->name" 
                            size="sm"
                        />
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <flux:button href="{{ route('users.index') }}" variant="ghost" wire:navigate>
                    {{ __('Cancelar') }}
                </flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">{{ __('Guardar Cambios') }}</span>
                    <span wire:loading wire:target="save">{{ __('Guardando...') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
