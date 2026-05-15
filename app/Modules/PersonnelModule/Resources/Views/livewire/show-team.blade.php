<div class="container mx-auto px-4 py-8">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <flux:button icon="chevron-left" variant="ghost" href="{{ route('organization.teams.index') }}" :inset="true" />
                <div>
                    <flux:heading size="xl" level="1">{{ $team->name }}</flux:heading>
                    <flux:subheading>Detalle y gestión de miembros del equipo operativo.</flux:subheading>
                </div>
            </div>

            <div class="flex gap-2">
                <flux:button href="{{ route('organization.teams.transfer', $team) }}" icon="user-plus">Gestionar Miembros</flux:button>
                <flux:button href="{{ route('organization.teams.edit', $team) }}" icon="pencil-square" variant="primary">Editar</flux:button>
                <flux:button wire:click="toggleStatus" variant="ghost" icon="{{ $team->is_active ? 'eye-slash' : 'eye' }}" title="{{ $team->is_active ? 'Desactivar' : 'Activar' }}" />
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Información General -->
            <div class="lg:col-span-1 space-y-6">
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Información General</flux:heading>
                    
                    <div class="space-y-4">
                        <flux:field label="Nombre">
                            <div class="text-zinc-900 dark:text-white font-medium">{{ $team->name }}</div>
                        </flux:field>

                        <flux:field label="Descripción">
                            <div class="text-zinc-500 text-sm">{{ $team->description ?: 'Sin descripción' }}</div>
                        </flux:field>

                        <flux:field label="Estado">
                            @if($team->is_active)
                                <flux:badge color="green">Activo</flux:badge>
                            @else
                                <flux:badge color="red">Inactivo</flux:badge>
                            @endif
                        </flux:field>

                        <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 space-y-2">
                            <div class="flex justify-between text-xs">
                                <span class="text-zinc-400">Creado:</span>
                                <span class="text-zinc-600 dark:text-zinc-300">{{ $team->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-zinc-400">Actualizado:</span>
                                <span class="text-zinc-600 dark:text-zinc-300">{{ $team->updated_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </flux:card>
            </div>

            <!-- Miembros del Equipo -->
            <div class="lg:col-span-2">
                <flux:card>
                    <div class="flex items-center justify-between mb-6">
                        <flux:heading size="lg">Miembros del Equipo ({{ $team->users->count() }})</flux:heading>
                        <flux:button href="{{ route('organization.teams.transfer', $team) }}" variant="ghost" size="sm" icon="plus">Añadir</flux:button>
                    </div>

                    @if($team->users->isNotEmpty())
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($team->users as $user)
                                <div class="py-4 flex items-center justify-between first:pt-0 last:pb-0">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-500 font-bold uppercase">
                                            {{ substr($user->name, 0, 2) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $user->name }}</div>
                                            <div class="text-xs text-zinc-500">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                    <flux:button variant="ghost" size="sm" icon="chevron-right" />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center py-12 text-zinc-400 gap-2">
                            <flux:icon.users class="w-12 h-12" />
                            <span>No hay miembros asignados a este equipo.</span>
                        </div>
                    @endif
                </flux:card>
            </div>
        </div>
    </div>
</div>