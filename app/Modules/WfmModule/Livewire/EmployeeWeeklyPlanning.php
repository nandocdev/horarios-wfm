<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Component;

class EmployeeWeeklyPlanning extends Component
{
    public WeeklySchedule $week;

    public Employee $employee;

    // Datos de la semana [day_of_week] => [fields]
    public array $assignments = [];

    public array $exceptionsByDay = [];

    public Collection $activeSchedules;

    public function mount(WeeklySchedule $week, Employee $employee): void
    {
        $this->week = $week;
        $this->employee = $employee;

        $this->activeSchedules = Schedule::where('is_active', true)->orderBy('name')->get();

        $this->loadAssignments();
    }

    public function loadAssignments(): void
    {
        $existing = WeeklyScheduleAssignment::with('schedule')
            ->where('weekly_schedule_id', $this->week->id)
            ->where('employee_id', $this->employee->id)
            ->get()
            ->keyBy('day_of_week');

        $this->assignments = [];
        $this->exceptionsByDay = [];

        $exceptions = ScheduleException::where('employee_id', $this->employee->id)
            ->where(function ($query) {
                $query->whereBetween('start_at', [$this->week->week_start_date, $this->week->week_end_date])
                    ->orWhereBetween('end_at', [$this->week->week_start_date, $this->week->week_end_date])
                    ->orWhere(function ($q) {
                        $q->where('start_at', '<=', $this->week->week_start_date)
                            ->where('end_at', '>=', $this->week->week_end_date);
                    });
            })
            ->with('reason')
            ->get();

        for ($day = 1; $day <= 7; $day++) {
            $currentDate = $this->week->week_start_date->copy()->addDays($day - 1);
            $dayException = $exceptions->first(function ($ex) use ($currentDate) {
                return $currentDate->between($ex->start_at->startOfDay(), $ex->end_at->endOfDay());
            });

            if ($dayException) {
                $this->exceptionsByDay[$day] = $dayException;
            }

            $record = $existing->get($day);

            $this->assignments[$day] = [
                'schedule_id' => $record?->schedule_id,
                'start_time' => $this->formatTime($record?->start_time) ?? ($record?->schedule ? $this->formatTime($record->schedule->start_time) : null),
                'end_time' => $this->formatTime($record?->end_time) ?? ($record?->schedule ? $this->formatTime($record->schedule->end_time) : null),
                'lunch_start_time' => $this->formatTime($record?->lunch_start_time),
                'break_start_time' => $this->formatTime($record?->break_start_time),
            ];
        }
    }

    /**
     * Formatea un tiempo (Carbon o string) a H:i.
     */
    private function formatTime($value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('H:i');
        }

        if (is_string($value)) {
            return substr($value, 0, 5);
        }

        return null;
    }

    public function updated($property, $value): void
    {
        if (str_ends_with($property, '.schedule_id') && $value) {
            $parts = explode('.', $property);
            $dayNum = $parts[1];
            $schedule = Schedule::find($value);
            if ($schedule) {
                $this->assignments[$dayNum]['start_time'] = $this->formatTime($schedule->start_time);
                $this->assignments[$dayNum]['end_time'] = $this->formatTime($schedule->end_time);
            }
        }
    }

    public function save(): void
    {
        foreach ($this->assignments as $day => $data) {
            if (empty($data['schedule_id'])) {
                WeeklyScheduleAssignment::where([
                    'weekly_schedule_id' => $this->week->id,
                    'employee_id' => $this->employee->id,
                    'day_of_week' => $day,
                ])->delete();

                continue;
            }

            // Calculamos tiempos de fin basados en el turno (esto podría ser una Action)
            $schedule = Schedule::find($data['schedule_id']);

            $lunchEndTime = $data['lunch_start_time']
                ? Carbon::parse($data['lunch_start_time'])->addMinutes($schedule->lunch_minutes)->format('H:i:s')
                : null;

            $breakEndTime = $data['break_start_time']
                ? Carbon::parse($data['break_start_time'])->addMinutes($schedule->break_minutes)->format('H:i:s')
                : null;

            WeeklyScheduleAssignment::updateOrCreate(
                [
                    'weekly_schedule_id' => $this->week->id,
                    'employee_id' => $this->employee->id,
                    'day_of_week' => $day,
                ],
                [
                    'schedule_id' => (int) $data['schedule_id'],
                    'start_time' => $data['start_time'] ?: null,
                    'end_time' => $data['end_time'] ?: null,
                    'lunch_start_time' => $data['lunch_start_time'] ?: null,
                    'lunch_end_time' => $lunchEndTime,
                    'break_start_time' => $data['break_start_time'] ?: null,
                    'break_end_time' => $breakEndTime,
                ]
            );
        }

        \Flux::toast('Horario semanal actualizado correctamente.');
        $this->redirect(route('schedules.planning.team', [$this->week->id, $this->employee->team_id]), navigate: true);
    }

    public function render(): View
    {
        return view('wfm::livewire.employee-weekly-planning', [
            'days' => [
                1 => __('Lunes'),
                2 => __('Martes'),
                3 => __('Miércoles'),
                4 => __('Jueves'),
                5 => __('Viernes'),
                6 => __('Sábado'),
                7 => __('Domingo'),
            ],
        ]);
    }
}
