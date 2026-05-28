<flux:card>
    <flux:heading size="lg" class="mb-4">Últimas Incidencias de Asistencia</flux:heading>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-zinc-500 uppercase">
                <tr class="border-b border-zinc-100 dark:border-zinc-800">
                    <th class="px-4 py-2">Agente</th>
                    <th class="px-4 py-2">Tipo</th>
                    <th class="px-4 py-2">Hora</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($recentIncidents as $incident)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $incident->first_name }} {{ $incident->last_name }}
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" variant="pill">{{ $incident->type }}</flux:badge>
                        </td>
                        <td class="px-4 py-3 text-zinc-500 text-xs">
                            {{ \Carbon\Carbon::parse($incident->created_at)->diffForHumans() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-zinc-500">No hay incidencias
                            recientes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</flux:card>
