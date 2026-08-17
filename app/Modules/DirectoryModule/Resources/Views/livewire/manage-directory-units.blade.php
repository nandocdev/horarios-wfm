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

    <x-wfm.table :headers="['Servicio', 'Edificio', 'Ubicación', 'Horario', 'Contacto', 'Acciones']" compact>
        @forelse($services as $service)
            <flux:table.row :key="$service->id">
                <flux:table.cell>
                    <p class="text-sm font-semibold text-wfm-navy-800 dark:text-white">{{ $service->name }}</p>
                    @if($service->door_id)
                        <p class="text-xs text-wfm-surface-muted font-mono">Puerta: {{ $service->door_id }}</p>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    <flux:badge size="sm" color="indigo">{{ $service->unit->building->name }}</flux:badge>
                </flux:table.cell>
                <flux:table.cell class="text-xs text-wfm-navy-700">
                    {{ collect([$service->unit->sector, $service->unit->level])->filter()->implode(' · ') ?: '—' }}
                </flux:table.cell>
                <flux:table.cell>
                    <span class="text-xs text-wfm-navy-700 font-mono">{{ $service->attention_hours }}</span>
                    @if($service->results_hours)
                        <p class="text-[10px] text-wfm-surface-muted font-mono">R: {{ $service->results_hours }}</p>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    <p class="text-xs text-wfm-navy-700">{{ $service->contact_role }} <span class="font-mono text-[10px]">· {{ $service->contact_extension }}</span></p>
                    @if($service->contact_email)
                        <a href="mailto:{{ $service->contact_email }}" class="text-[10px] text-wfm-info hover:underline break-all">{{ $service->contact_email }}</a>
                    @endif
                </flux:table.cell>
                <flux:table.cell class="text-right">
                    <flux:button wire:click="openContactCard({{ $service->id }})" variant="ghost" icon="eye" size="sm" title="Ver ficha de contacto" />
                    <flux:button href="{{ route('directory.edit', $service->unit_id) }}" wire:navigate variant="ghost" icon="pencil-square" size="sm" />
                    <flux:button wire:click="deleteUnit({{ $service->unit_id }})" wire:confirm="¿Eliminar permanentemente esta unidad y sus servicios?" variant="ghost" size="sm" icon="trash" />
                </flux:table.cell>
            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="6">
                    <x-wfm.empty icon="building-office" message="No se encontraron servicios registrados." />
                </flux:table.cell>
            </flux:table.row>
        @endforelse
    </x-wfm.table>

    <div class="mt-4">{{ $services->links() }}</div>

    <flux:modal name="contact-card-modal" wire:model="showContactCard" variant="flyout" class="!max-w-2xl">
        @if($viewingService)
            <div class="space-y-4">
                <flux:modal.header>
                    <flux:heading size="lg">Ficha de Contacto</flux:heading>
                    <flux:subheading>{{ $viewingService->unit->display_name }}</flux:subheading>
                </flux:modal.header>

                <div class="max-h-[70vh] overflow-y-auto px-4 pb-4">
                    <x-directory::contact-card :service="$viewingService" />
                </div>

                <flux:modal.footer>
                    <flux:button variant="ghost" wire:click="closeContactCard">Cerrar</flux:button>
                    <flux:button href="{{ route('directory.edit', $viewingService->unit_id) }}" wire:navigate icon="pencil-square">Editar Unidad</flux:button>
                </flux:modal.footer>
            </div>
        @endif
    </flux:modal>
</div>