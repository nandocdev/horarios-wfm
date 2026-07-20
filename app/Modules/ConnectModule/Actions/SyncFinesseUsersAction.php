<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use Illuminate\Support\Facades\Log;

/**
 * Sincroniza nombres de agentes desde Cisco Finesse a la tabla local de empleados.
 *
 * Utiliza loginId/loginName de Cisco como llave para buscar el 'username' local.
 * Actualiza first_name y last_name con los valores oficiales de Cisco.
 */
final class SyncFinesseUsersAction
{
    public function __construct(
        private readonly CiscoFinesseClient $finesse
    ) {}

    public function execute(): array
    {
        $finesseUsers = $this->finesse->getUsers();
        $stats = ['updated' => 0, 'skipped' => 0, 'not_found' => 0];

        foreach ($finesseUsers as $userData) {
            $loginId = $userData['loginId'] ?? $userData['loginName'] ?? null;

            if (! $loginId) {
                $stats['skipped']++;

                continue;
            }

            $employee = Employee::where('username', strtolower((string) $loginId))->first();

            if (! $employee) {
                $stats['not_found']++;

                continue;
            }

            $firstName = (string) ($userData['firstName'] ?? '');
            $lastName = (string) ($userData['lastName'] ?? '');

            if (empty($firstName) && empty($lastName)) {
                $stats['skipped']++;

                continue;
            }

            $employee->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);

            $stats['updated']++;
        }

        Log::info('[Finesse-Sync] Finalizado', $stats);

        return $stats;
    }
}
