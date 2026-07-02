<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Console;

use App\Src\Connect\Application\DTOs\AgentSnapshotFilterDTO;
use App\Src\Connect\Application\Handlers\FetchCiscoAgentSnapshotHandler;
use App\Src\Connect\Application\Handlers\SyncFinesseUsersHandler;
use Illuminate\Console\Command;

final class FinesseSyncCommand extends Command
{
    protected $signature = 'connect:finesse:sync';
    protected $description = 'Sincroniza usuarios y estados desde Cisco Finesse';

    public function __construct(
        private readonly SyncFinesseUsersHandler $finesseHandler,
        private readonly FetchCiscoAgentSnapshotHandler $snapshotHandler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Sincronizando usuarios Finesse...');

        try {
            $users = $this->getUsersFromFinesse();
            $synced = $this->finesseHandler->handle($users);
            $this->info("Usuarios sincronizados: {$synced}");

            $employeeIds = array_map(fn ($u) => $u['employee_id'] ?? $u['userId'] ?? 0, $users);
            $employeeIds = array_filter($employeeIds, fn ($id) => $id > 0);

            if (! empty($employeeIds)) {
                $dto = new AgentSnapshotFilterDTO(
                    employeeIds: $employeeIds,
                );

                $snapshots = $this->snapshotHandler->handle($dto);
                $this->info("Snapshots de agentes: " . count($snapshots));
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Finesse sync failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function getUsersFromFinesse(): array
    {
        // TODO: Implementar obtención de usuarios desde Finesse API.
        // Por ahora retorna un array vacío para que se implemente después.
        $this->warn('La obtención de usuarios Finesse aún no está implementada.');
        return [];
    }
}
