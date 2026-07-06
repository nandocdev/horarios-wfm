<flux:card>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="lg">Estado de Colas ({{ $this->isHistorical ? 'Histórico' : 'Realtime' }})</flux:heading>
        <flux:badge variant="subtle">ACD Data</flux:badge>
    </div>

    <div class="space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead
                    class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-4 py-3">Cola</th>
                        <th class="px-4 py-3 text-center">Espera</th>
                        <th class="px-4 py-3 text-center">Hablando</th>
                        <th class="px-4 py-3 text-center text-blue-600">Recibidas</th>
                        <th class="px-4 py-3 text-center text-green-600">Atendidas</th>
                        <th class="px-4 py-3 text-center text-red-600">Aband.</th>
                        <th class="px-4 py-3 text-center">T. Máx. Espera</th>
                        <th class="px-4 py-3 text-center">SL %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($queueStats as $queue)
                        <tr>
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $queue['name'] }}
                            </td>
                            <td class="px-4 py-3 text-center font-bold">
                                @if($this->isHistorical)
                                    <flux:badge color="zinc" variant="subtle">
                                        -
                                    </flux:badge>
                                @else
                                    <flux:badge
                                        :color="$queue['status'] === 'danger' ? 'red' : ($queue['status'] === 'warning' ? 'amber' : 'green')"
                                        variant="subtle">
                                        {{ $queue['waiting'] }}
                                    </flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-blue-600 font-bold">
                                {{ $this->isHistorical ? '-' : $queue['talking'] }}
                            </td>
                            <td class="px-4 py-3 text-center text-zinc-600 dark:text-zinc-400">
                                {{ $queue['received'] }}
                            </td>
                            <td class="px-4 py-3 text-center text-green-600 dark:text-green-400 font-medium">
                                {{ $queue['handled'] }}
                            </td>
                            <td class="px-4 py-3 text-center text-red-600 dark:text-red-400 font-medium">
                                {{ $queue['abandoned'] }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-zinc-500">
                                @if($this->isHistorical || $queue['name'] === 'LLAMADAS DIRECTAS / SALIENTES' || !isset($queue['lwt']))
                                    -
                                @else
                                    {{ sprintf('%02d:%02d', floor($queue['lwt'] / 60), $queue['lwt'] % 60) }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <flux:badge
                                    :color="$queue['sl'] < 80 ? 'red' : ($queue['sl'] < 90 ? 'amber' : 'green')"
                                    variant="outline" size="sm">
                                    {{ round($queue['sl'], 0) }}%
                                </flux:badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-zinc-500">
                                No hay actividad de llamadas registrada para esta fecha.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</flux:card>
