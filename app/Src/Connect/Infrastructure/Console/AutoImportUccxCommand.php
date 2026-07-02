<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Console;

use App\Src\Connect\Application\Handlers\ImportUccxInboundHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

final class AutoImportUccxCommand extends Command
{
    protected $signature = 'connect:uccx:auto-import';
    protected $description = 'Monitorea directorio de exportación UCCX e importa automáticamente nuevos archivos CSV';

    public function __construct(
        private readonly ImportUccxInboundHandler $inboundHandler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $watchDir = config('contact-center.uccx.watch_directory', storage_path('uccx/exports'));
        $processedDir = config('contact-center.uccx.processed_directory', storage_path('uccx/processed'));

        if (! File::exists($watchDir)) {
            File::makeDirectory($watchDir, 0755, true);
            $this->info("Directorio de monitoreo creado: {$watchDir}");
        }

        if (! File::exists($processedDir)) {
            File::makeDirectory($processedDir, 0755, true);
        }

        $this->info("Monitoreando: {$watchDir}");

        while (true) {
            $files = File::files($watchDir);
            $imported = 0;

            foreach ($files as $file) {
                if ($file->getExtension() !== 'csv') {
                    continue;
                }

                $this->info("Importando: {$file->getFilename()}");

                try {
                    $handle = fopen($file->getPathname(), 'r');
                    if ($handle === false) {
                        continue;
                    }

                    $headers = fgetcsv($handle);
                    if ($headers === false) {
                        fclose($handle);
                        continue;
                    }

                    while (($line = fgetcsv($handle)) !== false) {
                        $row = [];
                        foreach ($headers as $i => $header) {
                            if (isset($line[$i])) {
                                $row[trim($header)] = trim($line[$i]);
                            }
                        }

                        if (! empty($row)) {
                            // TODO: Determinar tipo según contenido del CSV
                            $this->inboundHandler->handle(new \App\Src\Connect\Application\DTOs\UccxCallDataDTO($row));
                        }
                    }

                    fclose($handle);

                    $destPath = $processedDir . '/' . $file->getFilename();
                    File::move($file->getPathname(), $destPath);
                    $imported++;
                } catch (\Throwable $e) {
                    Log::error('Auto-import failed for file', [
                        'file' => $file->getFilename(),
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("Error importando {$file->getFilename()}: {$e->getMessage()}");
                }
            }

            if ($imported > 0) {
                $this->info("Importados {$imported} archivo(s).");
            }

            sleep(10);
        }
    }
}
