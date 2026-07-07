<flux:card>
    <div class="flex items-center justify-between mb-8">
        <flux:heading size="lg">Estado de Colas ({{ $this->isHistorical ? 'Histórico' : 'Realtime' }})</flux:heading>
        <flux:badge variant="subtle" class="rounded-md">ACD Data</flux:badge>
    </div>

    <div class="space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead
                    class="sticky top-0 z-10 text-xs text-slate-500 uppercase bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors duration-150">
                        <th class="px-4 py-2">Cola</th>
                        <th class="px-4 py-2 text-center">Espera</th>
                        <th class="px-4 py-2 text-center">Hablando</th>
                        <th class="px-4 py-2 text-center text-blue-600">Recibidas</th>
                        <th class="px-4 py-2 text-center text-green-600">Atendidas</th>
                        <th class="px-4 py-2 text-center text-red-600">Aband.</th>
                        <th class="px-4 py-2 text-center">T. Máx. Espera</th>
                        <th class="px-4 py-2 text-center">SL %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($queueStats as $queue)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors duration-150">
                            <td class="px-4 py-2 font-semibold text-slate-900 dark:text-slate-100">
                                {{ $queue['name'] }}
                            </td>
                            <td class="px-4 py-2 text-center font-bold">
                                @if($this->isHistorical)
                                    <flux:badge color="zinc" variant="subtle" class="rounded-md">
                                        -
                                    </flux:badge>
                                @else
                                    <flux:badge
                                        :color="$queue['status'] === 'danger' ? 'red' : ($queue['status'] === 'warning' ? 'amber' : 'green')"
                                        variant="subtle" class="rounded-md">
                                        {{ $queue['waiting'] }}
                                    </flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center text-blue-600 font-bold">
                                {{ $this->isHistorical ? '-' : $queue['talking'] }}
                            </td>
                            <td class="px-4 py-2 text-center text-slate-600 dark:text-slate-400">
                                {{ $queue['received'] }}
                            </td>
                            <td class="px-4 py-2 text-center text-green-600 dark:text-green-400 font-medium">
                                {{ $queue['handled'] }}
                            </td>
                            <td class="px-4 py-2 text-center text-red-600 dark:text-red-400 font-medium">
                                {{ $queue['abandoned'] }}
                            </td>
                            <td class="px-4 py-2 text-center font-mono text-xs text-slate-500">
                                @if($this->isHistorical || $queue['name'] === 'LLAMADAS DIRECTAS / SALIENTES' || !isset($queue['lwt']))
                                    -
                                @else
                                    {{ sprintf('%02d:%02d', floor($queue['lwt'] / 60), $queue['lwt'] % 60) }}
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                <flux:badge
                                    :color="$queue['sl'] < 80 ? 'red' : ($queue['sl'] < 90 ? 'amber' : 'green')"
                                    variant="outline" size="sm" class="rounded-md">
                                    {{ round($queue['sl'], 0) }}%
                                </flux:badge>
                            </td>
                        </tr>
                    @empty
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors duration-150">
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                                No hay actividad de llamadas registrada para esta fecha.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</flux:card>
