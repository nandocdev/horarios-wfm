<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Pendientes</h3>
    <div class="grid grid-cols-4 gap-3 text-center">
        <div>
            <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ $pendingApprovals }}</p>
            <p class="text-[10px] text-zinc-500">Pendientes</p>
        </div>
        <div>
            <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">{{ $pendingLeaves }}</p>
            <p class="text-[10px] text-zinc-500">Permisos</p>
        </div>
        <div>
            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $pendingSwaps }}</p>
            <p class="text-[10px] text-zinc-500">Cambios</p>
        </div>
        <div>
            <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ $vacations }}</p>
            <p class="text-[10px] text-zinc-500">Vacaciones</p>
        </div>
    </div>
</div>
