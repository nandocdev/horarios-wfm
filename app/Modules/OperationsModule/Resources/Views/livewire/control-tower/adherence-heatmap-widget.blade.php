<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">Adherencia (HeatMap)</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="text-zinc-500 dark:text-zinc-400">
                    <th class="text-left py-1 pr-3 font-medium">Equipo</th>
                    @foreach ($hours as $h)
                        <th class="text-center py-1 px-1 font-medium w-8">{{ sprintf('%02d', $h) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="border-t border-zinc-100 dark:border-zinc-700/50">
                        <td class="py-2 pr-3 font-medium text-zinc-700 dark:text-zinc-300">{{ $row['name'] }}</td>
                        @foreach ($row['hours'] as $cell)
                            <td class="text-center py-1 px-1">
                                <div class="w-7 h-7 rounded {{ $cell['class'] }} mx-auto flex items-center justify-center">
                                    @if ($cell['value'] !== null)
                                        <span class="text-[9px] font-bold {{ $cell['value'] >= 85 ? 'text-white' : 'text-white' }}">{{ $cell['value'] >= 100 ? '99' : number_format($cell['value'], 0) }}</span>
                                    @endif
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="flex items-center gap-4 mt-3 text-[10px] text-zinc-500 dark:text-zinc-400">
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-500"></span> ≥95%</span>
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-yellow-500"></span> 85-94%</span>
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-500"></span> &lt;85%</span>
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-zinc-200 dark:bg-zinc-700"></span> Sin datos</span>
    </div>
</div>
