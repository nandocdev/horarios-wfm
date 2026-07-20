<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\Widgets;

use App\Shared\Contracts\WfmModule\ScheduleValidationInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class CriticalAlertsWidget extends Component
{
    public string $selectedDate;

    public function mount(string $selectedDate): void
    {
        $this->selectedDate = $selectedDate;
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div class="h-[300px] bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl animate-pulse"></div>
        HTML;
    }

    public function render(ScheduleValidationInterface $validationService)
    {
        $date = Carbon::parse($this->selectedDate);

        // 1. Solicitudes pendientes de aprobación
        $pendingApprovals = DB::table('leave_requests')
            ->where('status', 'pending')
            ->count() +
            DB::table('shift_swap_requests')
                ->where('status', 'pending')
                ->count();

        // 2. Conflictos de mallas horarias (Pre-check Preventivo)
        $scheduleConflicts = [];
        try {
            $scheduleConflicts = $validationService->detectScheduleConflicts($this->selectedDate);
        } catch (\Exception $e) {
            // Fallback silencioso en caso de error
        }

        // 3. Colas con SLA Crítico (solo hoy)
        $criticalQueues = [];
        if ($date->isToday()) {
            try {
                $criticalQueues = DB::table('csq_realtime_stats')
                    ->where('calls_waiting', '>', 5)
                    ->get()
                    ->map(fn ($csq) => [
                        'name' => $csq->csq_name,
                        'waiting' => $csq->calls_waiting,
                        'sl' => $csq->service_level_long_term,
                    ])
                    ->toArray();
            } catch (\Exception $e) {
                // Fallback silencioso
            }
        }

        return view('operations::livewire.widgets.critical-alerts-widget', [
            'pendingApprovals' => $pendingApprovals,
            'scheduleConflicts' => $scheduleConflicts,
            'criticalQueues' => $criticalQueues,
        ]);
    }
}
