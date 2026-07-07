<div class="max-w-3xl mx-auto space-y-8">
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
                <tr class="border-b bg-slate-50 dark:bg-slate-900/50 sticky top-0 z-10">
                    <th class="py-2 px-4 text-xs font-bold uppercase tracking-wider text-slate-500">Fecha</th>
                    <th class="py-2 px-4 text-xs font-bold uppercase tracking-wider text-slate-500">Tipo</th>
                    <th class="py-2 px-4 text-xs font-bold uppercase tracking-wider text-slate-500">Duración</th>
                    <th class="py-2 px-4 text-xs font-bold uppercase tracking-wider text-slate-500">Estado</th>
                    <th class="py-2 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-slate-800">
                @forelse($leaves as $leave)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-opacity">
                        <td class="py-2 px-4 text-sm font-medium">
                            {{ $leave->start_time->format('d M, Y') }}
                        </td>
                        <td class="py-2 px-4 text-sm">
                            @php
                                $typeLabel = match($leave->type) {
                                    'quarterly' => 'Trimestral',
                                    'compensatory' => 'Compensatorio',
                                    default => ucfirst($leave->type)
                                };
                            @endphp
                            {{ $typeLabel }}
                        </td>
                        <td class="py-2 px-4 text-sm text-slate-500">
                            {{ round($leave->minutes / 60, 1) }}h 
                            <span class="text-[10px]">({{ $leave->start_time->format('H:i') }} - {{ $leave->end_time->format('H:i') }})</span>
                        </td>
                        <td class="py-2 px-4">
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
                        <td class="py-2 px-4 text-right">
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
                        <td colspan="5" class="p-8 text-center text-slate-500 italic">
                            No se encontraron permisos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($leaves->hasPages())
            <div class="p-4 border-t dark:border-slate-800">
                {{ $leaves->links() }}
            </div>
        @endif
    </flux:card>

    {{-- Modal de Detalles de Aprobación --}}
    <flux:modal name="leave-details" class="w-full max-w-lg space-y-4">
        @if($selectedLeave)
            <div>
                <flux:heading size="lg">Estado de Aprobación</flux:heading>
                <flux:subheading>Solicitud de Permiso {{ ucfirst($selectedLeave->type) }}</flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-md border">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold text-slate-500 uppercase">Resumen</span>
                        <flux:badge size="xs" variant="{{ $selectedLeave->status === 'approved' ? 'success' : ($selectedLeave->status === 'rejected' ? 'danger' : 'warning') }}">
                            {{ ucfirst($selectedLeave->status) }}
                        </flux:badge>
                    </div>
                    <p class="text-sm"><strong>Fecha:</strong> {{ $selectedLeave->start_time->format('d/m/Y') }}</p>
                    <p class="text-sm"><strong>Horario:</strong> {{ $selectedLeave->start_time->format('H:i') }} a {{ $selectedLeave->end_time->format('H:i') }}</p>
                </div>

                <div class="space-y-2">
                    <span class="text-xs font-bold text-slate-500 uppercase">Visto Bueno Jefe Directo</span>
                    @forelse($selectedLeave->approvals as $approval)
                        <div class="flex flex-col p-3 bg-slate-50 dark:bg-slate-900 rounded-md border gap-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <flux:icon name="{{ $approval->status === 'approved' ? 'check-circle' : ($approval->status === 'rejected' ? 'x-circle' : 'clock') }}" 
                                               size="xs" 
                                               class="{{ $approval->status === 'approved' ? 'text-green-500' : ($approval->status === 'rejected' ? 'text-red-500' : 'text-slate-400') }}" />
                                    <span class="text-xs font-medium">{{ $approval->approver->first_name }} {{ $approval->approver->last_name }}</span>
                                </div>
                                <span class="text-[10px] text-slate-400">{{ $approval->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @if($approval->comment)
                                <p class="text-xs italic text-zinc-600 dark:text-slate-400 bg-white dark:bg-zinc-800 p-2 rounded border border-dashed">
                                    "{{ $approval->comment }}"
                                </p>
                            @endif
                        </div>
                    @empty
                        <div class="p-4 text-center border border-dashed rounded-md">
                            <flux:icon name="clock" size="sm" class="mx-auto mb-2 text-slate-300" />
                            <p class="text-xs text-slate-400 italic">Esperando revisión de tu jefe inmediato.</p>
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
