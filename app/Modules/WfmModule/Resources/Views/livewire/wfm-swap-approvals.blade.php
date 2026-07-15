<div class=" mx-auto space-y-8 flex-1 flex flex-col">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <flux:heading size="xl">Aprobación de Cambios de Turno (WFM)</flux:heading>
            <flux:subheading>Revisa y procesa las solicitudes de intercambio de turnos del Call Center.
            </flux:subheading>
        </div>

        {{-- Navegación de Solicitudes (Tabs) --}}
        <div class="flex gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-md self-start md:self-auto">
            <flux:button wire:click="$set('currentTab', 'pending')"
                variant="{{ $currentTab === 'pending' ? 'filled' : 'ghost' }}" size="sm">
                Pendientes
            </flux:button>
            <flux:button wire:click="$set('currentTab', 'processed')"
                variant="{{ $currentTab === 'processed' ? 'filled' : 'ghost' }}" size="sm">
                Tramitadas
            </flux:button>
        </div>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b bg-slate-50 dark:bg-slate-900/50 sticky top-0 z-10">
                    <th class="py-2 px-4 text-xs font-bold uppercase tracking-wider text-slate-500">Fecha</th>
                    <th class="py-2 px-4 text-xs font-bold uppercase tracking-wider text-slate-500">Solicitante</th>
                    <th class="py-2 px-4 text-xs font-bold uppercase tracking-wider text-slate-500">Destinatario</th>
                    <th class="py-2 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-center">Estado
                    </th>
                    <th class="py-2 px-4 text-xs font-bold uppercase tracking-wider text-slate-500">Motivo</th>
                    <th class="py-2 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-slate-800">
                @forelse($requests as $request)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-opacity">
                        <td class="py-2 px-4 text-sm font-medium">
                            {{ $request->start_date->format('d/m/Y') }}
                            @if($request->end_date && $request->end_date->ne($request->start_date))
                                - {{ $request->end_date->format('d/m/Y') }}
                            @endif
                        </td>
                        <td class="py-2 px-4 text-sm">
                            <div class="flex flex-col">
                                <span class="font-bold">{{ $request->requester->first_name }}
                                    {{ $request->requester->last_name }}</span>
                                <span class="text-xs text-slate-500">{{ $request->requester->team->name ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td class="py-2 px-4 text-sm">
                            <div class="flex flex-col">
                                <span class="font-bold">{{ $request->recipient->first_name }}
                                    {{ $request->recipient->last_name }}</span>
                                <span class="text-xs text-slate-500">{{ $request->recipient->team->name ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td class="py-2 px-4 text-center">
                            @if($request->status === 'approved')
                                <flux:badge color="green" size="sm" inset="top bottom" class="font-bold">APROBADO</flux:badge>
                            @elseif($request->status === 'accepted')
                                <flux:badge color="blue" size="sm" inset="top bottom" class="font-bold">LISTO (ACEPTADO)
                                </flux:badge>
                            @elseif($request->status === 'pending')
                                <flux:badge color="amber" size="sm" inset="top bottom" class="font-bold">PENDIENTE RECEPTOR
                                </flux:badge>
                            @elseif($request->status === 'rejected')
                                <flux:badge color="red" size="sm" inset="top bottom" class="font-bold">RECHAZADO</flux:badge>
                            @else
                                <flux:badge color="slate" size="sm" inset="top bottom">{{ strtoupper($request->status) }}
                                </flux:badge>
                            @endif
                        </td>
                        <td class="py-2 px-4 text-sm text-slate-500 max-w-xs truncate italic">
                            {{ $request->reason ?? 'Sin motivo especificado' }}
                        </td>
                        <td class="py-2 px-4 text-right">
                            <div class="flex justify-end gap-2">
                                <flux:button wire:click="showDetails({{ $request->id }})" variant="ghost" size="sm"
                                    icon="eye">
                                    Detalles
                                </flux:button>
                                @can('wfm.swaps.manage')
                                    @if($currentTab === 'pending' && $request->status === 'accepted')
                                        <flux:button wire:click="approveSwap({{ $request->id }})"
                                            wire:confirm="¿Estás seguro de aprobar este cambio? Se aplicará el intercambio de turnos en el horario inmediatamente."
                                            variant="primary" size="sm" icon="check">
                                            Aprobar
                                        </flux:button>
                                        <flux:button wire:click="rejectSwap({{ $request->id }})"
                                            wire:confirm="¿Deseas rechazar esta solicitud?" variant="subtle" size="sm"
                                            icon="x-mark">
                                            Rechazar
                                        </flux:button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-500 italic">
                            No hay solicitudes {{ $currentTab === 'pending' ? 'pendientes' : 'tramitadas' }} registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($requests->hasPages())
            <div class="p-4 border-t dark:border-slate-800">
                {{ $requests->links() }}
            </div>
        @endif
    </flux:card>

    {{-- Modal de Detalles --}}
    <flux:modal name="swap-details" class="w-full max-w-xl space-y-4">
        @if($selectedRequest)
            <div>
                <flux:heading size="lg">Detalle de Solicitud #{{ $selectedRequest->id }}</flux:heading>
                <flux:subheading>Intercambio solicitado para el {{ $selectedRequest->start_date->format('d M, Y') }}
                    @if($selectedRequest->end_date && $selectedRequest->end_date->gt($selectedRequest->start_date))
                        al {{ $selectedRequest->end_date->format('d M, Y') }}
                    @endif
                </flux:subheading>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <span class="text-xs text-slate-500 uppercase font-bold">Solicitante</span>
                    <p class="text-sm font-medium">{{ $selectedRequest->requester->first_name }}
                        {{ $selectedRequest->requester->last_name }}</p>
                    <p class="text-xs text-slate-400">{{ $selectedRequest->requester->team->name ?? 'N/A' }}</p>
                </div>
                <div class="space-y-1">
                    <span class="text-xs text-slate-500 uppercase font-bold">Destinatario</span>
                    <p class="text-sm font-medium">{{ $selectedRequest->recipient->first_name }}
                        {{ $selectedRequest->recipient->last_name }}</p>
                    <p class="text-xs text-slate-400">{{ $selectedRequest->recipient->team->name ?? 'N/A' }}</p>
                </div>
            </div>

            <div class="space-y-2">
                <span class="text-xs text-slate-500 uppercase font-bold">Comparativa de Horarios Originales</span>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-md border">
                        <p class="text-[10px] text-slate-500 font-bold mb-2 uppercase">Turno de
                            {{ $selectedRequest->requester->first_name }}</p>
                        @if($requesterShift)
                            <p class="text-xs font-bold">
                                {{ \Illuminate\Support\Carbon::parse($requesterShift->start_time)->format('H:i') }} -
                                {{ \Illuminate\Support\Carbon::parse($requesterShift->end_time)->format('H:i') }}
                            </p>
                            <p class="text-[10px] text-slate-400">{{ $requesterShift->schedule->name }}</p>
                        @else
                            <p class="text-xs text-slate-400">Sin turno</p>
                        @endif
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-md border">
                        <p class="text-[10px] text-slate-500 font-bold mb-2 uppercase">Turno de
                            {{ $selectedRequest->recipient->first_name }}</p>
                        @if($recipientShift)
                            <p class="text-xs font-bold">
                                {{ \Illuminate\Support\Carbon::parse($recipientShift->start_time)->format('H:i') }} -
                                {{ \Illuminate\Support\Carbon::parse($recipientShift->end_time)->format('H:i') }}
                            </p>
                            <p class="text-[10px] text-slate-400">{{ $recipientShift->schedule->name }}</p>
                        @else
                            <p class="text-xs text-slate-400">Sin turno</p>
                        @endif
                    </div>
                </div>
            </div>

            @if($selectedRequest->reason)
                <div class="space-y-1">
                    <span class="text-xs text-slate-500 uppercase font-bold">Motivo del Cambio</span>
                    <p class="text-sm p-3 bg-slate-50 dark:bg-slate-900 rounded-md italic">"{{ $selectedRequest->reason }}"</p>
                </div>
            @endif

            @if($selectedRequest->status === 'rejected' && $selectedRequest->rejection_reason)
                <div class="space-y-1">
                    <span class="text-xs text-slate-500 uppercase font-bold">Motivo del Rechazo</span>
                    <p
                        class="text-sm p-3 bg-red-50/50 dark:bg-red-950/20 text-red-700 dark:text-red-300 rounded-md border border-red-100 dark:border-red-900/50">
                        {{ $selectedRequest->rejection_reason }}
                    </p>
                </div>
            @endif

            <div class="space-y-2">
                <span class="text-xs text-slate-500 uppercase font-bold">Historial de Aprobaciones / Estados</span>
                <div class="space-y-2">
                    @forelse($selectedRequest->approvals as $approval)
                        <div class="flex items-center justify-between p-2 text-xs border-b last:border-0">
                            <div class="flex items-center gap-2">
                                <flux:icon
                                    name="{{ $approval->status === 'approved' ? 'check-circle' : ($approval->status === 'rejected' ? 'x-circle' : 'clock') }}"
                                    size="xs"
                                    class="{{ $approval->status === 'approved' ? 'text-green-500' : ($approval->status === 'rejected' ? 'text-red-500' : 'text-slate-400') }}" />
                                <span>{{ $approval->approver->first_name }} {{ $approval->approver->last_name }} (WFM)</span>
                            </div>
                            <flux:badge size="xs"
                                variant="{{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'danger' : 'subtle') }}">
                                {{ ucfirst($approval->status) }}
                            </flux:badge>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic">Pendiente de aprobación por WFM.</p>
                    @endforelse
                </div>
            </div>

            <div class="flex justify-between pt-4 border-t dark:border-slate-800">
                <div class="flex gap-2">
                    @can('wfm.swaps.manage')
                        @if($selectedRequest->status === 'accepted')
                            <flux:button wire:click="approveSwap({{ $selectedRequest->id }})"
                                wire:confirm="¿Estás seguro de aprobar este cambio?" variant="primary" size="sm">
                                Aprobar
                            </flux:button>
                            <flux:button wire:click="rejectSwap({{ $selectedRequest->id }})"
                                wire:confirm="¿Deseas rechazar esta solicitud?" variant="subtle" size="sm">
                                Rechazar
                            </flux:button>
                        @endif
                    @endcan
                </div>
                <flux:modal.close>
                    <flux:button variant="ghost">Cerrar</flux:button>
                </flux:modal.close>
            </div>
        @endif
    </flux:modal>
</div>