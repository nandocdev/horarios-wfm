<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm" wire:poll.60s>
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">Cobertura por Intervalo</h3>
    </div>
    <div class="overflow-x-auto max-h-[300px]">
        <table class="w-full text-xs">
            <thead class="sticky top-0 bg-white dark:bg-zinc-800">
                <tr class="text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">
                    <th class="text-left py-2 px-2 font-semibold">Hora</th>
                    <th class="text-right py-2 px-2 font-semibold">Req</th>
                    <th class="text-right py-2 px-2 font-semibold">Prog</th>
                    <th class="text-right py-2 px-2 font-semibold">Gap</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                @foreach ($rows as $row)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors {{ $row['gap'] < 0 ? 'bg-red-50/50 dark:bg-red-900/10' : '' }}">
                        <td class="py-2 px-2 font-medium text-zinc-700 dark:text-zinc-300">{{ $row['hour'] }}</td>
                        <td class="py-2 px-2 text-right text-zinc-600 dark:text-zinc-400">{{ $row['req'] }}</td>
                        <td class="py-2 px-2 text-right text-zinc-600 dark:text-zinc-400">{{ $row['prog'] }}</td>
                        <td class="py-2 px-2 text-right font-bold {{ $row['gap'] < 0 ? 'text-red-600 dark:text-red-400' : ($row['gap'] == 0 ? 'text-green-600 dark:text-green-400' : 'text-zinc-700 dark:text-zinc-300') }}">
                            <div class="flex items-center justify-end gap-1">
                                <span>{{ $row['gap'] > 0 ? '+' : '' }}{{ $row['gap'] }}</span>
                                @if ($row['gap'] < 0)
                                    <flux:icon name="chevron-down" class="w-3 h-3 text-red-500" />
                                @elseif ($row['gap'] == 0)
                                    <flux:icon name="check" class="w-3 h-3 text-green-500" />
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
