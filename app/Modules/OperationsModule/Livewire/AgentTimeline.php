<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use App\Shared\DTOs\TimelineItemDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class AgentTimeline extends Component
{
    public $employeeId;

    public function render()
    {
        $timeline = $this->buildTimeline();
        $barSegments = $this->buildBarSegments($timeline);

        return view('operations::livewire.agent-timeline', [
            'timeline' => $timeline,
            'barSegments' => $barSegments,
        ]);
    }

    public function buildBarSegments(array $timeline): array
    {
        $segments = [];
        // Filtramos solo los estados reales y actividades intradía/turnos para la barra
        // La barra representará de las 05:00 a las 18:00 (horario plataforma + margen)
        $startWindow = Carbon::now()->startOfDay()->addHours(5);
        $endWindow = Carbon::now()->startOfDay()->addHours(18);
        $totalMinutes = $endWindow->diffInMinutes($startWindow);

        foreach ($timeline as $item) {
            if (! $item->endTime) {
                continue;
            }

            $start = Carbon::parse($item->startTime);
            $end = Carbon::parse($item->endTime);

            // Solo mostrar si está dentro de la ventana
            if ($start->gt($endWindow) || $end->lt($startWindow)) {
                continue;
            }

            $clampedStart = $start->lt($startWindow) ? $startWindow : $start;
            $clampedEnd = $end->gt($endWindow) ? $endWindow : $end;

            $left = (($clampedStart->diffInMinutes($startWindow)) / $totalMinutes) * 100;
            $width = (($clampedEnd->diffInMinutes($clampedStart)) / $totalMinutes) * 100;

            if ($width <= 0) {
                continue;
            }

            $color = match ($item->type) {
                'REAL_STATE' => match (strtoupper($item->label)) {
                    'READY' => 'bg-green-400',
                    'TALKING' => 'bg-blue-500',
                    'NOT READY', 'NOT_READY' => 'bg-red-400',
                    'WORK' => 'bg-yellow-300',
                    'RESERVED' => 'bg-purple-500',
                    'LOGGED-IN', 'LOGGED_IN' => 'bg-slate-400',
                    'LOGOUT' => 'bg-zinc-900',
                    default => 'bg-slate-300',
                },
                'LUNCH', 'BREAK' => 'bg-yellow-300',
                'INTRADAY' => 'bg-indigo-400',
                default => 'bg-slate-100',
            };

            $segments[] = [
                'left' => $left,
                'width' => $width,
                'color' => $color,
                'label' => $item->label,
                'time' => $item->displayTime,
            ];
        }

        return $segments;
    }

    public function buildTimeline(): array
    {
        Log::error('BUILDING TIMELINE START for ID: '.$this->employeeId);
        $now = Carbon::now();
        $rawItems = [];
        $dayOfWeek = $now->dayOfWeekIso;
        $weekStart = $now->copy()->startOfWeek();

        // A. Turno Base (Weekly Assignments)
        $assignments = WeeklyScheduleAssignment::where('employee_id', (int) $this->employeeId)
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('weeklySchedule', function ($q) use ($weekStart) {
                $q->whereDate('week_start_date', $weekStart->format('Y-m-d'));
            })
            ->get();

        Log::error('DEBUG: Assignments found: '.$assignments->count());

        foreach ($assignments as $a) {
            // Asegurarnos de tener objetos Carbon. Laravel casts ya debería darlos.
            $startTime = $a->start_time instanceof Carbon ? $a->start_time : Carbon::parse($a->start_time);
            $endTime = $a->end_time instanceof Carbon ? $a->end_time : Carbon::parse($a->end_time);

            Log::error("DEBUG: Processing assignment ID: {$a->id}, Start: ".$startTime->toDateTimeString());

            // Inicio de Jornada
            $rawItems[] = [
                'type' => 'SHIFT_START',
                'label' => 'Inicio de Jornada',
                'start_time' => $startTime->toIso8601String(),
                'display_time' => $startTime->format('H:i'),
                'icon' => 'play',
                'description' => 'Horario programado',
            ];

            // Almuerzo
            if ($a->lunch_start_time) {
                $lStart = $a->lunch_start_time instanceof Carbon ? $a->lunch_start_time : Carbon::parse($a->lunch_start_time);
                $lEnd = $a->lunch_end_time instanceof Carbon ? $a->lunch_end_time : Carbon::parse($a->lunch_end_time);

                $rawItems[] = [
                    'type' => 'LUNCH',
                    'label' => 'Almuerzo',
                    'start_time' => $lStart->toIso8601String(),
                    'end_time' => $lEnd->toIso8601String(),
                    'display_time' => $lStart->format('H:i').' - '.$lEnd->format('H:i'),
                    'icon' => 'clock',
                    'description' => 'Tiempo de comida',
                ];
            }

            // Descanso/Break
            if ($a->break_start_time) {
                $bStart = $a->break_start_time instanceof Carbon ? $a->break_start_time : Carbon::parse($a->break_start_time);
                $bEnd = $a->break_end_time instanceof Carbon ? $a->break_end_time : Carbon::parse($a->break_end_time);

                $rawItems[] = [
                    'type' => 'BREAK',
                    'label' => 'Descanso',
                    'start_time' => $bStart->toIso8601String(),
                    'end_time' => $bEnd->toIso8601String(),
                    'display_time' => $bStart->format('H:i').' - '.$bEnd->format('H:i'),
                    'icon' => 'coffee',
                    'description' => 'Receso breve',
                ];
            }

            // Fin de Jornada
            $rawItems[] = [
                'type' => 'SHIFT_END',
                'label' => 'Fin de Jornada',
                'start_time' => $endTime->toIso8601String(),
                'display_time' => $endTime->format('H:i'),
                'icon' => 'stop',
                'description' => 'Termino de labores',
            ];
        }

        Log::error('DEBUG: Assignments loop finished. Items count: '.count($rawItems));

        // B. Actividades Intradiarias (Meetings, Formación, etc)
        // Usamos whereRaw para filtrar por la fecha dentro del TSTZRANGE
        Log::error('DEBUG: Querying Intraday for ID: '.$this->employeeId);
        if (DB::getDriverName() === 'pgsql') {
            $intradays = IntradayActivity::with(['activityType'])
                ->where('employee_id', $this->employeeId)
                ->whereRaw('lower(time_range)::date = ?', [$now->toDateString()])
                ->get();
        } else {
            // Compatibilidad SQLite para tests
            $intradays = IntradayActivity::with(['activityType'])
                ->where('employee_id', $this->employeeId)
                ->where('time_range', 'like', '%'.$now->toDateString().'%')
                ->get();
        }

        Log::error('DEBUG: Intraday found: '.$intradays->count());

        foreach ($intradays as $ia) {
            $start = $ia->getRangeStart();
            $end = $ia->getRangeEnd();

            $rawItems[] = [
                'type' => 'INTRADAY',
                'label' => $ia->activityType->name ?? 'Actividad Especial',
                'start_time' => $start->toIso8601String(),
                'end_time' => $end?->toIso8601String(),
                'display_time' => $start->format('H:i').($end ? ' - '.$end->format('H:i') : ''),
                'icon' => 'briefcase',
                'description' => 'Asignación manual del día',
            ];
        }

        // C. Transiciones Reales (CUIC Snapshot)
        $realtimeRepo = app(TelemetryRealtimeRepositoryInterface::class);
        $transitions = $realtimeRepo->getBatchStateTransitions([(int) $this->employeeId], $now->toDateString())
            ->sortBy('transition_time');

        Log::error('DEBUG: Transitions found: '.$transitions->count());

        for ($i = 0; $i < count($transitions); $i++) {
            $t = $transitions[$i];
            $startTime = Carbon::parse($t->transition_time);

            // Calculamos el end_time basado en la siguiente transición o el "ahora" si es la última
            $nextT = isset($transitions[$i + 1]) ? $transitions[$i + 1] : null;
            $endTime = $nextT ? Carbon::parse($nextT->transition_time) : ($startTime->isToday() ? now() : $startTime->copy()->endOfDay());

            $durationLabel = null;
            if ($t->duration) {
                $minutes = floor($t->duration / 60);
                $seconds = $t->duration % 60;
                $durationLabel = $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
            }

            $rawItems[] = [
                'type' => 'REAL_STATE',
                'label' => strtoupper((string) $t->agent_state),
                'start_time' => $startTime->toIso8601String(),
                'end_time' => $endTime->toIso8601String(),
                'display_time' => $startTime->format('H:i'),
                'icon' => 'arrow-right-circle',
                'is_real' => true,
                'description' => $t->reason_code
                    ? "Motivo: {$t->reason_code}".($durationLabel ? " • Duración: {$durationLabel}" : '')
                    : ($durationLabel ? "Duración: {$durationLabel}" : 'En curso...'),
            ];
        }

        // D. Ordenar y Aplanar
        // Orden cronológico inverso (Lo más reciente arriba)
        usort($rawItems, fn ($a, $b) => strcmp($b['start_time'], $a['start_time']));

        return array_map(function ($item) {
            // Asegurar que TimelineItemDTO no falle por campos faltantes
            $data = array_merge([
                'icon' => 'clock',
                'is_real' => false,
                'description' => null,
            ], $item);

            $dto = TimelineItemDTO::fromArray($data);

            // Mapeo semántico para badges de FluxUI
            $semanticColor = match ($dto->type) {
                'REAL_STATE' => match (strtoupper($dto->label)) {
                    'READY' => 'green',
                    'TALKING' => 'blue',
                    'NOT READY', 'NOT_READY' => 'red',
                    'WORK' => 'yellow',
                    'RESERVED' => 'purple',
                    'LOGGED-IN', 'LOGGED_IN' => 'slate',
                    'LOGOUT' => 'zinc',
                    default => 'slate',
                },
                'LUNCH', 'BREAK' => 'yellow',
                'INTRADAY' => 'indigo',
                'SHIFT_START' => 'emerald',
                'SHIFT_END' => 'zinc',
                default => 'slate',
            };

            return (object) [
                'id' => $dto->id,
                'type' => $dto->type,
                'label' => $dto->label,
                'startTime' => $dto->startTime,
                'displayTime' => $dto->displayTime,
                'icon' => $dto->icon,
                'color' => $semanticColor, // Usamos el color semántico aquí
                'endTime' => $dto->endTime,
                'description' => $dto->description,
                'isReal' => $dto->isReal,
                'isPast' => $dto->isPast,
                'isCurrent' => $dto->isCurrent,
                'isFuture' => $dto->isFuture,
            ];
        }, $rawItems);
    }
}
