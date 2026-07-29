<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm flex flex-col h-full">
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700 shrink-0">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">Pendientes</h3>
    </div>
    <div class="flex-1 grid grid-cols-2 gap-3 mb-4">
        <div class="flex flex-col items-center justify-center bg-zinc-50 dark:bg-zinc-700/30 rounded-md p-3 border border-zinc-100 dark:border-zinc-700">
            <span class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $pendingApprovals }}</span>
            <span class="text-[10px] font-medium text-zinc-500 uppercase">Aprobaciones</span>
        </div>
        <div class="flex flex-col items-center justify-center bg-zinc-50 dark:bg-zinc-700/30 rounded-md p-3 border border-zinc-100 dark:border-zinc-700">
            <span class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $pendingLeaves }}</span>
            <span class="text-[10px] font-medium text-zinc-500 uppercase">Permisos</span>
        </div>
        <div class="flex flex-col items-center justify-center bg-zinc-50 dark:bg-zinc-700/30 rounded-md p-3 border border-zinc-100 dark:border-zinc-700">
            <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $pendingSwaps }}</span>
            <span class="text-[10px] font-medium text-zinc-500 uppercase">Cambios</span>
        </div>
        <div class="flex flex-col items-center justify-center bg-zinc-50 dark:bg-zinc-700/30 rounded-md p-3 border border-zinc-100 dark:border-zinc-700">
            <span class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $vacations }}</span>
            <span class="text-[10px] font-medium text-zinc-500 uppercase">Vacaciones</span>
        </div>
    </div>
    <div class="shrink-0 text-center pt-2 border-t border-zinc-100 dark:border-zinc-700">
        <a href="{{ route('schedules.leave-request') ?? '#' }}" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">[Ver Bandeja]</a>
    </div>
</div>
