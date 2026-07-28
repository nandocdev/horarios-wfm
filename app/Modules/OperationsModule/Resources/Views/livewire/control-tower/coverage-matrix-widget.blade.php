<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5" wire:poll.60s>
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">Cobertura por Intervalo</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">
                    <th class="text-left py-2 font-medium">Hora</th>
                    <th class="text-right py-2 font-medium">Req</th>
                    <th class="text-right py-2 font-medium">Prog</th>
                    <th class="text-right py-2 font-medium">Gap</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="border-b border-zinc-100 dark:border-zinc-700/50 {{ $row['class'] }}">
                        <td class="py-1.5 font-medium text-zinc-700 dark:text-zinc-300">{{ $row['hour'] }}</td>
                        <td class="py-1.5 text-right text-zinc-600 dark:text-zinc-400">{{ $row['req'] }}</td>
                        <td class="py-1.5 text-right text-zinc-600 dark:text-zinc-400">{{ $row['prog'] }}</td>
                        <td class="py-1.5 text-right font-semibold">{{ $row['signal'] }} {{ $row['gap'] >= 0 ? '+' : '' }}{{ $row['gap'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
