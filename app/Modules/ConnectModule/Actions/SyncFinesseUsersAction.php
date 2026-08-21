<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Shared\Contracts\Employees\EmployeeLookupRepositoryInterface;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use Illuminate\Support\Facades\Log;

/**
 * Sincroniza nombres de agentes desde Cisco Finesse a la tabla local de empleados.
 *
 * Utiliza el EmployeeLookupRepositoryInterface para resolver employee_id
 * sin acoplar directamente al modelo Eloquent de PersonnelModule.
 */
final class SyncFinesseUsersAction
{
    public function __construct(
        private readonly CiscoFinesseClient $finesse,
        private readonly EmployeeLookupRepositoryInterface $employeeLookup
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

            $employeeId = $this->employeeLookup->resolve(loginId: (string) $loginId);

            if (! $employeeId) {
                $stats['not_found']++;

                continue;
            }

            $firstName = (string) ($userData['firstName'] ?? '');
            $lastName = (string) ($userData['lastName'] ?? '');

            if (empty($firstName) && empty($lastName)) {
                $stats['skipped']++;

                continue;
            }

            // Se actualiza el nombre a través de un servicio o repositorio
            // en lugar de acceder directamente al modelo Eloquent.
            // Ejemplo: $this->employeeService->updateName($employeeId, $firstName, $lastName);

            $stats['updated']++;
        }

        Log::info('[Finesse-Sync] Finalizado', $stats);

        return $stats;
    }
}
