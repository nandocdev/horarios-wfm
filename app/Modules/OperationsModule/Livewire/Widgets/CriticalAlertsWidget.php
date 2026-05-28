<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\Widgets;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class CriticalAlertsWidget extends Component
{
    public array $queueStats = [];

    public function mount(array $queueStats = []): void
    {
        $this->queueStats = $queueStats;
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div class="h-[300px] bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl animate-pulse"></div>
        HTML;
    }

    public function render()
    {
        $pendingApprovals = DB::table('leave_requests')
            ->where('status', 'pending')
            ->count() +
            DB::table('shift_swap_requests')
                ->where('status', 'pending')
                ->count();

        return view('operations::livewire.widgets.critical-alerts-widget', [
            'pendingApprovals' => $pendingApprovals,
        ]);
    }
}
