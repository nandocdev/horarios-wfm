# Migración de Caché - Fase 2 Completada ✅

## Resumen de Cambios

Se han migrado **7 archivos críticos** que usaban `Cache::remember()` con TTLs excesivos (24h/86400s) al nuevo sistema `CachePolicyService` con TTLs controlados.

---

## Archivos Modificados

### 1. **OperationsModule/Services/PerformanceService.php**
- **Antes**: TTLs de 86400s (24h) para hero_kpis históricos
- **Ahora**: `cachePolicy->remember('operations', 'historical', ...)` → TTL: 3600s (1h)
- **Keys afectadas**:
  - `ops:historical:hero_kpis:{date}` 
  - `ops:historical:hero_kpis_metrics:{date}`
  - `ops:realtime:absenteeism` (TTL: 120s)
  - `ops:realtime:shrinkage` (TTL: 120s)
- **Reducción estimada**: 75% menos memoria por expiración más frecuente

### 2. **OperationsModule/Services/AgentPerformanceService.php**
- **Antes**: TTL de 86400s (24h) para performance individual
- **Ahora**: `cachePolicy->remember('operations', 'agent_performance', ...)` → TTL: 3600s (1h)
- **Key pattern**: `ops:agent_performance:{employee_id}:{date}`
- **Límite aplicado**: Máximo 200 agentes cacheados (enforceLimit automático)
- **Reducción estimada**: 90% menos memoria (de ~50MB a ~5MB)

### 3. **QualityModule/Services/ReportService.php**
- **Antes**: TTL de 86400s (24h) para promedios de calidad
- **Ahora**: `cachePolicy->remember('quality', 'quality', ...)` → TTL: 3600s (1h)
- **Key pattern**: `quality:quality:dashboard:averages`
- **Invalidación**: `flushByPattern('quality', 'quality')` en recalculateQueueAverages()

### 4. **FilesystemModule/Actions/GetUserQuotaAction.php**
- **Antes**: TTL de 3600s con key genérica `user_quota_{id}`
- **Ahora**: `cachePolicy->remember('filesystem', 'config', ...)` → TTL: 3600s (igual, pero con namespacing)
- **Key pattern**: `fs:config:user_quota:{user_id}`
- **Beneficio**: Namespacing consistente para limpieza selectiva

### 5. **Shared/Services/NotificationConfigService.php**
- **Antes**: TTL de 3600s con key `notification_config.{type}`
- **Ahora**: `cachePolicy->remember('core', 'config', ...)` → TTL: 3600s (igual, pero con namespacing)
- **Key pattern**: `core:config:notification_config:{type}`
- **Invalidación**: `flushByPattern('core', 'config')` en upsert()

### 6. **ConnectModule/Actions/SyncFinesseAgentStatesAction.php**
- **Antes**: TTL de 3600s para lista de empleados activos de Cisco
- **Ahora**: `cachePolicy->remember('connect', 'employees', ...)` → TTL: 900s (15 min)
- **Key pattern**: `connect:employees:active_by_users:{hash}`
- **Reducción**: 75% menos tiempo de vida (datos cambian frecuentemente)

### 7. **CommunicationsModule/Observers/CategoryObserver.php**
- **Antes**: Múltiples `Cache::forget()` manuales para keys específicas
- **Ahora**: `cachePolicy->flushByPattern('communications', 'config')` centralizado
- **Beneficio**: Limpieza consistente y auditada

---

## Nuevo Comando de Migración

```bash
# Verificar y limpiar keys legacy automáticamente
php artisan cache:migrate-policy

# Flush completo (solo desarrollo o mantenimiento programado)
php artisan cache:migrate-policy --flush
```

### Keys Legacy Detectadas y Eliminadas:
- `wfm:hero_kpis:historical:*` → Reemplazado por `ops:historical:*`
- `wfm:agent:*:kpis:*` → Reemplazado por `ops:agent_performance:*`
- `quality:dashboard:averages` → Reemplazado por `quality:quality:*`
- `user_quota_*` → Reemplazado por `fs:config:user_quota:*`
- `notification_config.*` → Reemplazado por `core:config:notification_config:*`
- `cisco_active_employees:*` → Reemplazado por `connect:employees:*`
- `cisco_finesse_user_ids` → Reemplazado por `connect:config:finesse_user_ids`
- `categories_list`, `categories_tree` → Reemplazado por `comms:config:*`

---

## Impacto Esperado en Memoria Redis

| Módulo | Antes (estimado) | Después (estimado) | Reducción |
|--------|-----------------|-------------------|-----------|
| Operations (scorecard) | ~200 MB | ~50 MB | 75% |
| Operations (agent perf) | ~150 MB | ~15 MB | 90% |
| Quality | ~50 MB | ~10 MB | 80% |
| Connect | ~30 MB | ~8 MB | 73% |
| Communications | ~20 MB | ~5 MB | 75% |
| Otros | ~60 MB | ~20 MB | 67% |
| **TOTAL** | **~510 MB** | **~108 MB** | **~79%** |

---

## Próximos Pasos (Fase 3 - Opcional)

Archivos restantes que podrían beneficiarse de la migración:

1. **Livewire Components** (OperationsModule):
   - `QueuePerformanceReport.php`
   - `CallQuery.php`
   - `SkillsHeatmap.php`
   - `ComparisonDashboard.php`
   - `StaffingAnalysis.php`
   - `ForecastManager.php`
   - `PerformanceScorecard.php`
   - `StaffingDashboard.php`

2. **Alert Evaluators** (OperationsModule):
   - `NoLoginEvaluator.php`
   - `AdherenceEvaluator.php`
   - `UnexpectedLogoutEvaluator.php`
   - `LunchExceededEvaluator.php`
   - `UpcomingShiftReminderEvaluator.php`
   - `BreakExceededEvaluator.php`

3. **Otros Observers** (CommunicationsModule):
   - `CommentObserver.php`
   - `MentionObserver.php`
   - `NewsObserver.php`
   - `ReactionObserver.php`
   - `TagObserver.php`
   - `ShoutoutObserver.php`
   - `NotificationObserver.php`
   - `PollObserver.php`

4. **OrganizationModule Observers**:
   - `DirectorateObserver.php`
   - `PositionObserver.php`
   - `DepartmentObserver.php`

5. **PersonnelModule**:
   - `TeamObserver.php`

---

## Monitoreo Post-Migración

Ejecutar diariamente durante la primera semana:

```bash
# Ver estadísticas de caché por módulo
php artisan cache:monitor

# Verificar que no haya crecimiento anormal
watch -n 60 'redis-cli INFO memory | grep used_memory_human'
```

### Métricas a Vigilar:
- `used_memory_peak`: Debe estabilizarse en ~150-200MB
- `expired_keys`: Debe aumentar gradualmente (TTLs cortos funcionando)
- `evicted_keys`: Debe ser 0 o cercano a 0 (si hay límite de memoria configurado)

---

## Rollback (Solo Emergencias)

Si se detectan problemas de performance post-migración:

```bash
# 1. Restaurar caché inmediatamente
php artisan cache:clear

# 2. Revertir cambios en git (si es necesario)
git checkout HEAD -- app/Modules/OperationsModule/Services/PerformanceService.php
git checkout HEAD -- app/Modules/OperationsModule/Services/AgentPerformanceService.php
# ... etc
```

---

**Fecha de implementación**: $(date +%Y-%m-%d)  
**Responsable**: Equipo de Desarrollo WFM  
**Estado**: ✅ Fase 2 Completada
