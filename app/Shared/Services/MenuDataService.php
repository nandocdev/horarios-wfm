<?php

declare(strict_types=1);

namespace App\Shared\Services;

use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\ShiftSwapRequest;

class MenuDataService {
    /**
     * Centraliza las consultas necesarias para los badges del menú.
     *
     * @return array<string, int>
     */
    public function getCounts(?User $user): array {
        if (!$user) {
            return $this->empty();
        }

        // Si es manager o admin, cuenta las que están pendientes para que él apruebe
        // Si no es un servicio muy complejo, haremos count global de 'pending' a las que tiene acceso.
        // Pero para no saturar, podemos contar las solicitudes donde el usuario es el supervisor.

        // De acuerdo a las reglas de negocio, podemos intentar contar las que él debería revisar.
        // Para simplificar, obtenemos los ids si es supervisor.
        $managedIds = [];
        if ($user->employee) {
            $managedIds = $user->employee->getAllSubordinateIds();
        }

        $pendingLeaves = 0;
        $pendingSwaps = 0;

        if (!empty($managedIds)) {
            $pendingLeaves = LeaveRequest::whereIn('employee_id', $managedIds)
                ->where('status', 'pending')
                ->count();

            $pendingSwaps = ShiftSwapRequest::whereIn('requester_id', $managedIds)
                ->where('status', 'pending')
                ->count();
        }

        // Si es superadmin o tiene permiso total, quizás queramos mostrar todas,
        // pero por performance, lo dejaremos en las que gestiona, o 0.
        if ($user->can('wfm.leaves.manage') && empty($managedIds)) {
            $pendingLeaves = LeaveRequest::where('status', 'pending')->count();
            $pendingSwaps = ShiftSwapRequest::where('status', 'pending')->count();
        }

        return [
            'pending_leaves' => $pendingLeaves,
            'pending_swaps' => $pendingSwaps,
        ];
    }

    private function empty(): array {
        return [
            'pending_leaves' => 0,
            'pending_swaps' => 0,
        ];
    }
}
