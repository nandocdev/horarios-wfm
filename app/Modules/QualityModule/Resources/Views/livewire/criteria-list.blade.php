<div class="space-y-6">
    <x-wfm.page-header title="Criterios de Evaluación" description="Gestión de criterios con versionado automático.">
        <x-slot:actions>
            <flux:button href="{{ route('quality.criteria.create') }}" icon="plus" variant="primary">Nuevo Criterio</flux:button>
        </x-slot:actions>
    </x-wfm.page-header>

    <x-wfm.table :headers="['Código', 'Texto del Criterio', 'Versión', 'Pts', 'Descripción', 'Acciones']" compact>
        @forelse($criterias as $criteria)
            <flux:table.row :key="$criteria->id">
                <flux:table.cell>
                    <flux:badge size="sm" color="indigo">{{ $criteria->code }}</flux:badge>
                </flux:table.cell>
                <flux:table.cell class="text-sm max-w-xs truncate">{{ $criteria->currentVersion?->criterio_text ?? '—' }}</flux:table.cell>
                <flux:table.cell class="text-sm">v{{ $criteria->currentVersion?->version ?? '—' }}</flux:table.cell>
                <flux:table.cell class="text-sm font-mono">{{ $criteria->currentVersion?->puntaje ?? '—' }}</flux:table.cell>
                <flux:table.cell class="text-sm max-w-xs truncate text-wfm-surface-muted">{{ $criteria->currentVersion?->descripcion ?? '—' }}</flux:table.cell>
                <flux:table.cell>
                    <flux:button href="{{ route('quality.criteria.edit', $criteria) }}" variant="ghost" size="sm" icon="pencil" />
                </flux:table.cell>
            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="6">
                    <x-wfm.empty icon="clipboard-list" message="No hay criterios registrados." />
                </flux:table.cell>
            </flux:table.row>
        @endforelse
    </x-wfm.table>
</div>
