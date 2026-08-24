<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Carbon\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class CoverageMatrixWidget extends Component
{
    /**
     * Posiciones que atienden llamadas ("Operador Asist. Serv. Aseg. I y II").
     * Misma definición de agente que usa AdherenceHeatmapWidget. La "Base" de
     * cobertura cuenta solo estas posiciones: la plantilla operativa no incluye
     * coordinadores, jefes ni analistas aunque tengan turno asignado.
     */
    private const AGENT_POSITION_IDS = [1, 2];

    public array $employeeIds = [];

    public string $selectedDate;

    public function placeholder()
    {
        return '<div class="h-64 bg-zinc-100 dark:bg-zinc-800 rounded-xl animate-pulse"></div>';
    }

    public function render()
    {
        $today = $this->selectedDate;
        $dayOfWeek = Carbon::parse($today)->dayOfWeekIso;

        $assignments = WeeklyScheduleAssignment::whereHas('weeklySchedule', function ($q) use ($today) {
            $q->where('week_start_date', '<=', $today)->where('week_end_date', '>=', $today);
        })
            ->where('day_of_week', $dayOfWeek)
            ->where('is_replaced', false)
            ->whereIn('employee_id', $this->employeeIds)
            ->whereHas('employee', fn ($q) => $q->whereIn('position_id', self::AGENT_POSITION_IDS))
            ->get(['employee_id', 'start_time', 'end_time', 'lunch_start_time', 'lunch_end_time', 'break_start_time', 'break_end_time'])
            // Un agente = un turno al día. Si existieran filas duplicadas (p. ej.
            // dos planes que cubran la misma fecha), se cuenta una sola vez para
            // que la Base nunca supere la plantilla operativa real.
            ->unique('employee_id')
            ->values();

        $absentIds = ScheduleException::whereIn('employee_id', $this->employeeIds)
            ->whereDate('start_at', '<=', $today)
            ->whereDate('end_at', '>=', $today)
            ->pluck('employee_id')
            ->unique()
            ->toArray();

        $leaveIds = LeaveRequest::whereIn('employee_id', $this->employeeIds)
            ->where('status', 'approved')
            ->whereDate('start_time', '<=', $today)
            ->whereDate('end_time', '>=', $today)
            ->pluck('employee_id')
            ->unique()
            ->toArray();

        $intradayActivities = IntradayActivity::whereIn('employee_id', $this->employeeIds)
            ->get(['employee_id', 'time_range']);

        $fullDayAbsent = array_unique(array_merge($absentIds, $leaveIds));

        $hours = range(6, 17);
        $rows = collect();
        $totalScheduled = 0;
        $totalAbsences = 0;

        foreach ($hours as $h) {
            $label = sprintf('%02d:00', $h);

            $scheduled = $assignments->filter(function ($a) use ($label) {
                if ($a->start_time === null || $a->end_time === null) {
                    return false;
                }

                return $a->start_time->format('H:i') <= $label
                    && $a->end_time->format('H:i') >= $label;
            });

            $hourAbsences = $scheduled->filter(fn ($a) => in_array($a->employee_id, $fullDayAbsent));

            $hourLunch = $scheduled->filter(function ($a) use ($label) {
                if ($a->lunch_start_time === null || $a->lunch_end_time === null) {
                    return false;
                }

                return $a->lunch_start_time->format('H:i') <= $label
                    && $a->lunch_end_time->format('H:i') > $label;
            });

            $hourBreak = $scheduled->filter(function ($a) use ($label) {
                if ($a->break_start_time === null || $a->break_end_time === null) {
                    return false;
                }

                return $a->break_start_time->format('H:i') <= $label
                    && $a->break_end_time->format('H:i') > $label;
            });

            $hourIntraday = $scheduled->filter(function ($a) use ($label, $intradayActivities) {
                $activities = $intradayActivities->where('employee_id', $a->employee_id);
                if ($activities->isEmpty()) {
                    return false;
                }

                foreach ($activities as $act) {
                    $start = $act->getRangeStart();
                    $end = $act->getRangeEnd();
                    if ($start && $end) {
                        $actStart = $start->format('H:i');
                        $actEnd = $end->format('H:i');
                        if ($actStart <= $label && $actEnd > $label) {
                            return true;
                        }
                    }
                }

                return false;
            });

            $excluded = $hourAbsences
                ->merge($hourLunch)
                ->merge($hourBreak)
                ->merge($hourIntraday)
                ->unique('employee_id');

            $netAvailable = $scheduled->count() - $excluded->count();
            $required = max(1, (int) round($scheduled->count() * 1.1));
            $gap = $required - $netAvailable;

            $totalScheduled += $scheduled->count();
            $totalAbsences += $excluded->count();

            $class = $gap <= -3 ? 'bg-green-50 dark:bg-green-900/20'
                : ($gap <= 0 ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-red-50 dark:bg-red-900/20');

            $rows->push([
                'hour' => $label,
                'req' => $required,
                'prog' => $netAvailable,
                'programmed_raw' => $scheduled->count(),
                'ausentes' => $hourAbsences->count(),
                'almuerzo' => $hourLunch->count(),
                'descanso' => $hourBreak->count(),
                'intradia' => $hourIntraday->count(),
                'gap' => $gap,
                'class' => $class,
            ]);
        }

        return view('operations::livewire.control-tower.coverage-matrix-widget', [
            'rows' => $rows,
            'totalScheduled' => $totalScheduled,
            'totalAbsences' => $totalAbsences,
        ]);
    }
}
