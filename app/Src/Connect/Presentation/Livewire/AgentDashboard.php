<?php

declare(strict_types=1);

namespace App\Src\Connect\Presentation\Livewire;

use App\Src\Connect\Infrastructure\Persistence\EloquentAgentState;
use App\Src\Connect\Infrastructure\Persistence\EloquentCallEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Mis Datos — Contact Center')]
class AgentDashboard extends Component
{
    use WithPagination;

    public string $dateRange = 'today';

    #[Computed]
    public function employee(): ?object
    {
        return auth()->user()?->employee;
    }

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
    public function state(): ?EloquentAgentState
    {
        if (! $this->employee) {
            return null;
        }

        return EloquentAgentState::where('employee_id', $this->employee->id)->first();
    }

    #[Computed]
    public function metrics(): array
    {
        if (! $this->employee) {
            return ['total_calls' => 0, 'avg_talk_time' => 0, 'avg_handle_time' => 0, 'abandoned' => 0];
        }

        [$start, $end] = $this->dateBoundaries();

        $stats = EloquentCallEvent::where('employee_id', $this->employee->id)
            ->whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('AVG(talk_time) as avg_talk_time'),
            )->first();

        return [
            'total_calls' => (int) ($stats->total_calls ?? 0),
            'avg_talk_time' => round((float) ($stats->avg_talk_time ?? 0), 0),
            'avg_handle_time' => round((float) ($stats->avg_talk_time ?? 0), 0),
            'abandoned' => 0,
        ];
    }

    #[Computed]
    public function recentCalls()
    {
        if (! $this->employee) {
            return collect();
        }

        [$start, $end] = $this->dateBoundaries();

        return EloquentCallEvent::where('employee_id', $this->employee->id)
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function render()
    {
        return view('connect::livewire.agent-dashboard');
    }
}
