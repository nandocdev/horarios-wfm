<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <flux:heading size="xl">Aprobación de Cambios de Turno (WFM)</flux:heading>
        <flux:subheading>Revisa y procesa las solicitudes de intercambio aceptadas por los operadores.</flux:subheading>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b bg-zinc-50 dark:bg-zinc-900/50">
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Fecha</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Solicitante</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Destinatario</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500 text-center">Estado</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Motivo</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-zinc-800">
                @forelse($requests as $request)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                        <td class="p-4 text-sm font-medium">
                            {{ $request->start_date->format('d/m/Y') }}
                            @if($request->end_date && $request->end_date->ne($request->start_date))
                                - {{ $request->end_date->format('d/m/Y') }}
                            @endif
                        </td>
                        <td class="p-4 text-sm">
                            <div class="flex flex-col">
                                <span class="font-bold">{{ $request->requester->first_name }} {{ $request->requester->last_name }}</span>
                                <span class="text-xs text-zinc-500">{{ $request->requester->team->name ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td class="p-4 text-sm">
                            <div class="flex flex-col">
                                <span class="font-bold">{{ $request->recipient->first_name }} {{ $request->recipient->last_name }}</span>
                                <span class="text-xs text-zinc-500">{{ $request->recipient->team->name ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td class="p-4 text-center">
                            @if($request->status === 'accepted')
                                <flux:badge color="green" size="sm" inset="top bottom" class="font-bold">LISTO (ACEPTADO)</flux:badge>
                            @elseif($request->status === 'pending')
                                <flux:badge color="amber" size="sm" inset="top bottom" class="font-bold">PENDIENTE RECEPTOR</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm" inset="top bottom">{{ strtoupper($request->status) }}</flux:badge>
                            @endif
                        </td>
                        <td class="p-4 text-sm text-zinc-500 max-w-xs truncate italic">
                            {{ $request->reason ?? 'Sin motivo especificado' }}
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex justify-end gap-2">
                                @if($request->status === 'accepted')
                                    <flux:button wire:click="approveSwap({{ $request->id }})" 
                                                wire:confirm="¿Estás seguro de aprobar este cambio? Se aplicará el intercambio de turnos en el horario inmediatamente." 
                                                variant="primary" 
                                                size="sm" 
                                                icon="check">
                                        Aprobar
                                    </flux:button>
                                    <flux:button wire:click="rejectSwap({{ $request->id }})" 
                                                wire:confirm="¿Deseas rechazar esta solicitud?" 
                                                variant="subtle" 
                                                size="sm" 
                                                icon="x-mark">
                                        Rechazar
                                    </flux:button>
                                @else
                                    <flux:text size="xs" class="italic text-zinc-400">Sólo Lectura</flux:text>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-zinc-500 italic">
                            No hay solicitudes pendientes de aprobación por WFM.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($requests->hasPages())
            <div class="p-4 border-t dark:border-zinc-800">
                {{ $requests->links() }}
            </div>
        @endif
    </flux:card>
</div>
