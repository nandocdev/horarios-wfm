<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Console;

use App\Src\Connect\Application\Handlers\SyncCsqRealtimeStatsHandler;
use Illuminate\Console\Command;

final class CuicRealtimeSyncCommand extends Command
{
    protected $signature = 'connect:cuic:realtime-sync {--loop} {--interval=300}';
    protected $description = 'Sincroniza estadísticas en tiempo real de CUIC (CSQ y estados de agentes)';

    public function __construct(
        private readonly SyncCsqRealtimeStatsHandler $handler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $loop = (bool) $this->option('loop');
        $interval = (int) $this->option('interval');

        do {
            try {
                $count = $this->handler->handle();
                $this->info("CSQ realtime stats synced: {$count} registros.");
            } catch (\Throwable $e) {
                $this->error("Error en sync: {$e->getMessage()}");
            }

            if (! $loop) {
                break;
            }

            $this->info("Esperando {$interval} segundos...");
            sleep($interval);
        } while ($loop);

        return self::SUCCESS;
    }
}
