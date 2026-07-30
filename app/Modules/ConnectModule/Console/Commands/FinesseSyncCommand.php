<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Console\Commands;

use App\Modules\ConnectModule\Actions\SyncFinesseUsersAction;
use App\Shared\Events\SyncFailed;
use Illuminate\Console\Command;

final class FinesseSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finesse:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza nombres de agentes desde Cisco Finesse a la tabla local de empleados';

    /**
     * Execute the console command.
     */
    public function handle(SyncFinesseUsersAction $action): int
    {
        $this->info('=== Iniciando Sincronización de Identidad Finesse ===');

        try {
            $stats = $action->execute();

            $this->table(['Categoría', 'Cantidad'], [
                ['Actualizados', $stats['updated']],
                ['No encontrados en DB local', $stats['not_found']],
                ['Omitidos (sin datos)', $stats['skipped']],
            ]);

            $this->info('Sincronización finalizada exitosamente.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error durante la sincronización: '.$e->getMessage());
            event(new SyncFailed('finesse:sync', $e->getMessage(), 1));

            return self::FAILURE;
        }
    }
}
