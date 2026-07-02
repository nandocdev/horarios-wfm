<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Bandeja de Aprobaciones</flux:heading>
            <flux:subheading>Gestión de solicitudes de aprobación multinivel (L1/L2/L3).</flux:subheading>
        </div>
    </div>

    <flux:tabs wire:model="tab" class="w-full">
        <flux:tab value="pending">Pendientes</flux:tab>
        <flux:tab value="history">Historial</flux:tab>
    </flux:tabs>

    <flux:card class="space-y-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>Solicitante</flux:column>
                <flux:table.column>Estado</flux:column>
                <flux:table.column>Nivel</flux:column>
                <flux:table.column>Motivo</flux:column>
                <flux:table.column align="end"></flux:column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($requests as $r)
                    <flux:table.row :key="$r->id">
                        <flux:table.cell>
                            <flux:badge size="sm" inset="top bottom">
                                {{ $r->type === 'leave' ? 'Permiso' : ($r->type === 'shift_swap' ? 'Intercambio' : $r->type) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>#{{ $r->requester_id }}</flux:table.cell>
                        <flux:table.cell>
                            @php
                                $state = $r->state ?? 'pending';
                                $variant = match(true) {
                                    in_array($state, ['approved', 'l1_approved', 'l2_approved', 'l3_approved']) => 'success',
                                    $state === 'rejected' => 'danger',
                                    $state === 'cancelled' => 'ghost',
                                    default => 'warning',
                                };
                            @endphp
                            <flux:badge :variant="$variant" size="sm">{{ $state }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>L{{ $r->required_levels ?? 1 }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:text size="sm" class="text-zinc-500">{{ Str::limit($r->reason ?? '—', 40) }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            @if(in_array($r->state, ['pending', 'l1_approved', 'l2_approved']))
                                <flux:button wire:click="$set('processingId', {{ $r->id }})" size="xs" variant="primary">
                                    Revisar
                                </flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>

                    @if($processingId === $r->id)
                        <flux:table.row>
                            <flux:table.cell colspan="6">
                                <div class="flex items-start gap-3 p-2">
                                    <flux:textarea wire:model="comment" placeholder="Comentario (requerido para rechazar)" class="flex-1" rows="2" />
                                    <div class="flex gap-2 pt-1">
                                        <flux:button wire:click="approve({{ $r->id }})" variant="primary" size="sm">Aprobar</flux:button>
                                        <flux:button wire:click="reject({{ $r->id }})" variant="danger" size="sm">Rechazar</flux:button>
                                        <flux:button wire:click="$set('processingId', 0)" variant="ghost" size="sm">Cancelar</flux:button>
                                    </div>
                                </div>
                                @error('comment') <flux:error>{{ $message }}</flux:error> @enderror
                            </flux:table.cell>
                        </flux:table.row>
                    @endif
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-10">
                            <flux:text class="text-zinc-400 italic">Sin solicitudes.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div>{{ $requests->links() }}</div>
    </flux:card>
</div>
