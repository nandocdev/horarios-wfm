<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Console;

use App\Src\Platform\Domain\Repositories\AuditLogRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class AuditPruneCommand extends Command {
    protected $signature = 'platform:audit:prune
                            {--days=90 : Días de retención de logs (anteriores a esto se eliminan)}
                            {--chunk=500 : Tamaño de lote para eliminación}
                            {--dry-run : Solo mostrar cuántos registros se eliminarían}';

    protected $description = 'Elimina registros de auditoría antiguos según la política de retención';

    public function handle(AuditLogRepositoryInterface $repository): int {
        $days = (int) $this->option('days');
        $chunk = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');

        $cutoff = now()->subDays($days);
        $cutoffImmutable = \DateTimeImmutable::createFromMutable($cutoff);

        $count = $repository->countOlderThan($cutoffImmutable);

        if ($count === 0) {
            $this->info("No hay registros de auditoría anteriores a {$cutoff->toDateString()}.");

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("[DRY-RUN] Se eliminarían {$count} registros anteriores a {$cutoff->toDateString()}.");

            return self::SUCCESS;
        }

        $this->warn("Eliminando {$count} registros anteriores a {$cutoff->toDateString()}...");

        $deleted = $repository->pruneOlderThan($cutoffImmutable, $chunk);

        $this->info("Eliminados {$deleted} registros de auditoría anteriores a {$cutoff->toDateString()}.");

        Log::channel('audit')->info('AuditLog prune completed', [
            'deleted' => $deleted,
            'cutoff' => $cutoff->toDateString(),
            'days_retained' => $days,
        ]);

        return self::SUCCESS;
    }
}
