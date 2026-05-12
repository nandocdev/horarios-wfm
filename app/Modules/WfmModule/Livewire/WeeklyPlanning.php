<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Actions\PublishWeeklyScheduleAction;
use App\Modules\WfmModule\Models\WeeklySchedule;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class WeeklyPlanning extends Component
{
    use WithPagination;

    public bool $showCreateModal = false;

    public ?string $nextWeekStart = null;

    public ?string $nextWeekEnd = null;

    public function confirmCreateWeek(): void
    {
        $this->authorize('schedules.manage');
        $lastWeek = WeeklySchedule::orderBy('week_start_date', 'desc')->first();
        $startDate = $lastWeek
            ? Carbon::parse($lastWeek->week_start_date)->addWeek()->startOfWeek()
            : Carbon::now()->startOfWeek();

        $endDate = $startDate->copy()->endOfWeek();

        $this->nextWeekStart = $startDate->toDateString();
        $this->nextWeekEnd = $endDate->toDateString();
        $this->showCreateModal = true;
    }

    public function createNextWeek(): void
    {
        $this->authorize('schedules.manage');
        if (! $this->nextWeekStart || ! $this->nextWeekEnd) {
            return;
        }

        WeeklySchedule::create([
            'week_start_date' => $this->nextWeekStart,
            'week_end_date' => $this->nextWeekEnd,
            'status' => 'draft',
        ]);

        $this->showCreateModal = false;
        $this->reset(['nextWeekStart', 'nextWeekEnd']);
        \Flux::toast('Semana creada exitosamente.');
    }

    public function publishWeek(int $weekId, PublishWeeklyScheduleAction $action): void
    {
        $this->authorize('schedules.manage');
        try {
            $action->execute($weekId);
            \Flux::toast('Semana publicada exitosamente.');
        } catch (\Exception $e) {
            \Flux::toast($e->getMessage(), variant: 'danger');
        }
    }

    public function render()
    {
        // Contamos cuántos equipos únicos tienen asignación en esa semana (usamos el lunes como referencia)
        $weeks = WeeklySchedule::withCount(['teamAssignments' => function ($query) {
            $query->where('day_of_week', 1);
        }])
            ->orderBy('week_start_date', 'desc')
            ->paginate(10);

        return view('wfm::livewire.weekly-planning', [
            'weeks' => $weeks,
        ]);
    }
}
