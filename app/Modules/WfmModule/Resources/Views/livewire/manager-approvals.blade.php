<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <flux:heading size="xl">Aprobación de Permisos (Jefe Inmediato)</flux:heading>
        <flux:subheading>Gestiona las solicitudes de permiso de tu equipo de trabajo directo.</flux:subheading>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b bg-zinc-50 dark:bg-zinc-900/50">
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Empleado</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Tipo / Fecha</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Duración</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Motivo</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-zinc-800">
                @forelse($requests as $request)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                        <td class="p-4 text-sm">
                            <div class="flex flex-col">
                                <span class="font-bold">{{ $request->employee->first_name }} {{ $request->employee->last_name }}</span>
                                <span class="text-xs text-zinc-500">{{ $request->employee->position->name ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td class="p-4 text-sm">
                            <div class="flex flex-col">
                                <span class="font-medium text-primary-600">{{ ucfirst($request->type) }}</span>
                                <span class="text-xs text-zinc-500">{{ $request->start_time->format('d M, Y') }}</span>
                            </div>
                        </td>
                        <td class="p-4 text-sm text-zinc-500">
                            {{ round($request->minutes / 60, 1) }}h 
                            <p class="text-[10px]">{{ $request->start_time->format('H:i') }} - {{ $request->end_time->format('H:i') }}</p>
                        </td>
                        <td class="p-4 text-sm text-zinc-500 max-w-xs truncate italic">
                            "{{ $request->reason }}"
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex justify-end gap-2">
                                <flux:button wire:click="approveLeave({{ $request->id }})" 
                                             wire:confirm="¿Confirmas el visto bueno para este permiso?" 
                                             variant="primary" 
                                             size="sm" 
                                             icon="check">
                                    Dar VB
                                </flux:button>
                                <flux:button wire:click="rejectLeave({{ $request->id }})" 
                                             wire:confirm="¿Deseas rechazar esta solicitud?" 
                                             variant="subtle" 
                                             size="sm" 
                                             icon="x-mark">
                                    Rechazar
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-zinc-500 italic">
                            No tienes solicitudes pendientes de aprobación.
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
