<?php

declare(strict_types=1);

namespace App\Shared\Console\Commands;

use App\Shared\Support\Cache\CachePolicyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Comando para monitoreo y limpieza de caché Redis.
 * 
 * Uso:
 *   php artisan cache:monitor              - Ver estadísticas de uso
 *   php artisan cache:monitor --stats      - Estadísticas detalladas
 *   php artisan cache:monitor --flush wfm  - Limpiar módulo específico
 *   php artisan cache:monitor --prune      - Aplicar límites por categoría
 */
class CacheMonitorCommand extends Command
{
    protected $signature = 'cache:monitor 
                            {--stats : Mostrar estadísticas detalladas}
                            {--flush= : Limpiar módulo específico (wfm, connect, operations, etc.)}
                            {--prune : Aplicar límites de memoria por categoría}
                            {--keys : Listar todas las keys (puede ser lento)}';

    protected $description = 'Monitorea y gestiona el uso de caché Redis para prevenir consumo excesivo de memoria';

    private CachePolicyService $policyService;

    public function handle(CachePolicyService $policyService): int
    {
        $this->policyService = $policyService;

        if ($this->option('flush')) {
            return $this->handleFlush();
        }

        if ($this->option('prune')) {
            return $this->handlePrune();
        }

        if ($this->option('keys')) {
            return $this->handleListKeys();
        }

        return $this->handleStats();
    }

    /**
     * Muestra estadísticas de uso de caché.
     */
    private function handleStats(): int
    {
        $this->info('📊 Estado del Caché Redis');
        $this->newLine();

        $stats = $this->policyService->getStats();

        if (isset($stats['error'])) {
            $this->error('Error al obtener estadísticas: ' . $stats['error']);
            return Command::FAILURE;
        }

        $table = [];
        $total = 0;

        foreach ($stats as $module => $count) {
            if ($module === 'total' || $module === 'error') {
                continue;
            }
            
            $percentage = $stats['total'] > 0 
                ? round(($count / $stats['total']) * 100, 1) 
                : 0;
            
            $table[] = [
                'module' => ucfirst($module),
                'keys' => $count,
                'percentage' => "{$percentage}%",
            ];
            
            $total += $count;
        }

        $this->table(['Módulo', 'Keys', '% del Total'], $table);

        $this->newLine();
        $this->info("Total de keys: <fg=green>{$stats['total']}</>");

        // Información de memoria de Redis (si está disponible)
        try {
            $redis = Cache::store('redis')->getStore()->getRedis();
            $info = $redis->info('memory');
            
            $this->newLine();
            $this->info('💾 Memoria Redis');
            
            if (isset($info['used_memory'])) {
                $usedMb = round($info['used_memory'] / 1024 / 1024, 2);
                $this->line("  Usada: <fg=yellow>{$usedMb} MB</>");
            }
            
            if (isset($info['used_memory_peak'])) {
                $peakMb = round($info['used_memory_peak'] / 1024 / 1024, 2);
                $this->line("  Peak: {$peakMb} MB");
            }
            
            if (isset($info['maxmemory'])) {
                $maxMb = (int) $info['maxmemory'] > 0 
                    ? round((int) $info['maxmemory'] / 1024 / 1024, 2) 
                    : 'Ilimitado';
                $this->line("  Máxima configurada: {$maxMb} MB");
            }
            
            if (isset($info['used_memory_rss'])) {
                $rssMb = round($info['used_memory_rss'] / 1024 / 1024, 2);
                $this->line("  RSS (sistema): {$rssMb} MB");
            }
            
        } catch (\Exception $e) {
            $this->warn('No se pudo obtener información de memoria: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('💡 Tip: Usa --prune para aplicar límites automáticos o --flush=<modulo> para limpiar un módulo específico.');

        return Command::SUCCESS;
    }

    /**
     * Limpia un módulo específico.
     */
    private function handleFlush(): int
    {
        $module = $this->option('flush');
        
        if (empty($module)) {
            $this->error('Debes especificar un módulo: --flush=wfm');
            return Command::FAILURE;
        }

        $validModules = ['wfm', 'connect', 'operations', 'communications', 'quality', 'personnel', 'organization', 'filesystem'];
        
        if (! in_array($module, $validModules)) {
            $this->error("Módulo inválido. Opciones válidas: " . implode(', ', $validModules));
            return Command::FAILURE;
        }

        $this->warn("⚠️  Eliminando todas las keys del módulo: " . ucfirst($module));
        
        if (! $this->confirm('¿Estás seguro de continuar?')) {
            $this->info('Operación cancelada.');
            return Command::SUCCESS;
        }

        $before = $this->policyService->getStats()[$module] ?? 0;
        
        $this->policyService->flushModule($module);
        
        $after = $this->policyService->getStats()[$module] ?? 0;
        $deleted = $before - $after;

        $this->info("✅ Eliminadas <fg=green>{$deleted}</> keys del módulo " . ucfirst($module));

        return Command::SUCCESS;
    }

    /**
     * Aplica límites de memoria por categoría.
     */
    private function handlePrune(): int
    {
        $this->info('🔍 Aplicando límites de caché por categoría...');
        $this->newLine();

        $categories = [
            ['module' => 'wfm', 'category' => 'scorecard'],
            ['module' => 'wfm', 'category' => 'agent_performance'],
            ['module' => 'operations', 'category' => 'realtime'],
        ];

        $totalDeleted = 0;

        foreach ($categories as $config) {
            $module = $config['module'];
            $category = $config['category'];
            
            try {
                $redis = Cache::store('redis')->getStore()->getRedis();
                $prefix = $this->policyService->getPrefix($module) . 
                          $this->normalizeCategory($category) . ':';
                $keys = $redis->keys($prefix . '*');
                $countBefore = count($keys);
                
                $this->policyService->enforceLimit($module, $category);
                
                $keysAfter = $redis->keys($prefix . '*');
                $countAfter = count($keysAfter);
                $deleted = $countBefore - $countAfter;
                
                if ($deleted > 0) {
                    $this->line("  {$module}/{$category}: <fg=red>-{$deleted}</> keys eliminadas");
                    $totalDeleted += $deleted;
                } else {
                    $this->line("  {$module}/{$category}: OK ({$countAfter} keys)");
                }
                
            } catch (\Exception $e) {
                $this->error("  Error en {$module}/{$category}: " . $e->getMessage());
            }
        }

        $this->newLine();
        
        if ($totalDeleted > 0) {
            $this->info("✅ Total: <fg=green>{$totalDeleted}</> keys eliminadas");
        } else {
            $this->info('✅ Todas las categorías dentro de los límites');
        }

        return Command::SUCCESS;
    }

    /**
     * Lista todas las keys del caché.
     */
    private function handleListKeys(): int
    {
        $this->warn('⚠️  Listando todas las keys (esto puede ser lento)...');
        $this->newLine();

        try {
            $redis = Cache::store('redis')->getStore()->getRedis();
            $allKeys = $redis->keys(config('cache.prefix') . '*');
            
            $this->info("Total de keys: " . count($allKeys));
            $this->newLine();

            // Agrupar por módulo
            $grouped = [];
            foreach ($allKeys as $key) {
                $parts = explode(':', $key);
                $module = $parts[0] ?? 'unknown';
                $grouped[$module][] = $key;
            }

            foreach ($grouped as $module => $keys) {
                $this->info("📁 Módulo: {$module} (" . count($keys) . " keys)");
                
                // Mostrar máximo 10 keys por módulo
                $display = array_slice($keys, 0, 10);
                foreach ($display as $k) {
                    $this->line("   - {$k}");
                }
                
                if (count($keys) > 10) {
                    $this->line("   ... y " . (count($keys) - 10) . " más");
                }
                
                $this->newLine();
            }

        } catch (\Exception $e) {
            $this->error('Error al listar keys: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Normaliza categoría (método auxiliar).
     */
    private function normalizeCategory(string $category): string
    {
        $mapping = [
            'agent_performance' => 'agent_performance',
            'scorecard' => 'scorecard',
            'realtime' => 'realtime',
            'metrics' => 'metrics_today',
            'queues' => 'queues',
        ];

        $normalized = strtolower($category);
        
        foreach ($mapping as $variant => $standard) {
            if (str_contains($normalized, $variant)) {
                return $standard;
            }
        }

        return $normalized;
    }
}
