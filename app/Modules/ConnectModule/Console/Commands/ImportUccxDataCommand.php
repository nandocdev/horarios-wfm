<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Console\Commands;

use App\Modules\ConnectModule\Actions\ImportUccxChatAction;
use App\Modules\ConnectModule\Actions\ImportUccxInboundAction;
use App\Modules\ConnectModule\Actions\ImportUccxPerformanceAction;
use App\Modules\ConnectModule\Actions\ImportUccxTransitionsAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class ImportUccxDataCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'uccx:import {path? : Archivo o directorio a importar} {--all : Importar todos los archivos en el UCCX_DATA_PATH}';

    /**
     * @var string
     */
    protected $description = 'Importa datos históricos de Cisco UCCX desde archivos CSV';

    public function handle(
        ImportUccxInboundAction $inboundAction,
        ImportUccxTransitionsAction $transitionsAction,
        ImportUccxPerformanceAction $performanceAction,
        ImportUccxChatAction $chatAction
    ): int {
        $path = $this->argument('path');
        $importAll = $this->option('all');

        $basePath = env('UCCX_DATA_PATH', '/srv/data/');

        if ($importAll) {
            $this->importFullStack($basePath, $inboundAction, $transitionsAction, $performanceAction, $chatAction);

            return 0;
        }

        if (! $path) {
            $this->error('Debe especificar un archivo/directorio o usar --all');

            return 1;
        }

        if (! File::exists($path)) {
            $this->error("La ruta no existe: {$path}");

            return 1;
        }

        if (File::isDirectory($path)) {
            $this->importDirectory($path, $inboundAction, $transitionsAction, $performanceAction, $chatAction);
        } else {
            $this->importFile($path, $inboundAction, $transitionsAction, $performanceAction, $chatAction);
        }

        $this->info('Proceso de importación finalizado.');

        return 0;
    }

    private function importFullStack(string $basePath, $inboundAction, $transitionsAction, $performanceAction, $chatAction): void
    {
        $dirs = ['inbound', 'not_ready', 'aht', 'chat'];
        foreach ($dirs as $dir) {
            $fullDir = rtrim($basePath, '/').'/'.$dir;
            if (File::exists($fullDir)) {
                $this->info("=== Importando Directorio: {$dir} ===");
                $this->importDirectory($fullDir, $inboundAction, $transitionsAction, $performanceAction, $chatAction);
            }
        }
    }

    private function importDirectory(string $directory, $inboundAction, $transitionsAction, $performanceAction, $chatAction): void
    {
        $files = File::files($directory);
        $csvFiles = array_filter($files, fn ($file) => $file->getExtension() === 'csv');

        $this->info('Encontrados '.count($csvFiles)." archivos CSV en {$directory}");

        foreach ($csvFiles as $file) {
            $this->importFile($file->getPathname(), $inboundAction, $transitionsAction, $performanceAction, $chatAction);
        }
    }

    private function importFile(string $filePath, $inboundAction, $transitionsAction, $performanceAction, $chatAction): void
    {
        $type = $this->detectType($filePath);
        $this->comment("Importando [{$type}]: ".basename($filePath));

        try {
            $action = match ($type) {
                'inbound' => $inboundAction,
                'not_ready' => $transitionsAction,
                'aht' => $performanceAction,
                'chat' => $chatAction,
                default => throw new \Exception("Tipo de archivo no soportado para: {$filePath}")
            };

            $count = $action->execute($filePath);
            $this->info("Sincronizados {$count} registros.");
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());
        }
    }

    private function detectType(string $filePath): string
    {
        $dirName = basename(dirname($filePath));

        return match ($dirName) {
            'inbound' => 'inbound',
            'not_ready' => 'not_ready',
            'aht' => 'aht',
            'chat' => 'chat',
            default => 'unknown'
        };
    }
}
