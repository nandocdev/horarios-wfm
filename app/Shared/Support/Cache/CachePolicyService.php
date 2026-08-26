<?php

declare(strict_types=1);

namespace App\Shared\Support\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * Servicio centralizado para gestión de caché con políticas de memoria controlada.
 * 
 * Objetivos:
 * 1. Prevenir crecimiento ilimitado de Redis
 * 2. Estandarizar TTLs por tipo de dato
 * 3. Proveer métodos de invalidación por patrón
 * 4. Implementar circuit breaker para caché
 */
final class CachePolicyService
{
    /**
     * TTLs estandarizados por categoría de dato.
     * Todos en segundos.
     */
    private const TTLS = [
        // Datos en tiempo real (máximo 2 minutos)
        'realtime' => 120,
        
        // Métricas del día actual (5 minutos)
        'metrics_today' => 300,
        
        // Datos históricos (1 hora)
        'historical' => 3600,
        
        // Configuraciones y catálogos (1 hora)
        'config' => 3600,
        
        // Datos de empleados activos (15 minutos)
        'employees' => 900,
        
        // Performance scorecard (5 minutos, NO 24h)
        'scorecard' => 300,
        
        // Agent performance individual (1 hora, NO 24h)
        'agent_performance' => 3600,
        
        // Colas y configuraciones operativas (5 minutos)
        'queues' => 300,
        
        // Circuit breaker states (1 hora)
        'circuit_breaker' => 3600,
        
        // Datos de comunicaciones (noticias, comentarios) - 10 minutos
        'communications' => 600,
        
        // Quality reports (1 hora)
        'quality' => 3600,
    ];

    /**
     * Prefijos por módulo para namespacing y limpieza selectiva.
     */
    private const PREFIXES = [
        'wfm' => 'wfm:',
        'connect' => 'connect:',
        'operations' => 'ops:',
        'communications' => 'comms:',
        'quality' => 'quality:',
        'personnel' => 'hr:',
        'organization' => 'org:',
        'filesystem' => 'fs:',
        'idempotency' => 'idem:',
    ];

    /**
     * Límites máximos de items por categoría (para prevenir crecimiento ilimitado).
     * Estos valores deben monitorearse y ajustarse según uso real.
     */
    private const MAX_ITEMS = [
        'scorecard' => 500,      // Máximo 500 combinaciones empleado/día
        'agent_performance' => 200, // Máximo 200 agentes con performance cacheado
        'realtime' => 100,       // Máximo 100 keys de tiempo real
    ];

    /**
     * Obtiene el TTL estandarizado para una categoría.
     */
    public function getTtl(string $category): int
    {
        return self::TTLS[$category] ?? self::TTLS['config'];
    }

    /**
     * Obtiene el prefijo para un módulo.
     */
    public function getPrefix(string $module): string
    {
        return self::PREFIXES[$module] ?? 'app:';
    }

    /**
     * Construye una key con prefijo de módulo y categoría.
     * 
     * Ejemplo: buildKey('wfm', 'scorecard', 'employee_123_2024-01-15')
     * Resultado: "wfm:scorecard:employee_123_2024-01-15"
     */
    public function buildKey(string $module, string $category, string $identifier): string
    {
        $prefix = $this->getPrefix($module);
        $ttlCategory = $this->normalizeCategory($category);
        
        return "{$prefix}{$ttlCategory}:{$identifier}";
    }

    /**
     * Normaliza el nombre de categoría para matching con TTLS.
     */
    private function normalizeCategory(string $category): string
    {
        // Mapeo de variantes a categorías estándar
        $mapping = [
            'agent_performance' => 'agent_performance',
            'agent_perf' => 'agent_performance',
            'performance' => 'agent_performance',
            'scorecard' => 'scorecard',
            'score' => 'scorecard',
            'realtime' => 'realtime',
            'rt' => 'realtime',
            'metrics' => 'metrics_today',
            'kpis' => 'metrics_today',
            'historical' => 'historical',
            'history' => 'historical',
            'config' => 'config',
            'configuration' => 'config',
            'employees' => 'employees',
            'staff' => 'employees',
            'queues' => 'queues',
            'call_queues' => 'queues',
        ];

        $normalized = strtolower($category);
        
        foreach ($mapping as $variant => $standard) {
            if (str_contains($normalized, $variant)) {
                return $standard;
            }
        }

        return $normalized;
    }

    /**
     * Remember con política de TTL automático según categoría.
     * 
     * @template T
     * @param string $module Módulo (wfm, connect, operations, etc.)
     * @param string $category Categoría del dato (scorecard, agent_performance, realtime, etc.)
     * @param string $key Identificador único
     * @param \Closure(): T $callback Callback para generar el valor si no existe
     * @return T
     */
    public function remember(string $module, string $category, string $key, \Closure $callback): mixed
    {
        $fullKey = $this->buildKey($module, $category, $key);
        $ttl = $this->getTtl($this->normalizeCategory($category));

        return Cache::remember($fullKey, $ttl, $callback);
    }

    /**
     * Invalida todas las keys que coincidan con un patrón de módulo y categoría.
     * 
     * Nota: Redis no soporta wildcard delete nativo en Laravel Cache.
     * Para producción, considerar usar Redis tags o mantener un índice de keys.
     * 
     * @param string $module Módulo a limpiar
     * @param string|null $category Categoría opcional (si null, limpia todo el módulo)
     */
    public function flushByPattern(string $module, ?string $category = null): void
    {
        $prefix = $this->getPrefix($module);
        
        if ($category) {
            $prefix .= $this->normalizeCategory($category) . ':';
        }

        // Obtener todas las keys del cache store
        // NOTA: Esto requiere acceso directo a Redis. Para otros drivers, usar flushAll()
        try {
            $redis = Cache::store('redis')->getStore()->getRedis();
            $keys = $redis->keys($prefix . '*');
            
            if (! empty($keys)) {
                $redis->del(...$keys);
            }
        } catch (\Exception $e) {
            // Fallback: si no hay acceso directo a Redis, loguear advertencia
            \Illuminate\Support\Facades\Log::warning(
                "No se pudo limpiar caché por patrón '{$prefix}': " . $e->getMessage()
            );
        }
    }

    /**
     * Verifica si una categoría ha excedido su límite máximo de items.
     * Útil para decisiones de caché condicional.
     */
    public function isCategoryAtLimit(string $category): bool
    {
        $maxItems = self::MAX_ITEMS[$category] ?? null;
        
        if ($maxItems === null) {
            return false; // Sin límite definido
        }

        try {
            $redis = Cache::store('redis')->getStore()->getRedis();
            $prefix = $this->normalizeCategory($category);
            $keys = $redis->keys("*:{$prefix}:*");
            
            return count($keys) >= $maxItems;
        } catch (\Exception $e) {
            return false; // En caso de error, asumir que no hay límite
        }
    }

    /**
     * Limpia caché de una categoría específica si excede el límite.
     * Estrategia: eliminar las keys más antiguas (primero por patrón de fecha/hora)
     */
    public function enforceLimit(string $module, string $category): void
    {
        $maxItems = self::MAX_ITEMS[$category] ?? null;
        
        if ($maxItems === null) {
            return;
        }

        try {
            $redis = Cache::store('redis')->getStore()->getRedis();
            $prefix = $this->getPrefix($module) . $this->normalizeCategory($category) . ':';
            $keys = $redis->keys($prefix . '*');
            
            if (count($keys) > $maxItems) {
                // Ordenar keys y eliminar las más antiguas
                // Asumiendo que las keys incluyen timestamp o fecha al final
                sort($keys);
                $keysToDelete = array_slice($keys, 0, count($keys) - $maxItems);
                
                if (! empty($keysToDelete)) {
                    $redis->del(...$keysToDelete);
                    \Illuminate\Support\Facades\Log::info(
                        "Limpieza de caché: {$category} excedió límite. Eliminadas " . 
                        count($keysToDelete) . " keys."
                    );
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                "Error aplicando límite de caché para {$category}: " . $e->getMessage()
            );
        }
    }

    /**
     * Flush completo del cache del módulo (solo desarrollo/testing).
     * En producción, usar flushByPattern con precaución.
     */
    public function flushModule(string $module): void
    {
        $this->flushByPattern($module);
    }

    /**
     * Obtiene estadísticas básicas de uso de caché por módulo.
     * Requiere acceso directo a Redis.
     * 
     * @return array<string, int> Count de keys por módulo
     */
    public function getStats(): array
    {
        $stats = [];
        
        try {
            $redis = Cache::store('redis')->getStore()->getRedis();
            
            foreach (self::PREFIXES as $module => $prefix) {
                $keys = $redis->keys($prefix . '*');
                $stats[$module] = count($keys);
            }
            
            // Total general
            $allKeys = $redis->keys(config('cache.prefix') . '*');
            $stats['total'] = count($allKeys);
            
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }

        return $stats;
    }
}
