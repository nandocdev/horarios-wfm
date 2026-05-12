<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class CiscoHistoricalDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $files = [
            'database/data/202604290921 2026 04 29 09 21.sql',
            'database/data/202604290922 2026 04 29 09 22.sql',
            'database/data/202604290922 2026 04 29 09 22-1777472575450.sql',
        ];

        $this->command->info('Iniciando importación masiva de datos históricos de Cisco...');

        foreach ($files as $file) {
            $path = base_path($file);

            if (! File::exists($path)) {
                $this->command->warn("Archivo no encontrado: {$file}");
                continue;
            }

            $size = round(File::size($path) / 1024 / 1024, 2);
            $this->command->info("Procesando {$file} ({$size} MB)...");

            $startTime = microtime(true);

            try {
                $this->executeSqlFile($path);
                
                $duration = round(microtime(true) - $startTime, 2);
                $this->command->info("Éxito. Tiempo: {$duration}s");
            } catch (\Exception $e) {
                $this->command->error("Error procesando {$file}: " . $e->getMessage());
                Log::error("CiscoHistoricalDataSeeder Error: " . $e->getMessage());
            }
        }

        $this->command->info('Proceso de carga histórica finalizado.');
    }

    /**
     * Ejecuta el archivo SQL usando el comando psql si está disponible,
     * de lo contrario usa DB::unprepared (más lento).
     */
    private function executeSqlFile(string $path): void
    {
        $dbConfig = config('database.connections.' . config('database.default'));

        if ($dbConfig['driver'] === 'pgsql') {
            $password = $dbConfig['password'];
            $command = sprintf(
                'PGPASSWORD=%s psql -h %s -U %s -d %s -f %s',
                escapeshellarg($password),
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($path)
            );

            // Redirigir salida a /dev/null para no saturar la consola pero capturar errores
            $process = Process::fromShellCommandline($command . ' > /dev/null 2>&1');
            $process->setTimeout(null); // Sin límite de tiempo para archivos grandes
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput() ?: 'Error desconocido ejecutando psql');
            }
        } else {
            // Fallback para otros drivers o si psql no está
            $sql = File::get($path);
            DB::unprepared($sql);
        }
    }
}
