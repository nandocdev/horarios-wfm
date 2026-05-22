<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Historial de Solicitudes</flux:heading>
            <flux:subheading>Gestiona y consulta el estado de tus intercambios de turno.</flux:subheading>
        </div>
        <flux:button href="{{ route('schedules.my-schedule') }}" wire:navigate variant="subtle" icon="arrow-left">Volver
            al Horario</flux:button>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b bg-zinc-50 dark:bg-zinc-900/50">
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Fecha Solicitada</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Tipo</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Compañero</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Estado</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-zinc-800">
                @forelse($requests as $request)
                    @php
                        $isRequester = $request->requester_id === $currentEmployeeId;
                        $peer = $isRequester ? $request->recipient : $request->requester;
                    @endphp
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                        <td class="p-4 text-sm font-medium">
                            {{ $request->start_date->format('d M, Y') }}
                            @if($request->end_date && $request->end_date->gt($request->start_date))
                                al {{ $request->end_date->format('d M, Y') }}
                            @endif
                        </td>
                        <td class="p-4 text-sm text-zinc-500">
                            @if($isRequester)
                                <flux:badge size="sm" variant="subtle">Enviada</flux:badge>
                            @else
                                <flux:badge size="sm" variant="subtle">Recibida</flux:badge>
                            @endif
                        </td>
                        <td class="p-4 text-sm">
                            <div class="flex flex-col">
                                <span class="font-bold">{{ $peer->first_name }} {{ $peer->last_name }}</span>
                                <span class="text-xs text-zinc-500">{{ $peer->team->name ?? 'Sin Equipo' }}</span>
                            </div>
                        </td>
                        <td class="p-4">
                            @php
                                $variant = match ($request->status) {
                                    'pending' => 'gost',
                                    'approved' => 'default',
                                    'accepted' => 'default',
                                    'rejected' => 'danger',
                                    'cancelled' => 'filled',
                                    default => 'gost'
                                };

                                $colors = [
                                    'pending' => 'warning',
                                    'accepted' => 'green',
                                    'approved' => 'blue',
                                    'rejected' => 'red',
                                    'cancelled' => 'yellow',
                                ];

                                $statusLabel = match ($request->status) {
                                    'pending' => 'Pendiente',
                                    'accepted' => 'Aceptado por Par',
                                    'approved' => 'Aprobado WFM',
                                    'rejected' => 'Rechazado',
                                    'cancelled' => 'Cancelado',
                                    default => ucfirst($request->status)
                                };
                            @endphp
                            <flux:badge size="sm" :variant="$variant" color="{{ $colors[$request->status] }}">{{ $statusLabel }}</flux:badge>
                        </td>
                        <td class="p-4 text-right">
                            <flux:button.group>
                                <flux:button wire:click="showDetails({{ $request->id }})" variant="filled" size="sm" icon="eye" />

                                @if($request->status === 'pending' && $isRequester)
                                    <flux:button wire:click="cancelSwap({{ $request->id }})"
                                        wire:confirm="¿Estás seguro de que deseas cancelar esta solicitud?"
                                        variant="filled" size="sm" icon="trash" />
                                @endif

                                @if($request->status === 'pending' && !$isRequester)
                                    <flux:button wire:click="acceptSwap({{ $request->id }})"
                                        wire:confirm="¿Confirmas que deseas intercambiar tu turno con {{ $peer->first_name }}?"
                                        variant="filled" size="sm" icon="check" />
                                    <flux:button wire:click="rejectSwap({{ $request->id }})"
                                        wire:confirm="¿Estás seguro de que deseas rechazar este cambio?"
                                        variant="filled" size="sm" icon="x-mark" />
                                @endif
                            </flux:button.group>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-zinc-500 italic">
                            No se encontraron solicitudes registradas.
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

    {{-- Modal de Detalles --}}
    <flux:modal name="swap-details" class="md:w-[600px] space-y-6">
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
                    <span class="text-xs text-zinc-500 uppercase font-bold">Solicitante</span>
                    <p class="text-sm font-medium">{{ $selectedRequest->requester->first_name }}
                        {{ $selectedRequest->requester->last_name }}</p>
                    <p class="text-xs text-zinc-400">{{ $selectedRequest->requester->team->name ?? 'N/A' }}</p>
                </div>
                <div class="space-y-1">
                    <span class="text-xs text-zinc-500 uppercase font-bold">Destinatario</span>
                    <p class="text-sm font-medium">{{ $selectedRequest->recipient->first_name }}
                        {{ $selectedRequest->recipient->last_name }}</p>
                    <p class="text-xs text-zinc-400">{{ $selectedRequest->recipient->team->name ?? 'N/A' }}</p>
                </div>
            </div>

            <div class="space-y-2">
                <span class="text-xs text-zinc-500 uppercase font-bold">Comparativa de Horarios</span>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border">
                        <p class="text-[10px] text-zinc-500 font-bold mb-2 uppercase">Turno de
                            {{ $selectedRequest->requester->first_name }}</p>
                        @if($requesterShift)
                            <p class="text-xs font-bold">
                                {{ \Illuminate\Support\Carbon::parse($requesterShift->start_time)->format('H:i') }} -
                                {{ \Illuminate\Support\Carbon::parse($requesterShift->end_time)->format('H:i') }}</p>
                            <p class="text-[10px] text-zinc-400">{{ $requesterShift->schedule->name }}</p>
                        @else
                            <p class="text-xs text-zinc-400">Sin turno</p>
                        @endif
                    </div>
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border">
                        <p class="text-[10px] text-zinc-500 font-bold mb-2 uppercase">Turno de
                            {{ $selectedRequest->recipient->first_name }}</p>
                        @if($recipientShift)
                            <p class="text-xs font-bold">
                                {{ \Illuminate\Support\Carbon::parse($recipientShift->start_time)->format('H:i') }} -
                                {{ \Illuminate\Support\Carbon::parse($recipientShift->end_time)->format('H:i') }}</p>
                            <p class="text-[10px] text-zinc-400">{{ $recipientShift->schedule->name }}</p>
                        @else
                            <p class="text-xs text-zinc-400">Sin turno</p>
                        @endif
                    </div>
                </div>
            </div>

            @if($selectedRequest->reason)
                <div class="space-y-1">
                    <span class="text-xs text-zinc-500 uppercase font-bold">Motivo del Cambio</span>
                    <p class="text-sm p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg italic">"{{ $selectedRequest->reason }}"</p>
                </div>
            @endif

            <div class="space-y-2">
                <span class="text-xs text-zinc-500 uppercase font-bold">Historial de Aprobación</span>
                <div class="space-y-2">
                    @forelse($selectedRequest->approvals as $approval)
                        <div class="flex items-center justify-between p-2 text-xs border-b last:border-0">
                            <div class="flex items-center gap-2">
                                <flux:icon
                                    name="{{ $approval->status === 'approved' ? 'check-circle' : ($approval->status === 'rejected' ? 'x-circle' : 'clock') }}"
                                    size="xs"
                                    class="{{ $approval->status === 'approved' ? 'text-green-500' : ($approval->status === 'rejected' ? 'text-red-500' : 'text-zinc-400') }}" />
                                <span>{{ $approval->approver->first_name }} {{ $approval->approver->last_name }}</span>
                            </div>
                            <flux:badge size="xs"
                                variant="{{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'danger' : 'subtle') }}">
                                {{ ucfirst($approval->status) }}
                            </flux:badge>
                        </div>
                    @empty
                        <p class="text-xs text-zinc-400 italic">Pendiente de acciones iniciales.</p>
                    @endforelse
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <flux:modal.close>
                    <flux:button variant="subtle">Cerrar</flux:button>
                </flux:modal.close>
            </div>
        @endif
    </flux:modal>
</div>