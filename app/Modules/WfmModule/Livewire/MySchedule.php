<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MySchedule extends Component
{
    public $selectedWeekId;

    public $currentWeek;

    public $selectedDay;

    public function mount()
    {
        $this->currentWeek = WeeklySchedule::where('week_start_date', '<=', Carbon::now()->toDateString())
            ->where('week_end_date', '>=', Carbon::now()->toDateString())
            ->first() ?? WeeklySchedule::orderBy('week_start_date', 'desc')->first();

        $this->selectedWeekId = $this->currentWeek?->id;
        $this->selectedDay = Carbon::now()->dayOfWeekIso;
    }

    public function selectWeek($weekId)
    {
        $this->selectedWeekId = $weekId;
        $this->currentWeek = WeeklySchedule::find($weekId);
    }

    public function selectDay($dayNum)
    {
        $this->selectedDay = (int) $dayNum;
    }

    public function render()
    {
        $employee = Auth::user()->employee;

        $assignments = [];
        $exceptions = collect();
        $intradayActivities = collect();

        if ($employee && $this->selectedWeekId) {
            $assignments = WeeklyScheduleAssignment::with('schedule')
                ->where('weekly_schedule_id', $this->selectedWeekId)
                ->where('employee_id', $employee->id)
                ->get()
                ->keyBy('day_of_week');

            // Cargar excepciones para la semana
            $exceptions = ScheduleException::with('reason')
                ->where('employee_id', $employee->id)
                ->where(function ($query) {
                    $query->whereBetween('start_at', [$this->currentWeek->week_start_date, $this->currentWeek->week_end_date])
                        ->orWhereBetween('end_at', [$this->currentWeek->week_start_date, $this->currentWeek->week_end_date])
                        ->orWhere(function ($q) {
                            $q->where('start_at', '<=', $this->currentWeek->week_start_date)
                                ->where('end_at', '>=', $this->currentWeek->week_end_date);
                        });
                })
                ->get();

            // Cargar actividades programadas para el día seleccionado
            if ($this->selectedDay) {
                $targetDate = $this->currentWeek->week_start_date->copy()->addDays($this->selectedDay - 1);

                $intradayActivities = IntradayActivity::with('activityType')
                    ->where('employee_id', $employee->id)
                    ->whereRaw('time_range && tstzrange(?, ?)', [
                        $targetDate->startOfDay()->toIso8601String(),
                        $targetDate->endOfDay()->toIso8601String(),
                    ])
                    ->get();
            }
        }

        $weeks = WeeklySchedule::orderBy('week_start_date', 'desc')->take(8)->get();

        return view('wfm::livewire.my-schedule', [
            'assignments' => $assignments,
            'exceptions' => $exceptions,
            'intradayActivities' => $intradayActivities,
            'weeks' => $weeks,
            'days' => [
                1 => 'Lunes',
                2 => 'Martes',
                3 => 'Miércoles',
                4 => 'Jueves',
                5 => 'Viernes',
                6 => 'Sábado',
                7 => 'Domingo',
            ],
        ]);
    }
}
