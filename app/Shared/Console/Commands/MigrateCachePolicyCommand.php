<?php

declare(strict_types=1);

namespace App\Shared\Console\Commands;

use App\Shared\Support\Cache\CachePolicyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Comando de migración para limpiar caché antiguo y adoptar CachePolicyService.
 * 
 * Uso: php artisan cache:migrate-policy
 */
class MigrateCachePolicyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:migrate-policy {--flush : Eliminar todo el caché antiguo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra el caché existente al nuevo sistema de políticas con TTLs controlados';

    private CachePolicyService $cachePolicy;

    public function __construct(CachePolicyService $cachePolicy)
    {
        parent::__construct();
        $this->cachePolicy = $cachePolicy;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Iniciando migración de caché a CachePolicyService...');

        // Keys antiguas que deben ser eliminadas (TTLs largos problemáticos)
        $legacyKeys = [
            // Performance Service - TTLs de 24h reemplazados por 1h
            'wfm:hero_kpis:historical:*',
            'wfm:hero_kpis_metrics:historical:*',
            
            // Agent Performance - TTLs de 24h reemplazados por 1h
            'wfm:agent:*:kpis:*',
            
            // Quality Module - TTLs de 24h reemplazados por 1h
            'quality:dashboard:averages',
            'quality:queue_avg:*',
            
            // Filesystem - TTLs genéricos con namespacing mejorado
            'user_quota_*',
            
            // Notification Config - TTLs genéricos con namespacing mejorado
            'notification_config.*',
            
            // Cisco/Finesse - Keys sin namespacing adecuado
            'cisco_active_employees:*',
            'cisco_finesse_user_ids',
            
            // Communications - Keys sin namespacing
            'categories_list',
            'categories_tree',
            'category:*',
            'news_comments:*',
            'comments_recent',
            'user_mentions:*',
            'mentions_recent',
        ];

        if ($this->option('flush')) {
            $this->warn('⚠️  Eliminando TODO el caché del sistema...');
            
            if ($this->confirm('¿Estás seguro? Esto eliminará todo el caché de Redis.')) {
                try {
                    $redis = Cache::store('redis')->getStore()->getRedis();
                    $prefix = config('cache.prefix');
                    $keys = $redis->keys($prefix . '*');
                    
                    if (! empty($keys)) {
                        $deleted = $redis->del(...$keys);
                        $this->info("✅ Eliminadas {$deleted} keys de Redis.");
                    } else {
                        $this->info('ℹ️  No hay keys en Redis.');
                    }
                } catch (\Exception $e) {
                    $this->error('❌ Error eliminando keys: ' . $e->getMessage());
                    return self::FAILURE;
                }
            }
            
            return self::SUCCESS;
        }

        // Modo normal: limpiar solo keys legacy específicas
        $this->info('🔍 Buscando keys legacy para eliminar...');
        
        try {
            $redis = Cache::store('redis')->getStore()->getRedis();
            $totalDeleted = 0;
            
            foreach ($legacyKeys as $pattern) {
                $keys = $redis->keys(config('cache.prefix') . $pattern);
                
                if (! empty($keys)) {
                    $count = count($keys);
                    $redis->del(...$keys);
                    $totalDeleted += $count;
                    $this->line("   🗑️  Eliminadas {$count} keys con patrón: {$pattern}");
                }
            }
            
            if ($totalDeleted === 0) {
                $this->info('✅ No se encontraron keys legacy. ¡El sistema ya está migrado!');
            } else {
                $this->info("✅ Migración completada. {$totalDeleted} keys legacy eliminadas.");
                $this->newLine();
                $this->info('📊 Estadísticas actuales de caché por módulo:');
                
                $stats = $this->cachePolicy->getStats();
                foreach ($stats as $module => $count) {
                    if ($module !== 'error' && $module !== 'total') {
                        $this->line("   • {$module}: {$count} keys");
                    }
                }
                
                if (isset($stats['total'])) {
                    $this->newLine();
                    $this->info("   Total general: {$stats['total']} keys");
                }
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error durante la migración: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
