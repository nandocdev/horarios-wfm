<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Console;

use App\Src\Connect\Application\DTOs\UccxCallDataDTO;
use App\Src\Connect\Application\Handlers\ImportUccxChatHandler;
use App\Src\Connect\Application\Handlers\ImportUccxInboundHandler;
use App\Src\Connect\Application\Handlers\ImportUccxPerformanceHandler;
use App\Src\Connect\Application\Handlers\ImportUccxTransitionsHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class ImportUccxDataCommand extends Command
{
    protected $signature = 'connect:uccx:import {file} {--type=inbound}';
    protected $description = 'Importa datos desde archivo CSV de UCCX (inbound, chat, transitions, performance)';

    public function __construct(
        private readonly ImportUccxInboundHandler $inboundHandler,
        private readonly ImportUccxChatHandler $chatHandler,
        private readonly ImportUccxTransitionsHandler $transitionsHandler,
        private readonly ImportUccxPerformanceHandler $performanceHandler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $file = $this->argument('file');
        $type = $this->option('type');

        if (! File::exists($file)) {
            $this->error("El archivo no existe: {$file}");
            return self::FAILURE;
        }

        $data = $this->parseCsv($file);

        if (empty($data)) {
            $this->warn('No se encontraron datos en el archivo.');
            return self::SUCCESS;
        }

        $this->info("Procesando {$type}: " . count($data) . " registros.");

        try {
            $count = match ($type) {
                'inbound' => $this->importInbound($data),
                'chat' => $this->importChat($data),
                'transitions' => $this->importTransitions($data),
                'performance' => $this->importPerformance($data),
                default => throw new \InvalidArgumentException("Tipo no soportado: {$type}"),
            };

            $this->info("Importación completada: {$count} registros.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Importación falló: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function parseCsv(string $file): array
    {
        $rows = [];
        $handle = fopen($file, 'r');

        if ($handle === false) {
            return $rows;
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);
            return $rows;
        }

        while (($line = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($headers as $i => $header) {
                if (isset($line[$i])) {
                    $row[trim($header)] = trim($line[$i]);
                }
            }
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    private function importInbound(array $data): int
    {
        $count = 0;
        foreach ($data as $row) {
            $dto = new UccxCallDataDTO($row);
            $this->inboundHandler->handle($dto);
            $count++;
        }
        return $count;
    }

    private function importChat(array $data): int
    {
        $count = 0;
        foreach ($data as $row) {
            $dto = new UccxCallDataDTO($row);
            $this->chatHandler->handle($dto);
            $count++;
        }
        return $count;
    }

    private function importTransitions(array $data): int
    {
        return $this->transitionsHandler->handle($data);
    }

    private function importPerformance(array $data): int
    {
        return $this->performanceHandler->handle($data);
    }
}
