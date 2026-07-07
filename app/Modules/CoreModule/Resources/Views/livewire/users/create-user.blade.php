<div class="max-w-2xl mx-auto space-y-8">
    <div class="flex items-center gap-4">
        <flux:button href="{{ route('users.index') }}" variant="ghost" icon="chevron-left" wire:navigate />
        <div>
            <flux:heading size="xl">Nuevo Usuario</flux:heading>
            <flux:subheading>Registra una nueva identidad institucional en el sistema.</flux:subheading>
        </div>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input 
                    wire:model="form.name" 
                    :label="__('Nombre Completo')" 
                    placeholder="Ej. Juan Pérez" 
                    required 
                />

                <flux:input 
                    wire:model="form.email" 
                    type="email" 
                    :label="__('Correo Electrónico')" 
                    placeholder="juan.perez@css.gob.pa" 
                    required 
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input 
                    wire:model="form.password" 
                    type="password" 
                    :label="__('Contraseña')" 
                    placeholder="Generar automáticamente si se deja vacío" 
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
                    <span wire:loading.remove wire:target="save">{{ __('Registrar Usuario') }}</span>
                    <span wire:loading wire:target="save">{{ __('Procesando...') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
