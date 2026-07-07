<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Gestión de Cuotas de Almacenamiento</flux:heading>
            <flux:subheading>Define límites de espacio para usuarios y roles del sistema.</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Formulario de Asignación -->
        <flux:card class="space-y-4">
            <flux:heading size="lg">Asignar Límite</flux:heading>
            
            <div class="space-y-4">
                <flux:radio.group wire:model.live="type" label="Tipo de objetivo" variant="segmented">
                    <flux:radio value="role" label="Rol" />
                    <flux:radio value="user" label="Usuario" />
                </flux:radio.group>

                @if($type === 'user')
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar usuario..." icon="magnifying-glass" />
                @endif

                <flux:select wire:model="targetId" label="Seleccionar {{ $type === 'role' ? 'Rol' : 'Usuario' }}">
                    <option value="">Seleccione una opción...</option>
                    @foreach($this->targets as $target)
                        <option value="{{ $target->id }}">{{ $target->name }}</option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="limitMb" type="number" label="Límite (MB)" placeholder="Ej. 500" suffix="MB" />

                <flux:button variant="primary" class="w-full" wire:click="save">Guardar Regla</flux:button>
            </div>
        </flux:card>

        <!-- Listado de Reglas -->
        <div class="lg:col-span-2">
            <flux:card class="p-0 overflow-hidden">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Tipo</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Objetivo</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Límite</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($quotas as $quota)
                            <flux:table.row class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 py-2">
                                <flux:table.cell class="py-2">
                                    <flux:badge size="sm" :color="$quota->target_type === 'role' ? 'blue' : 'slate'">
                                        {{ strtoupper($quota->target_type) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="py-2">
                                    @php
                                        $name = $quota->target_type === 'role' 
                                            ? \App\Modules\CoreModule\Models\Role::find($quota->target_id)?->name 
                                            : \App\Modules\CoreModule\Models\User::find($quota->target_id)?->name;
                                    @endphp
                                    <span class="font-medium">{{ $name ?? 'N/A' }}</span>
                                </flux:table.cell>
                                <flux:table.cell class="py-2">
                                    {{ \App\Modules\FilesystemModule\Models\StorageQuota::bytesToMb($quota->quota_limit) }} MB
                                </flux:table.cell>
                                <flux:table.cell class="py-2">
                                    <flux:button variant="ghost" icon="trash" size="sm" wire:click="delete({{ $quota->id }})" />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                @if($quotas->isEmpty())
                    <div class="p-8 text-center text-slate-500">
                        No hay reglas de cuota definidas. Se aplicará el límite global de 100MB.
                    </div>
                @endif

                <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $quotas->links() }}
                </div>
            </flux:card>
        </div>
    </div>
</div>
