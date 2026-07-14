<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Criterios de Evaluación</flux:heading>
            <flux:subheading>Gestión de criterios con versionado automático</flux:subheading>
        </div>
        {{-- <flux:button href="{{ route('quality.criteria.create') }}" icon="plus">Nuevo Criterio</flux:button> --}}
    </div>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Código</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Texto del Criterio</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Versión</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Puntaje</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($criterias as $criteria)
                    <flux:table.row :key="$criteria->id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <flux:table.cell class="py-2">
                            <flux:badge size="sm" color="indigo" inset="top">{{ $criteria->code }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="py-2 text-sm">{{ $criteria->currentVersion?->criterio_text ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="py-2 text-sm">{{ $criteria->currentVersion?->version ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="py-2 text-sm font-mono">{{ $criteria->currentVersion?->puntaje ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="py-2">
                            {{-- <flux:button href="#" variant="ghost" size="sm" icon="pencil">Editar</flux:button> --}}
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-12">
                            <flux:icon name="clipboard-list" class="w-12 h-12 text-slate-200 mx-auto mb-3" />
                            <flux:text class="text-slate-400">No hay criterios registrados</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
