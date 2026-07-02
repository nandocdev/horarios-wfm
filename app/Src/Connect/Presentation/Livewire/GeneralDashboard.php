<?php

declare(strict_types=1);

namespace App\Src\Connect\Presentation\Livewire;

use App\Src\Connect\Infrastructure\Persistence\EloquentCsqRealtimeStat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard General — Contact Center')]
class GeneralDashboard extends Component
{
    public string $dateRange = 'today';

    #[Computed]
    public function dateBoundaries(): array
    {
        return match ($this->dateRange) {
            'today' => [Carbon::today(), Carbon::tomorrow()],
            'this_week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            default => [Carbon::today(), Carbon::tomorrow()],
        };
    }

    #[Computed]
    public function csqStats()
    {
        return EloquentCsqRealtimeStat::orderBy('csq_name')->get();
    }

    #[Computed]
    public function metrics(): array
    {
        $totals = EloquentCsqRealtimeStat::select(
            DB::raw('COALESCE(SUM(calls_waiting), 0) as total_waiting'),
            DB::raw('COALESCE(SUM(agents_logged_on), 0) as total_logged'),
            DB::raw('COALESCE(SUM(agents_ready), 0) as total_ready'),
            DB::raw('COALESCE(SUM(agents_talking), 0) as total_talking'),
            DB::raw('COALESCE(SUM(agents_not_ready), 0) as total_not_ready'),
        )->first();

        return [
            'total_waiting' => (int) ($totals->total_waiting ?? 0),
            'total_logged' => (int) ($totals->total_logged ?? 0),
            'total_ready' => (int) ($totals->total_ready ?? 0),
            'total_talking' => (int) ($totals->total_talking ?? 0),
            'total_not_ready' => (int) ($totals->total_not_ready ?? 0),
        ];
    }

    public function render()
    {
        return view('connect::livewire.general-dashboard', [
            'csqStats' => $this->csqStats,
            'metrics' => $this->metrics,
        ]);
    }
}
