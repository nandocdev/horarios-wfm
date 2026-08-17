<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-md shadow-sm border border-slate-200">
        <div data-tour="workflows-header" class="p-4 border-b border-slate-200 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-slate-900">Aprobaciones Pendientes</h1>
            <x-wfm.tour-button :tour="'workflows.pending'" />
        </div>

        <div class="p-4">
            <flux:table data-tour="workflows-list" :paginate="$requests">
                <flux:table.columns>
                    <flux:table.column>Tipo</flux:table.column>
                    <flux:table.column>Solicitante</flux:table.column>
                    <flux:table.column>Motivo</flux:table.column>
                    <flux:table.column>Fecha</flux:table.column>
                    <flux:table.column>Acciones</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($requests as $request)
                        <flux:table.row :key="$request->id">
                            <flux:table.cell>
                                <flux:badge>{{ $request->type }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $request->requester?->full_name }}</flux:table.cell>
                            <flux:table.cell>{{ Str::limit($request->reason, 50) }}</flux:table.cell>
                            <flux:table.cell>{{ $request->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button.group data-tour="workflows-actions">
                                    <flux:button wire:click="approve({{ $request->id }})" variant="primary" size="sm">
                                        Aprobar
                                    </flux:button>
                                    <flux:button wire:click="selectForReject({{ $request->id }})" variant="danger" size="sm">
                                        Rechazar
                                    </flux:button>
                                </flux:button.group>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-8 text-slate-500">
                                No hay aprobaciones pendientes.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if($selectedRequestId)
                <flux:modal name="reject-modal" variant="flyout">
                    <div class="space-y-4 p-4">
                        <flux:heading>Motivo del Rechazo</flux:heading>
                        <flux:textarea wire:model="rejectReason" placeholder="Indica el motivo del rechazo..." rows="4" />
                        <flux:error name="rejectReason" />
                        <div class="flex justify-end gap-4">
                            <flux:button wire:click="$set('selectedRequestId', null)" variant="ghost">Cancelar</flux:button>
                            <flux:button wire:click="reject" variant="danger">Rechazar Solicitud</flux:button>
                        </div>
                    </div>
                </flux:modal>
            @endif
        </div>
    </div>
</div>
