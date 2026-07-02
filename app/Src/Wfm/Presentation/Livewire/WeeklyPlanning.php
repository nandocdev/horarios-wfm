<?php

declare(strict_types=1);

namespace App\Src\Wfm\Presentation\Livewire;

use App\Src\Wfm\Infrastructure\Persistence\EloquentWeeklySchedule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Planificación Semanal')]
class WeeklyPlanning extends Component
{
    use WithPagination;

    public function createNextWeek(): void
    {
        $last = EloquentWeeklySchedule::latest('week_start_date')->first();

        $start = $last
            ? $last->week_end_date->copy()->addDay()
            : now()->startOfWeek();

        $end = $start->copy()->addDays(6);

        EloquentWeeklySchedule::create([
            'week_start_date' => $start,
            'week_end_date' => $end,
            'status' => 'draft',
        ]);

        toast('Nueva semana creada.');
    }

    public function publish(int $id): void
    {
        $schedule = EloquentWeeklySchedule::findOrFail($id);

        if ($schedule->status === 'published') {
            $this->addError('publish', 'Ya está publicada.');
            return;
        }

        $schedule->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        toast('Planificación semanal publicada.');
    }

    public function render()
    {
        return view('wfm-src::livewire.weekly-planning', [
            'weeks' => EloquentWeeklySchedule::withCount('assignments')
                ->latest('week_start_date')
                ->paginate(10),
        ]);
    }
}
