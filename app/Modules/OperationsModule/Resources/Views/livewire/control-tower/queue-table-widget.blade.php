<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">Colas</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">
                    <th class="text-left py-2 font-medium">Cola</th>
                    <th class="text-right py-2 font-medium">SLA</th>
                    <th class="text-right py-2 font-medium">Espera</th>
                    <th class="text-right py-2 font-medium">Calls</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($queues as $queue)
                    <tr class="border-b border-zinc-100 dark:border-zinc-700/50 hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <td class="py-2 font-medium text-zinc-700 dark:text-zinc-300">{{ $queue['name'] }}</td>
                        <td class="py-2 text-right font-semibold {{ $queue['slaClass'] }}">{{ $queue['sla'] }}%</td>
                        <td class="py-2 text-right text-zinc-600 dark:text-zinc-400">{{ $queue['waiting'] }}</td>
                        <td class="py-2 text-right text-zinc-600 dark:text-zinc-400">{{ $queue['calls'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
