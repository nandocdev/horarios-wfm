<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm" wire:poll.30s>
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">Colas</h3>
    </div>
    <div class="overflow-x-auto max-h-[360px]">
        <table class="w-full text-[10px]">
            <thead class="sticky top-0 bg-white dark:bg-zinc-800">
                <tr class="text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">
                    <th class="text-left py-1.5 px-1.5 font-semibold whitespace-nowrap">Cola</th>
                    <th class="text-right py-1.5 px-1.5 font-semibold whitespace-nowrap" title="Recibidas">Rec.</th>
                    <th class="text-right py-1.5 px-1.5 font-semibold whitespace-nowrap" title="Atendidas">At.</th>
                    <th class="text-right py-1.5 px-1.5 font-semibold whitespace-nowrap" title="Abandonadas">Ab.</th>
                    <th class="text-right py-1.5 px-1.5 font-semibold whitespace-nowrap" title="En Espera">Esp.</th>
                    <th class="text-right py-1.5 px-1.5 font-semibold whitespace-nowrap" title="TMO Abandono">TMO Ab.</th>
                    <th class="text-right py-1.5 px-1.5 font-semibold whitespace-nowrap" title="AHT">AHT</th>
                    <th class="text-right py-1.5 px-1.5 font-semibold whitespace-nowrap" title="Máx Espera">Máx</th>
                    <th class="text-right py-1.5 px-1.5 font-semibold whitespace-nowrap">SLA</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                @foreach ($queues as $queue)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                        <td class="py-1.5 px-1.5 font-medium text-zinc-800 dark:text-zinc-200 whitespace-nowrap">{{ $queue['name'] }}</td>
                        <td class="py-1.5 px-1.5 text-right text-zinc-600 dark:text-zinc-400 font-mono">{{ $queue['recibidas'] }}</td>
                        <td class="py-1.5 px-1.5 text-right text-zinc-600 dark:text-zinc-400 font-mono">{{ $queue['atendidas'] }}</td>
                        <td class="py-1.5 px-1.5 text-right font-mono {{ $queue['abandonadas'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-400' }}">{{ $queue['abandonadas'] }}</td>
                        <td class="py-1.5 px-1.5 text-right font-mono {{ $queue['espera'] > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-zinc-400' }}">{{ $queue['espera'] }}</td>
                        <td class="py-1.5 px-1.5 text-right text-zinc-500 dark:text-zinc-400 font-mono">{{ $queue['tmo_abandono'] !== null ? $queue['tmo_abandono'] . 's' : '—' }}</td>
                        <td class="py-1.5 px-1.5 text-right text-zinc-500 dark:text-zinc-400 font-mono">{{ $queue['aht'] !== null ? $queue['aht'] . 's' : '—' }}</td>
                        <td class="py-1.5 px-1.5 text-right font-mono {{ $queue['max_espera'] > 60 ? 'text-red-600 dark:text-red-400' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $queue['max_espera'] > 0 ? $queue['max_espera'] . 's' : '—' }}</td>
                        <td class="py-1.5 px-1.5 text-right font-bold {{ $queue['slaClass'] }}">{{ $queue['sla'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
