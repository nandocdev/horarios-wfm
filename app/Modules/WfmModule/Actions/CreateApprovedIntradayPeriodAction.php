<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\Models\ApprovedIntradayPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Acción para que WFM cree un periodo de tiempo aprobado de actividad intradía para un equipo.
 * El coordinador del equipo podrá asignar a sus operadores dentro de este bloque.
 *
 * [RIESGOS]
 * - Periodos solapados para el mismo equipo y misma fecha no se validan aquí (se permite
 *   múltiples periodos el mismo día, de distintas actividades). Si se requiere exclusividad,
 *   agregar una constraint unique en la migración.
 * - max_slots=0 generaría un periodo sin utilidad; la validación del DTO debe prevenirlo.
 */
class CreateApprovedIntradayPeriodAction
{
    /**
     * Crea el periodo aprobado para un equipo.
     *
     * @param  array{team_id: int, activity_definition_id: int, date: string, start_time: string, end_time: string, max_slots: int, notes: ?string}  $data
     *
     * @throws ValidationException Si los datos no son válidos.
     */
    public function execute(array $data): ApprovedIntradayPeriod
    {
        return DB::transaction(function () use ($data) {
            $date = Carbon::parse($data['date']);
            $start = Carbon::parse($data['date'].' '.$data['start_time']);
            $end = Carbon::parse($data['date'].' '.$data['end_time']);

            if ($end->lte($start)) {
                throw ValidationException::withMessages([
                    'end_time' => ['La hora de fin debe ser posterior a la de inicio.'],
                ]);
            }

            $maxSlots = (int) ($data['max_slots'] ?? 1);
            if ($maxSlots < 1) {
                throw ValidationException::withMessages([
                    'max_slots' => ['El número de slots debe ser al menos 1.'],
                ]);
            }

            return ApprovedIntradayPeriod::create([
                'team_id' => (int) $data['team_id'],
                'activity_definition_id' => (int) $data['activity_definition_id'],
                'date' => $date->toDateString(),
                'start_time' => $start->format('H:i'),
                'end_time' => $end->format('H:i'),
                'max_slots' => $maxSlots,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }
}
