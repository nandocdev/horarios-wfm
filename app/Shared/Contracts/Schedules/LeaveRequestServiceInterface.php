<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Schedules;

/**
 * Contrato para la gestión del flujo de LeaveRequests (Solicitudes de permisos/vacaciones).
 */
interface LeaveRequestServiceInterface
{
    /**
     * Obtiene el conteo de solicitudes pendientes para los subordinados del usuario.
     */
    public function getPendingCountForUser(int $userId): int;
}
