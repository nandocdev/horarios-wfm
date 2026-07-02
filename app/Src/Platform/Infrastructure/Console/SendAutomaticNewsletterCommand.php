<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Console;

use App\Src\Platform\Application\Handlers\SendAutomaticNewsletterHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class SendAutomaticNewsletterCommand extends Command {
    protected $signature = 'platform:communications:newsletter
                            {--dry-run : Solo mostrar cuántas notificaciones se enviarían sin ejecutar}';

    protected $description = 'Envía un newsletter automático con las noticias publicadas hoy a todos los usuarios activos';

    public function handle(SendAutomaticNewsletterHandler $handler): int {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY-RUN] No se realizarán cambios. Simulando envío de newsletter...');

            return self::SUCCESS;
        }

        $this->info('Enviando newsletter automático...');

        $result = $handler->execute();

        $this->info("Noticias incluidas: {$result['news']}");
        $this->info("Notificaciones enviadas: {$result['notifications']}");

        Log::channel('audit')->info('SendAutomaticNewsletter completed', $result);

        return self::SUCCESS;
    }
}
