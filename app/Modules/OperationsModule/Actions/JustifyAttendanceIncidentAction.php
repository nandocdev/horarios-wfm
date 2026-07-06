<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Actions;

use App\Modules\OperationsModule\Models\AttendanceIncident;
use Illuminate\Support\Facades\DB;

class JustifyAttendanceIncidentAction
{
    /**
     * Justifica una incidencia de asistencia registrando un comentario administrativo.
     * Envuelto en transacción para asegurar consistencia e integridad de datos.
     */
    public function execute(string $id, string $comment = 'Justificado desde Dashboard'): AttendanceIncident
    {
        return DB::transaction(function () use ($id, $comment) {
            $incident = AttendanceIncident::findOrFail($id);
            
            $incident->update([
                'admin_comment' => $comment,
            ]);

            return $incident;
        });
    }
}
