<flux:card>
    <flux:heading size="lg" class="mb-4">Últimas Incidencias de Asistencia</flux:heading>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="sticky top-0 z-10 text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="px-4 py-2">Agente</th>
                    <th class="px-4 py-2">Tipo</th>
                    <th class="px-4 py-2">Fecha / Hora</th>
                    <th class="px-4 py-2 text-center">Estado</th>
                    <th class="px-4 py-2 text-right">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($recentIncidents as $incident)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors duration-150">
                        <td class="px-4 py-2 font-semibold text-slate-900 dark:text-slate-100">
                            {{ $incident->first_name }} {{ $incident->last_name }}
                        </td>
                        <td class="px-4 py-2">
                            <flux:badge size="sm" variant="subtle" class="rounded-md">{{ $incident->type }}</flux:badge>
                        </td>
                        <td class="px-4 py-2 text-slate-500 text-xs">
                            {{ \Carbon\Carbon::parse($incident->created_at)->format('d/m/Y H:i') }}
                            <span class="text-slate-400 font-normal">({{ \Carbon\Carbon::parse($incident->created_at)->diffForHumans() }})</span>
                        </td>
                        <td class="px-4 py-2 text-center">
                            @if($incident->status === 'Justificada')
                                <flux:badge color="green" size="sm" variant="subtle" class="rounded-md">
                                    Justificada
                                </flux:badge>
                            @else
                                <flux:badge color="red" size="sm" variant="subtle" class="rounded-md">
                                    Pendiente
                                </flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            @if($incident->status === 'Pendiente')
                                <flux:button 
                                    wire:click="justify('{{ $incident->id }}')" 
                                    size="xs" 
                                    variant="subtle"
                                    icon="check"
                                    class="text-green-600 hover:text-green-700 hover:bg-slate-50 dark:hover:bg-green-950/20"
                                >
                                    Justificar
                                </flux:button>
                            @else
                                <div class="flex justify-end pr-4 text-green-600">
                                    <flux:icon name="check-circle" variant="mini" class="w-5 h-5" />
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                            No hay incidencias de asistencia registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</flux:card>
