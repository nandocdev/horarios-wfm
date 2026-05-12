<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <flux:link href="{{ route('organization.teams.index') }}" variant="ghost">
                        ← Volver
                    </flux:link>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $team->name }}</h1>
                </div>
                <div class="flex space-x-2">

                    <flux:button.group>
                        <flux:button href="{{ route('organization.teams.members', $team) }}" icon="users">
                        </flux:button>
                        <flux:button href="{{ route('organization.teams.transfer', $team) }}" icon="arrows-right-left">
                        </flux:button>
                        <flux:button href="{{ route('organization.teams.edit', $team) }}" icon="pencil-square">
                        </flux:button>
                        <flux:button wire:click="toggleStatus" icon="{{ $team->is_active ? 'eye-slash' : 'eye' }}">
                        </flux:button>
                    </flux:button.group>


                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Información General</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nombre</dt>
                            <dd class="text-sm text-gray-900">{{ $team->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Descripción</dt>
                            <dd class="text-sm text-gray-900">{{ $team->description ?: 'Sin descripción' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Estado</dt>
                            <dd class="text-sm">
                                @if($team->is_active)
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Activo
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Inactivo
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Fecha de creación</dt>
                            <dd class="text-sm text-gray-900">{{ $team->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Última actualización</dt>
                            <dd class="text-sm text-gray-900">{{ $team->updated_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Miembros ({{ $team->users->count() }})</h3>
                    @if($team->users->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($team->users as $user)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                    </div>
                                    <flux:link href="#" {{-- TODO: route to user profile --}} variant="ghost" size="sm">
                                        Ver
                                    </flux:link>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No hay miembros en este equipo.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>