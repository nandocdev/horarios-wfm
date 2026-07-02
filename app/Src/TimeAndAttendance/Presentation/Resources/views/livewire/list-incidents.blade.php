<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Incidencias de Asistencia</flux:heading>
            <flux:subheading>Gestión de tardanzas, ausencias y salidas tempranas.</flux:subheading>
        </div>
    </div>

    <flux:card class="space-y-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por empleado..."
            class="max-w-md" icon="magnifying-glass" clearable />

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Empleado</flux:table.column>
                <flux:table.column>Fecha</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Comentario</flux:table.column>
                <flux:table.column align="end"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($incidents as $incident)
                    <flux:table.row :key="$incident->id">
                        <flux:table.cell>{{ $incident->employee?->full_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $incident->incident_date?->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" inset="top bottom">
                                {{ $incident->incident_type_id === 1 ? 'Tardanza' : ($incident->incident_type_id === 2 ? 'Ausencia' : 'Incidencia') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @switch($incident->status ?? 'open')
                                @case('open')
                                    <flux:badge variant="warning" size="sm">Abierta</flux:badge>
                                    @break
                                @case('justified')
                                    <flux:badge variant="success" size="sm">Justificada</flux:badge>
                                    @break
                                @case('unjustified')
                                    <flux:badge variant="danger" size="sm">Injustificada</flux:badge>
                                    @break
                                @case('resolved')
                                    <flux:badge variant="ghost" size="sm">Resuelta</flux:badge>
                                    @break
                                @default
                                    <flux:badge size="sm">{{ $incident->status }}</flux:badge>
                            @endswitch
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text size="sm" class="text-zinc-500">
                                {{ $incident->user_comment ?? $incident->admin_comment ?? '—' }}
                            </flux:text>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            @if(($incident->status ?? 'open') === 'open')
                                <flux:button wire:click="justify({{ $incident->id }})" size="xs" variant="primary">
                                    Justificar
                                </flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>

                    {{-- Justification form --}}
                    @if($justifyingId === $incident->id)
                        <flux:table.row>
                            <flux:table.cell colspan="6">
                                <form wire:submit="saveJustify" class="flex items-start gap-3 p-2">
                                    <flux:textarea wire:model="justifyComment" placeholder="Motivo de justificación (mín. 10 caracteres)"
                                        class="flex-1" rows="2" required />
                                    <div class="flex gap-2 pt-1">
                                        <flux:button type="submit" variant="primary" size="sm">Guardar</flux:button>
                                        <flux:button wire:click="cancelJustify" variant="ghost" size="sm">Cancelar</flux:button>
                                    </div>
                                </form>
                            </flux:table.cell>
                        </flux:table.row>
                    @endif
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-10">
                            <flux:text class="text-zinc-400 italic">Sin incidencias registradas.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div>{{ $incidents->links() }}</div>
    </flux:card>
</div>
