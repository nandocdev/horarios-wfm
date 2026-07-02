<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Console;

use App\Src\Platform\Application\Handlers\AutoArchiveContentHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class AutoArchiveContentCommand extends Command {
    protected $signature = 'platform:communications:archive
                            {--dry-run : Solo mostrar cuántos contenidos se archivarían sin ejecutar}';

    protected $description = 'Archiva automáticamente noticias, encuestas y shoutouts cuya fecha de archive_at haya vencido';

    public function handle(AutoArchiveContentHandler $handler): int {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY-RUN] No se realizarán cambios. Simulando archivado automático de contenido...');

            return self::SUCCESS;
        }

        $this->info('Archivando contenido expirado...');

        $result = $handler->execute();

        $this->info("Noticias archivadas: {$result['news']}");
        $this->info("Encuestas archivadas: {$result['polls']}");
        $this->info("Shoutouts archivados: {$result['shoutouts']}");

        $total = array_sum($result);
        $this->info("Total de elementos archivados: {$total}");

        Log::channel('audit')->info('AutoArchiveContent completed', $result);

        return self::SUCCESS;
    }
}
