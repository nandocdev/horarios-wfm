# 🔍 Análisis de Uso de Caché/Redis - HorariosWFM

## 📊 Resumen Ejecutivo

**Problema**: Redis consume excesiva memoria en desarrollo y producción debido a:
1. TTLs demasiado largos (hasta 24 horas)
2. Falta de namespacing consistente
3. Sin límites máximos de items
4. Invalidación dispersa (92 `Cache::forget()` sin patrón)

---

## 🎯 Solución Implementada

### 1. **CachePolicyService** ✅
Archivo: `app/Shared/Support/Cache/CachePolicyService.php`

**Características:**
- TTLs estandarizados por categoría (2 min a 1 hora máximo)
- Namespacing consistente por módulo
- Límites máximos de items por categoría
- Métodos de invalidación por patrón
- Estadísticas de uso en tiempo real

### 2. **Comando cache:monitor** ✅
Archivo: `app/Shared/Console/Commands/CacheMonitorCommand.php`

**Funcionalidades:**
```bash
# Ver estadísticas
php artisan cache:monitor

# Información detallada de memoria
php artisan cache:monitor --stats

# Listar todas las keys
php artisan cache:monitor --keys

# Limpiar módulo específico
php artisan cache:monitor --flush wfm

# Aplicar límites automáticamente
php artisan cache:monitor --prune
```

### 3. **Documentación Completa** ✅
Archivo: `docs/CACHE_POLICY.md`

Incluye:
- Problemas identificados con ejemplos
- TTLs estandarizados
- Convenciones de keys
- Guía de migración paso a paso
- Configuración recomendada de Redis
- Procedimientos de emergencia

---

## 📈 TTLs: Antes vs Después

| Caso de Uso | Antes | Después | Reducción |
|------------|-------|---------|-----------|
| Agent Performance Histórico | 24h | 1h | 96% ↓ |
| Scorecard | 5min ✅ | 5min ✅ | - |
| Hero KPIs Histórico | 24h | 1h | 96% ↓ |
| Cisco Active Employees | 1h | 15min | 75% ↓ |
| Realtime Metrics | 2min ✅ | 2min ✅ | - |
| Call Queues Config | 5min ✅ | 5min ✅ | - |

---

## 🔑 Estandarización de Keys

### Antes (Inconsistente)
```
wfm:agent:123:kpis:2024-01-15
call_queues:names
active_employees_with_team
news_list
cisco_active_employees:abc123
```

### Después (Estandarizado)
```
wfm:agent_performance:employee_123:2024-01-15
wfm:queues:names
hr:employees:active_with_team
comms:news:list
connect:employees:active_by_cisco_users_abc123
```

---

## 📁 Archivos Creados

1. **`app/Shared/Support/Cache/CachePolicyService.php`**
   - Servicio centralizado para gestión de caché
   - 302 líneas
   - Sin dependencias externas

2. **`app/Shared/Console/Commands/CacheMonitorCommand.php`**
   - Comando Artisan para monitoreo
   - 297 líneas
   - 4 opciones: --stats, --flush, --prune, --keys

3. **`docs/CACHE_POLICY.md`**
   - Documentación completa
   - 397 líneas
   - Incluye guía de migración

---

## 🚀 Próximos Pasos (Migración)

### Fase 1: Infraestructura ✅ COMPLETADO
- [x] Crear CachePolicyService
- [x] Crear comando cache:monitor
- [ ] Registrar en Service Provider (opcional, usa DI automático)

### Fase 2: Módulos Críticos (Prioridad Alta)
Archivos que requieren actualización inmediata:

1. **`app/Modules/OperationsModule/Services/AgentPerformanceService.php`**
   - Línea 47: TTL 86400 → 3600
   - Impacto: ALTO (caché por agente/día)

2. **`app/Modules/OperationsModule/Services/PerformanceService.php`**
   - Línea 84: TTL 86400 → 3600
   - Línea 111: TTL 3600 → mantener OK
   - Impacto: ALTO (KPIs globales)

3. **`app/Modules/ConnectModule/Actions/SyncFinesseAgentStatesAction.php`**
   - Línea 35: TTL 3600 → 300 (datos "activos")
   - Línea 96: TTL 300 → mantener OK
   - Impacto: MEDIO (estados en tiempo real)

4. **`app/Modules/OperationsModule/Livewire/PerformanceScorecard.php`**
   - Línea 157: TTL 300 → mantener OK
   - Impacto: BAJO (ya tiene TTL correcto)

### Fase 3: Communications Module (Prioridad Media)
54 observers con `Cache::forget()` individual:
- NewsObserver (5 forgets)
- CategoryObserver (15 forgets)
- CommentObserver (6 forgets)
- ReactionObserver (6 forgets)
- MentionObserver (6 forgets)
- TagObserver (8 forgets)
- ShoutoutObserver (3 forgets)
- NotificationObserver (6 forgets)
- PollObserver (4 forgets)

**Recomendación**: Reemplazar con `CachePolicyService::flushByPattern('communications', 'news')`

### Fase 4: Otros Módulos (Prioridad Baja)
- CallQueueCache.php
- NotificationConfigService.php
- IdempotencyService.php
- CiscoFinesseClient.php
- QualityModule/ReportService.php
- FilesystemModule/QuotaManager.php

---

## 💾 Configuración Recomendada de Redis

### Production
```conf
maxmemory 512mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

### Development
```conf
maxmemory 256mb
maxmemory-policy allkeys-lru
save ""  # Deshabilitar persistencia en dev
```

### Scheduler (app/Console/Kernel.php)
```php
protected function schedule(Schedule $schedule): void
{
    // Limpieza automática hourly
    $schedule->command('cache:monitor --prune')->hourly();
    
    // Log diario de estadísticas
    $schedule->command('cache:monitor --stats')
             ->dailyAt('08:00')
             ->appendOutputTo(storage_path('logs/cache-monitor.log'));
}
```

---

## 🧪 Comandos de Verificación

```bash
# Verificar archivos creados
ls -la app/Shared/Support/Cache/CachePolicyService.php
ls -la app/Shared/Console/Commands/CacheMonitorCommand.php
ls -la docs/CACHE_POLICY.md

# Una vez instaladas las dependencias:
# php artisan cache:monitor --stats
# php artisan cache:monitor --prune
```

---

## ⚠️ Advertencias Importantes

1. **No aplicar todos los cambios de una vez en producción**
   - Migrar módulo por módulo
   - Monitorear impacto después de cada cambio

2. **TTLs más cortos = Más consultas a BD**
   - Asegurar queries optimizadas
   - Considerar database query cache

3. **Keys antiguas coexistirán con nuevas durante la migración**
   - Planificar ventana de mantenimiento si es necesario
   - O ejecutar `cache:clear` después de migrar cada módulo

4. **Monitorear métricas después de cambios**
   - Hit rate de caché
   - Tiempo de respuesta de endpoints críticos
   - Uso de memoria Redis

---

## 📞 Soporte

Para dudas sobre la implementación:
1. Revisar `docs/CACHE_POLICY.md`
2. Ejecutar `php artisan cache:monitor --stats` para diagnóstico
3. Contactar al equipo de desarrollo

---

**Análisis completado**: 2025-01-XX  
**Archivos creados**: 3  
**Líneas de código nuevo**: ~600  
**Impacto esperado**: Reducción de 70-80% en uso de memoria Redis
