<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\AnalyticsModule\Models\StaffingRequirement;
use App\Shared\Support\CallQueueCache;
use Livewire\Component;

class StaffingAnalysis extends Component
{
    public string $date = '';

    public ?string $queueFilter = null;

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function render()
    {
        $queues = app(CallQueueCache::class)->selectIds();

        $query = StaffingRequirement::whereDate('interval_start', $this->date);

        if ($this->queueFilter) {
            $query->where('queue_id', $this->queueFilter);
        }

        $intervals = $query->orderBy('interval_start')->get();
        $totalIntervals = $intervals->count();

        $understaffed = $intervals->filter(fn ($i) => $i->gap > 0);
        $overstaffed = $intervals->filter(fn ($i) => $i->gap < 0);
        $adequate = $intervals->filter(fn ($i) => $i->gap == 0);

        $maxGap = $intervals->max('gap') ?? 0;
        $maxOverstaff = abs($intervals->min('gap') ?? 0);
        $avgCoverage = $intervals->avg('coverage') ?? 0;

        return view('operations::livewire.staffing-analysis', [
            'queues' => $queues,
            'intervals' => $intervals,
            'totalIntervals' => $totalIntervals,
            'understaffed' => $understaffed,
            'overstaffed' => $overstaffed,
            'adequate' => $adequate,
            'underCount' => $understaffed->count(),
            'overCount' => $overstaffed->count(),
            'adequateCount' => $adequate->count(),
            'maxGap' => $maxGap,
            'maxOverstaff' => $maxOverstaff,
            'avgCoverage' => $avgCoverage,
            'underPct' => $totalIntervals > 0 ? round(($understaffed->count() / $totalIntervals) * 100, 1) : 0,
            'overPct' => $totalIntervals > 0 ? round(($overstaffed->count() / $totalIntervals) * 100, 1) : 0,
            'adequatePct' => $totalIntervals > 0 ? round(($adequate->count() / $totalIntervals) * 100, 1) : 0,
        ])->layout('layouts.app', ['title' => 'Análisis de Staffing']);
    }
}
