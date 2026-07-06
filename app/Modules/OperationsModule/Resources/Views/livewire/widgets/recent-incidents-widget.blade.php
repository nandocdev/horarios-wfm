<flux:card>
    <flux:heading size="lg" class="mb-4">Últimas Incidencias de Asistencia</flux:heading>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-zinc-500 uppercase border-b border-zinc-200 dark:border-zinc-800">
                <tr>
                    <th class="px-4 py-3">Agente</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Fecha / Hora</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-right">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($recentIncidents as $incident)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $incident->first_name }} {{ $incident->last_name }}
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" variant="pill">{{ $incident->type }}</flux:badge>
                        </td>
                        <td class="px-4 py-3 text-zinc-500 text-xs">
                            {{ \Carbon\Carbon::parse($incident->created_at)->format('d/m/Y H:i') }}
                            <span class="text-zinc-400 font-normal">({{ \Carbon\Carbon::parse($incident->created_at)->diffForHumans() }})</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($incident->status === 'Justificada')
                                <flux:badge color="green" size="sm" variant="subtle">
                                    Justificada
                                </flux:badge>
                            @else
                                <flux:badge color="red" size="sm" variant="subtle">
                                    Pendiente
                                </flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($incident->status === 'Pendiente')
                                <flux:button 
                                    wire:click="justify('{{ $incident->id }}')" 
                                    size="xs" 
                                    variant="subtle"
                                    icon="check"
                                    class="text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-950/20"
                                >
                                    Justificar
                                </flux:button>
                            @else
                                <div class="flex justify-end pr-4 text-green-500">
                                    <flux:icon name="check-circle" variant="mini" class="w-5 h-5" />
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500">
                            No hay incidencias de asistencia registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</flux:card>
