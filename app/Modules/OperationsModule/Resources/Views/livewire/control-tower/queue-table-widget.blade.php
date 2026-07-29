<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm">
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">Colas</h3>
    </div>
    <div class="overflow-x-auto max-h-[300px]">
        <table class="w-full text-xs">
            <thead class="sticky top-0 bg-white dark:bg-zinc-800">
                <tr class="text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">
                    <th class="text-left py-2 px-2 font-semibold">Cola</th>
                    <th class="text-right py-2 px-2 font-semibold">SLA</th>
                    <th class="text-right py-2 px-2 font-semibold">Espera</th>
                    <th class="text-right py-2 px-2 font-semibold">Calls</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                @foreach ($queues as $queue)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                        <td class="py-2 px-2 font-medium text-zinc-800 dark:text-zinc-200">{{ $queue['name'] }}</td>
                        <td class="py-2 px-2 text-right font-bold {{ $queue['slaClass'] }}">{{ $queue['sla'] }}%</td>
                        <td class="py-2 px-2 text-right text-zinc-600 dark:text-zinc-400">{{ $queue['waiting'] }}</td>
                        <td class="py-2 px-2 text-right text-zinc-600 dark:text-zinc-400">{{ $queue['calls'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
