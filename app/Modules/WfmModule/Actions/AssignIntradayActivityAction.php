<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\DTOs\IntradayActivityDTO;
use App\Modules\WfmModule\Models\ApprovedIntradayPeriod;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\ScheduledActivityDefinition;
use App\Modules\WfmModule\Notifications\IntradayActivityNotification;
use App\Shared\Contracts\Employees\EmployeeRepositoryInterface;
use App\Shared\DTOs\NotificationDTO;
use App\Shared\Enums\NotificationType;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Acción para asignar actividades intradía a uno o más empleados.
 * Cuando se provee un approved_period_id, valida:
 *  - Que el periodo exista.
 *  - Que el rango de tiempo esté dentro del periodo aprobado.
 *  - Que el empleado pertenezca al equipo del periodo aprobado.
 *  - Que no se exceda la capacidad de slots (max_slots) del periodo.
 *
 * [RIESGOS]
 * - Race condition al consumir slots → mitigado con lockForUpdate() sobre el periodo aprobado.
 * - Traslapes en Postgres → validación nativa con tstzrange &&.
 * - Empleados de distintos equipos mezclados en la misma llamada → cada empleado se valida
 *   individualmente; los que fallen son omitidos del resultado.
 */
class AssignIntradayActivityAction
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepo,
    ) {}

    /**
     * Ejecuta la asignación de la actividad.
     *
     * @return array Lista de actividades creadas.
     *
     * @throws ValidationException Si hay traslapes o violaciones de capacidad.
     */
    public function execute(IntradayActivityDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $definition = ScheduledActivityDefinition::findOrFail($dto->activity_definition_id);

            $startRange = Carbon::parse($dto->date.' '.$dto->start_time)->toIso8601String();
            $endRange = Carbon::parse($dto->date.' '.$dto->end_time)->toIso8601String();
            $tstzRange = "[$startRange, $endRange)";

            // Validación del periodo aprobado (si se provee)
            $approvedPeriod = null;
            if ($dto->approved_period_id !== null) {
                // Bloqueo pesimista para evitar race conditions en max_slots
                $approvedPeriod = ApprovedIntradayPeriod::lockForUpdate()
                    ->findOrFail($dto->approved_period_id);

                // Validar que el rango solicitado esté dentro del periodo aprobado
                $periodStart = Carbon::parse($approvedPeriod->date->toDateString().' '.$approvedPeriod->start_time);
                $periodEnd = Carbon::parse($approvedPeriod->date->toDateString().' '.$approvedPeriod->end_time);
                $reqStart = Carbon::parse($dto->date.' '.$dto->start_time);
                $reqEnd = Carbon::parse($dto->date.' '.$dto->end_time);

                if ($reqStart->lt($periodStart) || $reqEnd->gt($periodEnd)) {
                    throw ValidationException::withMessages([
                        'startTime' => ["El horario solicitado ({$reqStart->format('H:i')}–{$reqEnd->format('H:i')}) "
                            ."está fuera del periodo aprobado ({$periodStart->format('H:i')}–{$periodEnd->format('H:i')})."],
                    ]);
                }

                // Validar que haya slots disponibles para todos los empleados a asignar
                $employeeCount = count($dto->employee_ids);
                $usedSlots = $approvedPeriod->assignments()->count();
                $remaining = $approvedPeriod->max_slots - $usedSlots;

                if ($employeeCount > $remaining) {
                    throw ValidationException::withMessages([
                        'selectedEmployeeIds' => ["El periodo aprobado solo tiene {$remaining} slot(s) disponible(s). "
                            ."Se intentaron asignar {$employeeCount}."],
                    ]);
                }
            }

            $createdActivities = [];
            $errors = [];

            foreach ($dto->employee_ids as $employeeId) {
                // Validar que el empleado pertenezca al equipo del periodo aprobado
                if ($approvedPeriod !== null) {
                    $employee = $this->employeeRepo->find($employeeId);
                    if (! $employee || (int) $employee->getTeamId() !== (int) $approvedPeriod->team_id) {
                        $errors[] = "El empleado ID {$employeeId} no pertenece al equipo del periodo aprobado.";

                        continue;
                    }
                }

                // Validación de traslapes (Solo Postgres soporta tstzrange nativo)
                if (DB::getDriverName() === 'pgsql') {
                    $hasOverlap = IntradayActivity::where('employee_id', $employeeId)
                        ->whereRaw('time_range && tstzrange(?, ?)', [$startRange, $endRange])
                        ->exists();

                    if ($hasOverlap) {
                        $errors[] = "El empleado ID {$employeeId} ya tiene una actividad programada en este horario.";

                        continue;
                    }
                }

                try {
                    $activity = IntradayActivity::create([
                        'employee_id' => $employeeId,
                        'activity_type_id' => $definition->activity_type_id,
                        'approved_period_id' => $approvedPeriod?->id,
                        'time_range' => $tstzRange,
                        'notes' => $dto->notes,
                    ]);
                } catch (QueryException $e) {
                    if ($e->getCode() === '23P01') {
                        throw ValidationException::withMessages([
                            'startTime' => ["El empleado ID {$employeeId} ya tiene una actividad programada en este horario (conflicto detectado a nivel de base de datos)."],
                        ]);
                    }

                    throw $e;
                }

                // Notificar al empleado
                $employeeNotify = $this->employeeRepo->find($employeeId);
                $userNotify = $employeeNotify ? User::find($employeeNotify->getUserId()) : null;
                if ($userNotify) {
                    $startTime = Carbon::parse($dto->date.' '.$dto->start_time)->format('H:i');
                    $endTime = Carbon::parse($dto->date.' '.$dto->end_time)->format('H:i');

                    $dtoNotify = new NotificationDTO(
                        title: 'Nueva actividad asignada',
                        message: "Se te ha asignado la actividad '{$definition->name}' para hoy de {$startTime} a {$endTime}.",
                        summary: 'Se agregó una actividad a tu jornada.',
                        actionUrl: route('schedules.my-schedule'),
                        icon: 'clock',
                        level: 'info',
                        notificationType: NotificationType::IntradayActivity->value,
                        facts: [
                            ['label' => 'Actividad', 'value' => $definition->name],
                            ['label' => 'Horario', 'value' => "{$startTime}–{$endTime}"],
                        ],
                        recommendation: 'Esta actividad forma parte de tu planificación diaria.',
                        resourceType: 'intraday_activity',
                        resourceId: (string) ($activity->id ?? ''),
                    );
                    $userNotify->notify(new IntradayActivityNotification($dtoNotify));
                }

                $createdActivities[] = $activity;
            }

            if (empty($createdActivities) && ! empty($errors)) {
                throw ValidationException::withMessages(['selectedEmployeeIds' => $errors[0]]);
            }

            return $createdActivities;
        });
    }
}
