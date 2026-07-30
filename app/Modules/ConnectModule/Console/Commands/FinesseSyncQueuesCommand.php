<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Console\Commands;

use App\Modules\ConnectModule\Actions\SyncQueuesFromFinesseAction;
use App\Shared\Events\SyncFailed;
use Illuminate\Console\Command;

final class FinesseSyncQueuesCommand extends Command
{
    protected $signature = 'finesse:sync-queues';

    protected $description = 'Sincroniza las colas (CSQ) desde Cisco Finesse API hacia call_queues';

    public function handle(SyncQueuesFromFinesseAction $action): int
    {
        $this->info('=== Sincronizando colas desde Finesse ===');

        try {
            $stats = $action->execute();

            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Colas descubiertas', $stats['discovered']],
                    ['Colas creadas', $stats['created']],
                    ['Colas actualizadas', $stats['updated']],
                    ['Errores', $stats['errors']],
                ]
            );

            $this->info('Sincronización finalizada.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error durante la sincronización: '.$e->getMessage());
            event(new SyncFailed('finesse:sync-queues', $e->getMessage(), 1));

            return self::FAILURE;
        }
    }
}
