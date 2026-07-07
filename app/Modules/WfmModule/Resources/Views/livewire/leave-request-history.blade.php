<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Historial de Permisos</flux:heading>
            <flux:subheading>Consulta el estado de tus solicitudes de permiso trimestral y compensatorio.</flux:subheading>
        </div>
        <flux:button href="{{ route('schedules.my-schedule') }}" wire:navigate variant="subtle" icon="arrow-left">Volver al Horario</flux:button>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b bg-zinc-50 dark:bg-zinc-900/50">
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Fecha</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Tipo</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Duración</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Estado</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-zinc-800">
                @forelse($leaves as $leave)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-opacity">
                        <td class="p-4 text-sm font-medium">
                            {{ $leave->start_time->format('d M, Y') }}
                        </td>
                        <td class="p-4 text-sm">
                            @php
                                $typeLabel = match($leave->type) {
                                    'quarterly' => 'Trimestral',
                                    'compensatory' => 'Compensatorio',
                                    default => ucfirst($leave->type)
                                };
                            @endphp
                            {{ $typeLabel }}
                        </td>
                        <td class="p-4 text-sm text-zinc-500">
                            {{ round($leave->minutes / 60, 1) }}h 
                            <span class="text-[10px]">({{ $leave->start_time->format('H:i') }} - {{ $leave->end_time->format('H:i') }})</span>
                        </td>
                        <td class="p-4">
                            @php
                                $variant = match($leave->status) {
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'cancelled' => 'neutral',
                                    default => 'warning'
                                };
                                $statusLabel = match($leave->status) {
                                    'pending' => 'Pendiente Jefe',
                                    'approved' => 'Aprobado',
                                    'rejected' => 'Rechazado',
                                    'cancelled' => 'Cancelado',
                                    default => ucfirst($leave->status)
                                };
                            @endphp
                            <flux:badge size="sm" :variant="$variant">{{ $statusLabel }}</flux:badge>
                        </td>
                        <td class="p-4 text-right">
                            <flux:dropdown>
                                <flux:button variant="subtle" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="showDetails({{ $leave->id }})" icon="eye">Ver Aprobación</flux:menu.item>
                                    @if($leave->status === 'pending')
                                        <flux:menu.item wire:click="cancelLeave({{ $leave->id }})" wire:confirm="¿Estás seguro de cancelar este permiso?" variant="danger" icon="trash">Cancelar Solicitud</flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-zinc-500 italic">
                            No se encontraron permisos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($leaves->hasPages())
            <div class="p-4 border-t dark:border-zinc-800">
                {{ $leaves->links() }}
            </div>
        @endif
    </flux:card>

    {{-- Modal de Detalles de Aprobación --}}
    <flux:modal name="leave-details" class="md:w-[500px] space-y-6">
        @if($selectedLeave)
            <div>
                <flux:heading size="lg">Estado de Aprobación</flux:heading>
                <flux:subheading>Solicitud de Permiso {{ ucfirst($selectedLeave->type) }}</flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold text-zinc-500 uppercase">Resumen</span>
                        <flux:badge size="xs" variant="{{ $selectedLeave->status === 'approved' ? 'success' : ($selectedLeave->status === 'rejected' ? 'danger' : 'warning') }}">
                            {{ ucfirst($selectedLeave->status) }}
                        </flux:badge>
                    </div>
                    <p class="text-sm"><strong>Fecha:</strong> {{ $selectedLeave->start_time->format('d/m/Y') }}</p>
                    <p class="text-sm"><strong>Horario:</strong> {{ $selectedLeave->start_time->format('H:i') }} a {{ $selectedLeave->end_time->format('H:i') }}</p>
                </div>

                <div class="space-y-2">
                    <span class="text-xs font-bold text-zinc-500 uppercase">Visto Bueno Jefe Directo</span>
                    @forelse($selectedLeave->approvals as $approval)
                        <div class="flex flex-col p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border gap-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <flux:icon name="{{ $approval->status === 'approved' ? 'check-circle' : ($approval->status === 'rejected' ? 'x-circle' : 'clock') }}" 
                                               size="xs" 
                                               class="{{ $approval->status === 'approved' ? 'text-green-500' : ($approval->status === 'rejected' ? 'text-red-500' : 'text-zinc-400') }}" />
                                    <span class="text-xs font-medium">{{ $approval->approver->first_name }} {{ $approval->approver->last_name }}</span>
                                </div>
                                <span class="text-[10px] text-zinc-400">{{ $approval->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @if($approval->comment)
                                <p class="text-xs italic text-zinc-600 dark:text-zinc-400 bg-white dark:bg-zinc-800 p-2 rounded border border-dashed">
                                    "{{ $approval->comment }}"
                                </p>
                            @endif
                        </div>
                    @empty
                        <div class="p-4 text-center border border-dashed rounded-lg">
                            <flux:icon name="clock" size="sm" class="mx-auto mb-2 text-zinc-300" />
                            <p class="text-xs text-zinc-400 italic">Esperando revisión de tu jefe inmediato.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <flux:modal.close>
                    <flux:button variant="subtle">Cerrar</flux:button>
                </flux:modal.close>
            </div>
        @endif
    </flux:modal>
</div>
