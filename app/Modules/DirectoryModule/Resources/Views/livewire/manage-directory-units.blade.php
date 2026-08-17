<div class="space-y-6">
    <x-wfm.page-header title="Directorio de Unidades" description="Unidades operativas y administrativas de la CSS con sus servicios y puntos de contacto.">
        <x-slot:actions>
            <flux:button href="{{ route('directory.create') }}" wire:navigate variant="primary" icon="plus">Nueva Unidad</flux:button>
        </x-slot:actions>
        <x-slot:filters>
            <x-wfm.filter-bar>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por edificio, sector, piso, puerta o servicio..." class="!w-80" />
            </x-wfm.filter-bar>
        </x-slot:filters>
    </x-wfm.page-header>

    <x-wfm.table :headers="['Ubicación Física', 'Edificio', 'Servicios', 'Estado', 'Acciones']" compact>
        @forelse($units as $unit)
            <flux:table.row :key="$unit->id">
                <flux:table.cell>
                    <p class="text-sm font-semibold text-wfm-navy-800 dark:text-white">{{ $unit->display_name }}</p>
                    <p class="text-xs text-wfm-surface-muted font-mono">
                        {{ collect([$unit->sector, $unit->level])->filter()->implode(' · ') ?: 'Ubicación principal' }}
                    </p>
                </flux:table.cell>
                <flux:table.cell>
                    <flux:badge size="sm" color="indigo">{{ $unit->building->name }}</flux:badge>
                </flux:table.cell>
                <flux:table.cell>
                    <span class="text-xs text-wfm-navy-700">{{ $unit->services_count }} servicios</span>
                </flux:table.cell>
                <flux:table.cell>
                    <x-wfm.agent-status :status="$unit->is_active ? 'available' : 'offline'" :label="$unit->is_active ? 'Activa' : 'Inactiva'" size="xs" />
                </flux:table.cell>
                <flux:table.cell class="text-right">
                    <flux:button wire:click="openContactCard({{ $unit->id }})" variant="ghost" icon="eye" size="sm" title="Ver ficha de contacto" />
                    <flux:button href="{{ route('directory.edit', $unit->id) }}" wire:navigate variant="ghost" icon="pencil-square" size="sm" />
                    <flux:button wire:click="deleteUnit({{ $unit->id }})" wire:confirm="¿Eliminar permanentemente esta unidad y sus servicios?" variant="ghost" size="sm" icon="trash" />
                </flux:table.cell>
            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="5">
                    <x-wfm.empty icon="building-office" message="No se encontraron unidades registradas." />
                </flux:table.cell>
            </flux:table.row>
        @endforelse
    </x-wfm.table>

    <div class="mt-4">{{ $units->links() }}</div>

    <flux:modal name="contact-card-modal" wire:model="showContactCard" variant="flyout" class="!max-w-2xl">
        @if($viewingUnit)
            <div class="space-y-4">
                <flux:modal.header>
                    <flux:heading size="lg">Ficha de Contacto</flux:heading>
                    <flux:subheading>{{ $viewingUnit->display_name }}</flux:subheading>
                </flux:modal.header>

                <div class="max-h-[70vh] overflow-y-auto px-4 pb-4">
                    <x-directory::contact-card :unit="$viewingUnit" />
                </div>

                <flux:modal.footer>
                    <flux:button variant="ghost" wire:click="closeContactCard">Cerrar</flux:button>
                    <flux:button href="{{ route('directory.edit', $viewingUnit->id) }}" wire:navigate icon="pencil-square">Editar Unidad</flux:button>
                </flux:modal.footer>
            </div>
        @endif
    </flux:modal>
</div>