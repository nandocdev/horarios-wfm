<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Planificación Semanal</flux:heading>
            <flux:subheading>Gestión de horarios semanales por equipo.</flux:subheading>
        </div>
        <flux:button wire:click="createNextWeek" variant="primary" icon="plus">
            Nueva Semana
        </flux:button>
    </div>

    <flux:card>
        <flux:table :paginate="$weeks">
            <flux:table.columns>
                <flux:table.column>Semana</flux:table.column>
                <flux:table.column>Asignaciones</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Publicado</flux:table.column>
                <flux:table.column align="end"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($weeks as $week)
                    <flux:table.row :key="$week->id">
                        <flux:table.cell variant="strong">
                            {{ $week->week_start_date->format('d/m/Y') }} - {{ $week->week_end_date->format('d/m/Y') }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $week->assignments_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :variant="$week->status === 'published' ? 'success' : 'warning'" size="sm">
                                {{ $week->status === 'published' ? 'Publicado' : 'Borrador' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $week->published_at ? $week->published_at->format('d/m/Y H:i') : '—' }}
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            @if($week->status === 'draft')
                                <flux:button wire:click="publish({{ $week->id }})" size="sm" variant="primary">
                                    Publicar
                                </flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-10">
                            <flux:text class="text-zinc-400 italic">No hay semanas creadas.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
