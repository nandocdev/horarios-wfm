<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\DTOs\IntradayActivityDTO;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\ScheduledActivityDefinition;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Acción para asignar actividades intradía a uno o más empleados.
 * Valida traslapes y encapsula la lógica de negocio.
 */
class AssignIntradayActivityAction
{
    /**
     * Ejecuta la asignación de la actividad.
     *
     * @param  IntradayActivityDTO  $dto
     * @return array Lista de actividades creadas.
     * @throws ValidationException Si hay traslapes detectados.
     */
    public function execute(IntradayActivityDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $definition = ScheduledActivityDefinition::findOrFail($dto->activity_definition_id);
            
            $startRange = Carbon::parse($dto->date . ' ' . $dto->start_time)->toIso8601String();
            $endRange = Carbon::parse($dto->date . ' ' . $dto->end_time)->toIso8601String();
            $tstzRange = "[$startRange, $endRange)";

            $createdActivities = [];
            $errors = [];

            foreach ($dto->employee_ids as $employeeId) {
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

                $activity = IntradayActivity::create([
                    'employee_id' => $employeeId,
                    'activity_type_id' => $definition->activity_type_id,
                    'time_range' => $tstzRange,
                    'notes' => $dto->notes,
                ]);

                $createdActivities[] = $activity;

                // TODO: Disparar evento de dominio para notificaciones
                // event(new IntradayActivityAssigned($activity));
            }

            if (empty($createdActivities) && !empty($errors)) {
                throw ValidationException::withMessages(['selectedEmployeeIds' => $errors[0]]);
            }

            return $createdActivities;
        });
    }
}
