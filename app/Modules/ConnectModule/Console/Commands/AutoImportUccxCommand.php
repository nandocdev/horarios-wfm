<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Console\Commands;

use App\Modules\ConnectModule\Actions\ImportUccxChatAction;
use App\Modules\ConnectModule\Actions\ImportUccxInboundAction;
use App\Modules\ConnectModule\Actions\ImportUccxPerformanceAction;
use App\Modules\ConnectModule\Actions\ImportUccxTransitionsAction;
use App\Modules\ConnectModule\Emails\ImportErrorNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AutoImportUccxCommand extends Command
{
    protected $signature = 'uccx:auto-import';

    protected $description = 'Barrido automático e importación de archivos Cisco UCCX con notificaciones de error';

    public function handle(
        ImportUccxInboundAction $inboundAction,
        ImportUccxTransitionsAction $transitionsAction,
        ImportUccxPerformanceAction $performanceAction,
        ImportUccxChatAction $chatAction
    ): int {
        $basePath = env('UCCX_DATA_PATH', '/srv/data/');

        $types = [
            'inbound' => $inboundAction,
            'not_ready' => $transitionsAction,
            'aht' => $performanceAction,
            'chat' => $chatAction,
        ];

        $this->info('Iniciando barrido de directorios UCCX...');

        foreach ($types as $dirName => $action) {
            $sourceDir = rtrim($basePath, '/').'/'.$dirName;

            if (! File::exists($sourceDir)) {
                $this->warn("Directorio no encontrado: {$sourceDir}");

                continue;
            }

            $files = File::files($sourceDir);
            $csvFiles = array_filter($files, fn ($file) => $file->getExtension() === 'csv');

            if (empty($csvFiles)) {
                continue;
            }

            $this->info("Procesando canal: {$dirName} (".count($csvFiles).' archivos)');

            foreach ($csvFiles as $file) {
                $filePath = $file->getPathname();
                $fileName = $file->getFilename();

                // Evitar procesar archivos que se están escribiendo (menos de 30 segundos de vida)
                if (time() - $file->getMTime() < 30) {
                    $this->comment("Saltando {$fileName} (archivo muy reciente, posible escritura en curso)");

                    continue;
                }

                try {
                    $this->comment("Importando {$fileName}...");
                    $count = $action->execute($filePath);

                    $this->info("Éxito: {$count} registros sincronizados.");

                    // Eliminación inmediata tras éxito
                    File::delete($filePath);
                    $this->comment("Archivo {$fileName} eliminado correctamente.");

                } catch (\Exception $e) {
                    $this->error("Error en {$fileName}: ".$e->getMessage());
                    $this->notifyError($fileName, $e);
                }
            }
        }

        $this->info('Proceso de auto-importación finalizado.');

        return 0;
    }

    private function notifyError(string $fileName, \Exception $e): void
    {
        $recipient = env('UCCX_ERROR_RECIPIENT', 'ferncastillo@css.gob.pa');

        try {
            Mail::to($recipient)->send(new ImportErrorNotification(
                fileName: $fileName,
                errorMessage: $e->getMessage(),
                stackTrace: $e->getTraceAsString()
            ));

            $this->info("Notificación de error enviada a {$recipient}");
        } catch (\Exception $mailEx) {
            Log::error('No se pudo enviar notificación de error UCCX: '.$mailEx->getMessage());
            $this->error('Fallo al enviar correo de notificación.');
        }
    }
}
