<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Console;

use App\Src\Platform\Application\Handlers\PublishScheduledContentHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class PublishScheduledContentCommand extends Command {
    protected $signature = 'platform:communications:publish
                            {--dry-run : Solo mostrar cuántos contenidos se publicarían sin ejecutar}';

    protected $description = 'Publica contenido programado (noticias, etc.) cuya fecha de scheduled_at ya haya llegado';

    public function handle(PublishScheduledContentHandler $handler): int {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY-RUN] No se realizarán cambios. Simulando publicación de contenido programado...');

            return self::SUCCESS;
        }

        $this->info('Publicando contenido programado...');

        $result = $handler->execute();

        $this->info("Noticias publicadas: {$result['news']}");

        Log::channel('audit')->info('PublishScheduledContent completed', $result);

        return self::SUCCESS;
    }
}
