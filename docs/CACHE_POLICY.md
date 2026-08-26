# 📋 Política de Uso de Caché - HorariosWFM

## 🎯 Objetivo

Prevenir el consumo excesivo de memoria Redis mediante:
1. TTLs estandarizados por tipo de dato
2. Límites máximos de items por categoría
3. Invalidación selectiva por patrón
4. Monitoreo proactivo

---

## ⚠️ Problemas Identificados

### 1. **TTLs Excesivamente Largos**
```php
// ❌ ANTES: 24 horas (86400 segundos)
Cache::remember($cacheKey, 86400, fn () => $data);

// ✅ DESPUÉS: 1 hora máximo para performance histórico
Cache::remember($cacheKey, 3600, fn () => $data);
```

**Archivos afectados:**
- `app/Modules/OperationsModule/Services/AgentPerformanceService.php` (línea 47)
- `app/Modules/OperationsModule/Services/PerformanceService.php` (línea 84)

### 2. **Falta de Invalidación Selectiva**
92 llamadas a `Cache::forget()` dispersas en el código sin patrón claro.

### 3. **Caché de Datos en Tiempo Real con TTL Largo**
```php
// ❌ ANTES
Cache::remember('wfm:realtime:absenteeism', 120, ...) // OK
Cache::remember('cisco_active_employees', 3600, ...)  // ❌ Demasiado largo para "activo"

// ✅ DESPUÉS
Cache::remember('connect:realtime:active_employees', 300, ...)
```

### 4. **Keys Sin Namespacing Consistente**
```php
// ❌ ANTES: Mezcla de formatos
'wfm:agent:123:kpis:2024-01-15'
'call_queues:names'
'active_employees_with_team'
'news_list'

// ✅ DESPUÉS: Formato estandarizado
'wfm:agent_performance:employee_123:2024-01-15'
'wfm:queues:names'
'hr:employees:active_with_team'
'comms:news:list'
```

---

## 📊 TTLs Estandarizados

| Categoría | TTL | Justificación |
|-----------|-----|---------------|
| `realtime` | 120s (2 min) | Estados de agentes, métricas en vivo |
| `metrics_today` | 300s (5 min) | KPIs del día actual |
| `scorecard` | 300s (5 min) | Performance scorecard (NO 24h) |
| `agent_performance` | 3600s (1h) | Histórico individual (NO 24h) |
| `historical` | 3600s (1h) | Datos históricos generales |
| `employees` | 900s (15 min) | Listas de empleados activos |
| `queues` | 300s (5 min) | Configuración de colas |
| `config` | 3600s (1h) | Configuraciones del sistema |
| `communications` | 600s (10 min) | Noticias, comentarios |
| `quality` | 3600s (1h) | Reportes de calidad |
| `circuit_breaker` | 3600s (1h) | Estados de circuit breaker |

---

## 🔑 Convenciones de Keys

### Formato General
```
{modulo}:{categoria}:{identificador}
```

### Ejemplos por Módulo

#### WFM Module
```
wfm:scorecard:{employee_id}:{date}
wfm:agent_performance:{employee_id}:{date}
wfm:hero_kpis:{date}
wfm:realtime:{metric_name}
```

#### Connect Module
```
connect:realtime:agent_states
connect:queues:names
connect:queues:aht_goals
connect:finesse:user_ids
```

#### Operations Module
```
ops:performance:{employee_id}:{date}
ops:dashboard:kpi:{date}
ops:alerts:{alert_type}
```

#### Communications Module
```
comms:news:list
comms:news:{id}
comms:comments:{news_id}
comms:mentions:{user_id}
```

#### Personnel/HR Module
```
hr:employees:active
hr:employees:{id}
hr:teams:list
hr:teams:{id}
```

#### Organization Module
```
org:directorates:list
org:departments:list
org:positions:list
```

#### Quality Module
```
quality:dashboard:averages
quality:queue_avg:{queue_id}
```

---

## 🛠️ Migración de Código Existente

### 1. AgentPerformanceService.php

**ANTES:**
```php
$cacheKey = "wfm:agent:{$employee->getId()}:kpis:{$date->toDateString()}";
$dayData = $date->isToday()
    ? $this->performanceAction->execute($employee, $date)->toArray()
    : Cache::remember($cacheKey, 86400, fn () => $this->performanceAction->execute($employee, $date)->toArray());
```

**DESPUÉS:**
```php
use App\Shared\Support\Cache\CachePolicyService;

public function __construct(
    // ... otras dependencias
    private readonly CachePolicyService $cachePolicy,
) {}

// ...

$cacheKey = $this->cachePolicy->buildKey(
    'wfm', 
    'agent_performance', 
    "employee_{$employee->getId()}:{$date->toDateString()}"
);

$ttl = $date->isToday() 
    ? $this->cachePolicy->getTtl('metrics_today') 
    : $this->cachePolicy->getTtl('agent_performance');

$dayData = $date->isToday()
    ? $this->performanceAction->execute($employee, $date)->toArray()
    : Cache::remember($cacheKey, $ttl, fn () => $this->performanceAction->execute($employee, $date)->toArray());
```

### 2. PerformanceService.php

**ANTES:**
```php
if (! $date->isToday()) {
    return Cache::remember("wfm:hero_kpis:historical:{$dateStr}", 86400, function () use ($date) {
        return $this->resolveHeroKpisData($date);
    });
}
```

**DESPUÉS:**
```php
if (! $date->isToday()) {
    return $this->cachePolicy->remember(
        'wfm',
        'hero_kpis',
        "historical:{$dateStr}",
        fn () => $this->resolveHeroKpisData($date)
    );
}
```

### 3. SyncFinesseAgentStatesAction.php

**ANTES:**
```php
$employeeCacheKey = 'cisco_active_employees:'.sha1(implode('|', $ciscoUsers));
$employees = Cache::remember($employeeCacheKey, 3600, function () use ($ciscoUsers) {
    return Employee::where('is_active', true)->whereIn('username', $ciscoUsers)->get();
});
```

**DESPUÉS:**
```php
$employees = $this->cachePolicy->remember(
    'connect',
    'employees',
    'active_by_cisco_users_' . sha1(implode('|', $ciscoUsers)),
    fn () => Employee::where('is_active', true)
        ->whereNotNull('username')
        ->whereIn('username', $ciscoUsers)
        ->get(['id', 'username', 'metadata'])
        ->toArray()
);
```

---

## 🧹 Limpieza de Caché

### Comando de Monitoreo
```bash
# Ver estadísticas de uso
php artisan cache:monitor

# Estadísticas detalladas con información de memoria
php artisan cache:monitor --stats

# Listar todas las keys (lento)
php artisan cache:monitor --keys

# Limpiar módulo específico
php artisan cache:monitor --flush wfm
php artisan cache:monitor --flush connect

# Aplicar límites automáticos (prune)
php artisan cache:monitor --prune
```

### Limpieza Programada (Recomendado)

Agregar al `app/Console/Kernel.php` o usar scheduler:

```php
// En app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Limpieza automática cada hora
    $schedule->command('cache:monitor --prune')->hourly();
    
    // Estadísticas diarias en log
    $schedule->command('cache:monitor --stats')
             ->dailyAt('08:00')
             ->appendOutputTo(storage_path('logs/cache-monitor.log'));
}
```

---

## 📈 Límites Máximos por Categoría

| Categoría | Máximo Items | Estrategia al Exceder |
|-----------|-------------|----------------------|
| `scorecard` | 500 | Eliminar más antiguos (por fecha) |
| `agent_performance` | 200 | Eliminar más antiguos |
| `realtime` | 100 | Eliminar más antiguos |

---

## 🔧 Configuración Recomendada de Redis

### redis.conf
```conf
# Límite de memoria (ajustar según servidor)
maxmemory 512mb

# Política de evacuación cuando se alcanza el límite
maxmemory-policy allkeys-lru

# Persistencia (opcional según necesidades)
save 900 1
save 300 10
save 60 10000
```

### Laravel `.env`
```env
CACHE_STORE=redis
REDIS_CACHE_CONNECTION=cache
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DATABASE=1  # Usar DB separada para caché
```

---

## 🧪 Testing

### Verificar TTLs Correctos
```php
use Illuminate\Support\Facades\Cache;

it('uses correct TTL for agent performance', function () {
    $key = 'wfm:agent_performance:employee_123:2024-01-15';
    
    Cache::remember($key, 3600, fn () => ['test' => 'data']);
    
    expect(Cache::get($key))->toBe(['test' => 'data']);
    
    // Simular paso de tiempo (requiere mock de Cache)
    // ...
});
```

### Verificar Namespacing
```php
it('uses consistent key naming', function () {
    $policy = new CachePolicyService();
    
    $key = $policy->buildKey('wfm', 'scorecard', 'employee_123:2024-01-15');
    
    expect($key)->toBe('wfm:scorecard:employee_123:2024-01-15');
});
```

---

## 📝 Checklist de Migración

### Fase 1: Infraestructura (✅ Completado)
- [x] Crear `CachePolicyService`
- [x] Crear comando `cache:monitor`
- [ ] Registrar service provider (si es necesario)

### Fase 2: Módulos Críticos
- [ ] Actualizar `AgentPerformanceService.php`
- [ ] Actualizar `PerformanceService.php`
- [ ] Actualizar `SyncFinesseAgentStatesAction.php`
- [ ] Actualizar `PerformanceScorecard.php` (Livewire)

### Fase 3: Módulo Communications
- [ ] Actualizar observers (NewsObserver, CategoryObserver, etc.)
- [ ] Revisar `ProcessMentionsAction.php`

### Fase 4: Otros Módulos
- [ ] Revisar `CallQueueCache.php`
- [ ] Revisar `NotificationConfigService.php`
- [ ] Revisar `IdempotencyService.php`
- [ ] Revisar `CiscoFinesseClient.php`

### Fase 5: Monitoreo
- [ ] Configurar job programado para `cache:monitor --prune`
- [ ] Agregar alertas de uso de memoria Redis
- [ ] Documentar procedimientos de emergencia

---

## 🚨 Procedimiento de Emergencia

Si Redis está consumiendo >90% de memoria:

```bash
# 1. Ver estado actual
php artisan cache:monitor --stats

# 2. Limpiar módulos más grandes (generalmente wfm y operations)
php artisan cache:monitor --flush wfm
php artisan cache:monitor --flush operations

# 3. Si persiste, limpiar TODO el caché
php artisan cache:clear

# 4. Como último recurso, flush completo de Redis
redis-cli FLUSHDB  # O FLUSHALL si es dedicado para la app
```

---

## 📚 Referencias

- [Laravel Cache Documentation](https://laravel.com/docs/cache)
- [Redis Best Practices](https://redis.io/docs/latest/develop/use/)
- [Redis Memory Optimization](https://redis.io/docs/latest/develop/manage/memory-optimization/)

---

**Última actualización**: 2025-01-XX  
**Responsable**: Equipo de Desarrollo HorariosWFM
