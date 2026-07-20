<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions\Realtime;

use App\Modules\WfmModule\Models\IntradayActivity;
use App\Shared\Contracts\Schedules\ScheduleServiceInterface;
use App\Shared\Contracts\WfmModule\ExpectedAgentStateInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Acción para determinar el estado esperado de un agente en tiempo real.
 * DESACOPLADO: Utiliza ScheduleServiceInterface para obtener la programación.
 */
final class GetExpectedAgentStateAction implements ExpectedAgentStateInterface
{
    public function __construct(
        private readonly ScheduleServiceInterface $scheduleService
    ) {}

    public function execute(int $employeeId, ?CarbonInterface $now = null): array
    {
        $now = $now ?? Carbon::now();
        $schedule = $this->scheduleService->getScheduleForEmployee($employeeId, $now);

        // 1. Validar Excepciones (Total o Parcial)
        foreach ($schedule->exceptions as $exception) {
            $isMatch = false;

            if ($exception['is_full_day']) {
                $isMatch = true;
            } elseif (isset($exception['start_at'], $exception['end_at'])) {
                $start = Carbon::parse($exception['start_at']);
                $end = Carbon::parse($exception['end_at']);
                if ($now->between($start, $end)) {
                    $isMatch = true;
                }
            }

            if ($isMatch) {
                return [
                    'type' => 'EXCEPTION',
                    'label' => $exception['type'],
                    'is_productive' => false,
                    'color' => $exception['color'] ?? '#ef4444',
                ];
            }
        }

        // 2. Validar Actividades Intradía (Incluye Almuerzos/Descansos si están registrados)
        if (DB::getDriverName() === 'pgsql') {
            $intraday = IntradayActivity::with(['activityType'])
                ->where('employee_id', $employeeId)
                ->whereRaw('time_range @> ?::timestamptz', [$now->toIso8601String()])
                ->first();
        } else {
            // Compatibilidad SQLite para tests
            $intraday = IntradayActivity::with(['activityType'])
                ->where('employee_id', $employeeId)
                ->get()
                ->filter(function ($ia) use ($now) {
                    $start = $ia->getRangeStart();
                    $end = $ia->getRangeEnd();

                    return $start && $end && $now->between($start, $end);
                })
                ->first();
        }

        if ($intraday) {
            return [
                'type' => 'INTRADAY',
                'label' => $intraday->activityType?->name ?? 'Actividad',
                'is_productive' => $intraday->activityType?->is_productive ?? false,
                'color' => $intraday->activityType?->color_hex ?? '#f59e0b',
            ];
        }

        // 3. Validar Almuerzos/Descansos Implícitos en el Horario
        if (! $schedule->is_off) {
            // Almuerzo
            if ($schedule->lunch_start_time && $schedule->lunch_end_time) {
                $lStart = Carbon::parse($schedule->lunch_start_time)->setDate($now->year, $now->month, $now->day);
                $lEnd = Carbon::parse($schedule->lunch_end_time)->setDate($now->year, $now->month, $now->day);
                if ($lEnd->lessThan($lStart)) {
                    $lEnd = $lEnd->addDay();
                }

                if ($now->between($lStart, $lEnd)) {
                    return [
                        'type' => 'INTRADAY',
                        'label' => 'Almuerzo',
                        'is_productive' => false,
                        'color' => '#f59e0b',
                    ];
                }
            }

            // Descanso
            if ($schedule->break_start_time && $schedule->break_end_time) {
                $bStart = Carbon::parse($schedule->break_start_time)->setDate($now->year, $now->month, $now->day);
                $bEnd = Carbon::parse($schedule->break_end_time)->setDate($now->year, $now->month, $now->day);
                if ($bEnd->lessThan($bStart)) {
                    $bEnd = $bEnd->addDay();
                }

                if ($now->between($bStart, $bEnd)) {
                    return [
                        'type' => 'INTRADAY',
                        'label' => 'Descanso',
                        'is_productive' => false,
                        'color' => '#f59e0b',
                    ];
                }
            }
        }

        // 4. Validar Jornada Base (Disponible)
        if (! $schedule->is_off && $schedule->start_time && $schedule->end_time) {
            $start = Carbon::parse($schedule->start_time)->setDate($now->year, $now->month, $now->day);
            $end = Carbon::parse($schedule->end_time)->setDate($now->year, $now->month, $now->day);
            if ($end->lessThan($start)) {
                $end = $end->addDay();
            }

            if ($now->between($start, $end)) {
                return [
                    'type' => 'SHIFT',
                    'label' => 'Disponible',
                    'is_productive' => true,
                    'color' => '#10b981',
                    'start_time' => $start,
                    'end_time' => $end,
                ];
            }
        }

        return [
            'type' => 'OFF',
            'label' => 'Fuera de Jornada',
            'is_productive' => false,
            'color' => '#6b7280',
        ];
    }

    public function executeBatch(array $employeeIds, ?CarbonInterface $now = null): array
    {
        if (empty($employeeIds)) {
            return [];
        }
        $now = $now ?? Carbon::now();

        $schedules = $this->scheduleService->getBatchSchedules($employeeIds, $now);
        if (DB::getDriverName() === 'pgsql') {
            $intradays = IntradayActivity::with(['activityType'])
                ->whereIn('employee_id', $employeeIds)
                ->whereRaw('time_range @> ?::timestamptz', [$now->toIso8601String()])
                ->get()
                ->keyBy('employee_id');
        } else {
            // Compatibilidad SQLite para tests
            $intradays = IntradayActivity::with(['activityType'])
                ->whereIn('employee_id', $employeeIds)
                ->get()
                ->filter(function ($ia) use ($now) {
                    $start = $ia->getRangeStart();
                    $end = $ia->getRangeEnd();

                    return $start && $end && $now->between($start, $end);
                })
                ->keyBy('employee_id');
        }

        $results = [];
        foreach ($employeeIds as $id) {
            $schedule = $schedules[$id] ?? null;
            $intraday = $intradays[$id] ?? null;

            // 1. Excepciones
            $exceptionMatch = null;
            if ($schedule && ! empty($schedule->exceptions)) {
                foreach ($schedule->exceptions as $exc) {
                    if ($exc['is_full_day']) {
                        $exceptionMatch = $exc;
                        break;
                    } elseif (isset($exc['start_at'], $exc['end_at'])) {
                        $start = Carbon::parse($exc['start_at']);
                        $end = Carbon::parse($exc['end_at']);
                        if ($now->between($start, $end)) {
                            $exceptionMatch = $exc;
                            break;
                        }
                    }
                }
            }

            if ($exceptionMatch) {
                $results[$id] = [
                    'type' => 'EXCEPTION',
                    'label' => $exceptionMatch['type'],
                    'is_productive' => false,
                    'color' => $exceptionMatch['color'] ?? '#ef4444',
                ];

                continue;
            }

            // 2. Intraday
            if ($intraday) {
                $results[$id] = [
                    'type' => 'INTRADAY',
                    'label' => $intraday->activityType?->name ?? 'Actividad',
                    'is_productive' => $intraday->activityType?->is_productive ?? false,
                    'color' => $intraday->activityType?->color_hex ?? '#f59e0b',
                ];

                continue;
            }

            // 3. Implied Lunch/Break
            if ($schedule && ! $schedule->is_off) {
                // Almuerzo
                if ($schedule->lunch_start_time && $schedule->lunch_end_time) {
                    $lStart = Carbon::parse($schedule->lunch_start_time)->setDate($now->year, $now->month, $now->day);
                    $lEnd = Carbon::parse($schedule->lunch_end_time)->setDate($now->year, $now->month, $now->day);
                    if ($lEnd->lessThan($lStart)) {
                        $lEnd = $lEnd->addDay();
                    }
                    if ($now->between($lStart, $lEnd)) {
                        $results[$id] = [
                            'type' => 'INTRADAY',
                            'label' => 'Almuerzo',
                            'is_productive' => false,
                            'color' => '#f59e0b',
                        ];

                        continue;
                    }
                }
                // Descanso
                if ($schedule->break_start_time && $schedule->break_end_time) {
                    $bStart = Carbon::parse($schedule->break_start_time)->setDate($now->year, $now->month, $now->day);
                    $bEnd = Carbon::parse($schedule->break_end_time)->setDate($now->year, $now->month, $now->day);
                    if ($bEnd->lessThan($bStart)) {
                        $bEnd = $bEnd->addDay();
                    }
                    if ($now->between($bStart, $bEnd)) {
                        $results[$id] = [
                            'type' => 'INTRADAY',
                            'label' => 'Descanso',
                            'is_productive' => false,
                            'color' => '#f59e0b',
                        ];

                        continue;
                    }
                }
            }

            // 4. Shift
            if ($schedule && ! $schedule->is_off && $schedule->start_time) {
                $start = Carbon::parse($schedule->start_time)->setDate($now->year, $now->month, $now->day);
                $end = Carbon::parse($schedule->end_time)->setDate($now->year, $now->month, $now->day);
                if ($end->lessThan($start)) {
                    $end = $end->addDay();
                }

                if ($now->between($start, $end)) {
                    $results[$id] = [
                        'type' => 'SHIFT',
                        'label' => 'Disponible',
                        'is_productive' => true,
                        'color' => '#10b981',
                    ];

                    continue;
                }
            }

            $results[$id] = [
                'type' => 'OFF',
                'label' => 'Fuera de Jornada',
                'is_productive' => false,
                'color' => '#6b7280',
            ];
        }

        return $results;
    }
}
