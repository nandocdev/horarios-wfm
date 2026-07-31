<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Alerts\Evaluators;

use App\Modules\OperationsModule\Alerts\Models\AlertRule;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use Illuminate\Support\Facades\Cache;

class UpcomingShiftReminderEvaluator extends BaseAlertEvaluator
{
    public function eventType(): string
    {
        return 'agent.upcoming_shift_reminder';
    }

    public function evaluate(AlertRule $rule): void
    {
        $scheduleQueries = app(DashboardScheduleQueriesInterface::class);

        $today = now()->toDateString();
        $dayOfWeek = now()->dayOfWeekIso;
        $employees = Cache::remember('active_employees_with_team', 300, function () {
            return Employee::where('is_active', true)
                ->whereHas('user')
                ->whereHas('team')
                ->get();
        });

        $employeeIds = $employees->pluck('id')->toArray();
        $reminderMinutes = $rule->threshold_seconds ? (int) ($rule->threshold_seconds / 60) : 30;
        $assignments = $scheduleQueries->getScheduledAssignmentsWithSchedule($employeeIds, $today, $dayOfWeek);

        foreach ($employees as $employee) {
            $assignment = $assignments->firstWhere('employee_id', $employee->id);

            if (! $assignment || ! $assignment->start_time) {
                continue;
            }

            $shiftStart = $assignment->start_time;
            $reminderTime = $shiftStart->copy()->subMinutes($reminderMinutes);

            if (now()->between($reminderTime->subMinute(), $reminderTime->addMinute())) {
                if ($this->shouldSuppress($rule, (string) $employee->id, 0)) {
                    continue;
                }

                $this->trigger($rule, [
                    'employee_id' => $employee->id,
                    'message' => "Tu turno inicia en {$reminderMinutes} minutos ({$assignment->start_time}).",
                    'level' => 'info',
                    'source' => 'upcoming_shift_reminder_evaluator',
                    'summary' => 'Recordatorio de inicio de turno próximo.',
                    'icon' => 'clock',
                    'actionUrl' => '/schedules/my-schedule',
                    'facts' => [
                        ['label' => 'Empleado', 'value' => $employee->full_name],
                        ['label' => 'Inicio de Turno', 'value' => $assignment->start_time],
                        ['label' => 'Recordatorio', 'value' => "{$reminderMinutes} min antes"],
                    ],
                    'recommendation' => 'Prepárate para iniciar tu jornada laboral a tiempo.',
                    'context' => [
                        'employee_id' => $employee->id,
                        'scheduled_start' => $assignment->start_time,
                        'reminder_minutes' => $reminderMinutes,
                    ],
                ]);
            }
        }
    }
}
