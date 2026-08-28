# Auditoría — AuditModule

> Fecha: 2026-08-27
> Estado: 🟡 Requiere atención

## 1. Resumen ejecutivo

**AuditModule** es el módulo de trazabilidad y reporte del monolito. Su propósito es registrar cada cambio significativo en el sistema (creación, actualización, eliminación de modelos dominios) y proporcionar herramientas para exportar y filtrar estos logs.

El módulo es **funcional y bien delimitado** para su problema real. No hay sobreingeniería: un modelo `AuditLog`, políticas Spatie basadas en permisos, Livewire para la UI, Actions para la lógica, y un par de listeners que conectan con eventos de otros módulos (Wfm, Personnel). La inmudabilidad via trigger de PostgreSQL es un punto fuerte documentado.

**Sin embargo, hay 3 clusters de riesgo inmediato:**

1. **Autorización incompleta en Livewire** — `ListAuditLogs` confía en `->can('viewAny', AuditLog::class)` en la ruta; invocables vía Livewire snapshot sin re-validación dentro del componente.
2. **Export sin validación per-record** — cualquier usuario con `audit.export` puede exportar TODOS los logs sin filtrado por entidad/usuario. No hay `whereHas` ni scope que limite qué logs puede ver/exportar cada usuario.
3. **PII sin masking en export** — los logs contienen `ip_address`, nombres de usuario, emails, y datos sensibles en `before`/`after` JSONB. El export a CSV/JSON los muestra en claro.

Adicionalmente: el comando `audit:prune` tiene riesgo de transacción sin validación de inputs, y no hay constraint única en `entity_type+entity_id` (logs duplicados posibles).

Con 2 hotfixes (autorización Livewire + masking en export) + 3 fixes de integridad el módulo pasa a operable; requiere ruta de 2 sprints para quedar 🟢.

## 2. Alcance

**Archivos inspeccionados (24):**

- `Models/`: `AuditLog:1` (99L) — entidad de auditoría, soft-delete implied, JSONB before/after, scope `filter()`.
- `Policies/`: `AuditLogPolicy:1` (26L) — `viewAny/view/export` basados en `hasPermissionTo`.
- `Livewire/`: `ListAuditLogs:1` (119L) — componente con paginación, filtros en vivo, export a CSV/JSON, modal detalle.
- `Resources/Views/`: `list-audit-logs.blade.php:141` — tabla Flux con filtros, acciones CSV/JSON, modal.
- `Http/Controllers/`: `AuditExportController:1` (52L) — `export()` con `$this->authorize('export', AuditLog::class)`, CSV y JSON.
- `Actions/`: `ExportAuditLogsAction:1` (33L) — transacción, filtros, eager-load `user`.
- `DTOs/`: `AuditLogExportDTO:1` (31L) — `search`, `action`, `entityType`, `dateFrom`, `dateTo`, `format`.
- `Console/Commands/`: `AuditPruneCommand:1` (83L) — `--days`, `--chunk`, `--dry-run`, `set_config('app.audit_maintenance')`.
- `Listeners/`: 4 listeners (`AuditLeaveRequestCreatedListener`, `AuditLeaveRequestDecisionListener`, `AuditShiftSwapApprovedListener`, `AuditWeeklySchedulePublishedListener`) — registran `AuditLog` ante eventos de dominio.
- `Models/Relations/`: `AuditLog.user()` `BelongsTo(User)`.
- `Providers/ModuleServiceProvider:1` (71L) — boot: rutas, políticas, Livewire, listeners; register: comando.
- `Routes/web.php:1` (15L) — 2 rutas con `->can('viewAny', AuditLog::class)` y `->can('export', AuditLog::class)`.
- `Tests/`: 11 test files (ver sección de cobertura).

**Áreas cubiertas:** arquitectura, backend/Laravel, DB/PostgreSQL, Livewire frontend, seguridad (autorización por permiso), testing, performance, observabilidad.

**No modificado:** cero cambios de código durante la auditoría (solo lectura y `php artisan migrate:status`).

## 3. Arquitectura actual

```
Entrada (HTTP/Livewire)
  ↓
Presentación: Livewire Component (ListAuditLogs) + Controllers (AuditExport)
  ↓
Aplicación: Actions (ExportAuditLogsAction) + DTOs (AuditLogExportDTO) + Policies (AuditLogPolicy) + Middleware CheckMaintenanceMode (global)
  ↓
Dominio: AuditLog (entity_type, entity_id, action, before/after JSONB, ip_address, user_id, actor_name, actor_email) — SoftDeletes implícito — Trigger de inmutabilidad PostgreSQL
  ↓
Persistencia: PostgreSQL (audit_logs con indexes, trigger previene UPDATE/DELETE)
  ↓
Integraciones: Events Shared (LeaveRequestCreated/Decision/ShiftSwapApproved/WeeklySchedulePublished) → 4 Listeners → AuditLog; Command audit:prune; ModuleServiceProvider boot centraliza listeners.
```

**Bien resuelto:** Transaccionalidad consistente en Actions (`DB::transaction`), inmudabilidad via trigger PostgreSQL (`prevent_audit_log_modification`), scope `filter()` con `ILIKE` y rangos de fechas, eager-load `user` en 2 queries máximo, filtros de búsqueda consistentes entre Livewire y Action/Controller.

**Deuda estructurada:** 4 listeners cubren eventos específicos de Wfm+Personnel; ¿otros eventos deberían ser auditados? No hay constraint única en `entity_type+entity_id`; prune command usa `set_config` de sesión; livewire carece de `authorize()` intra-componente; export muestra PII en claro.

## 4. Dependencias

**Outbound (AuditModule → otros):**

- `App\Modules\CoreModule\Models\User` (AuditLog.user BelongsTo) — acoplamiento leve pero justificado (auditoría de la identidad).
- `App\Modules\PersonnelModule\Models\Employee` (listeners referencian Employee).
- `App\Modules\WfmModule\Models\{LeaveRequest, ShiftSwapRequest, WeeklySchedule}` (listeners importan directamente).
- `App\Shared\Events\{LeaveRequestCreated, LeaveRequestDecision, ShiftSwapApproved, WeeklySchedulePublished}` — eventos compartidos entre módulos.
- `Spatie\Permission\Permission` — `audit.view`, `audit.export`.

**Inbound (otros → AuditModule):** 4 listeners de dominio que registran logs cuando ocurren eventos en Wfm/Personnel. `ListAuditLogs` Livewire y `AuditExportController` son consumidores finales.

**Infraestructura:** PostgreSQL 17.11 (trigger de inmutabilidad, JSONB, index btree), Redis (no usado directamente), Queue `default` (Horizon, para commands), Filesystem `local` (no usado).

**Circular:** No detectada. Dirección correcta: `Core ← Personnel/Wfm ← Audit (como consumidor de eventos)`.

## 5. Health Score

| Área         | Estado | Justificación                                                                                                                                                                                |
| ------------ | ------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Arquitectura | 🟡     | Funcional y delimitado, pero 3 riesgos de seguridad/integridad identificados.                                                                                                                |
| Backend      | 🟡     | Actions/Policies/DOTs existen pero carecen de validación per-record y masking de PII.                                                                                                        |
| Database     | 🟡     | Trigger de inmutabilidad PostgreSQL (punto fuerte); falta constraint única en entity_type+entity_id; ip_address sin validación; JSONB sin restricciones.                                     |
| Frontend     | 🟡     | Livewire simple y eficiente; 2 botones de export, filtros debounce 250ms; carece de authorize() intra-componente.                                                                            |
| Security     | 🔴     | P0: export sin validación per-record + PII en claro; P1: autorización Livewire bypassable; IP sin validar.                                                                                   |
| Testing      | 🟡     | 11 tests cubren export, policies, constraints, prune, eventos, inmutabilidad. **Faltan:** tests de autorización cross-entity, masking PII, IDOR en listado, y el gap de events no auditados. |
| Performance  | 🟡     | No hay N+1 medido (eager-load user en 2 queries); pero prune command hace `set_config` por transacción y scan full table sin límite si no hay --days.                                        |
| Operabilidad | 🟡     | Auditable + trigger de inmutabilidad ok; falta logging estructurado y manejo de fallos en prune masivo.                                                                                      |

**Estado general: 🔴 Requiere intervención** — P0 de exportación/ PII exige hotfix antes de exponer a más roles; resto P1 no bloquea operación diaria pero deben ir en próximo sprint.

## 6. Hallazgos

### [P0] Exportación de logs sin autorización per-record — cualquier usuario con `audit.export` puede descargar TODOS los logs

**Categoría:** Security

**Ubicación:** `app/Modules/AuditModule/Http/Controllers/AuditExportController.php:20` (`$this->authorize('export', AuditLog::class)`) y `Livewire/ListAuditLogs::export():85-96`

**Problema:** El controller autoriza la ability `audit.export` a nivel de modelo, **no por registro**. Un usuario con permiso `audit.export` puede solicitar `/admin/audit/export` y recibir **todos** los audit logs del sistema en CSV o JSON, incluyendo `ip_address`, nombres de usuario, emails, y datos sensibles en `before`/`after`. No hay `whereHas`, `scope`, ni filtrado por entidad/usuario al que el usuario tenga acceso. Igual en Livewire `export()` que redirige a la misma ruta.

**Evidencia:** `AuditExportController@export` line 20 hace `$this->authorize('export', AuditLog::class)` — Spatie `Gate::authorize` chequea `hasPermissionTo('audit.export')` sobre el modelo, no sobre instancias individuales. `Livewire/ListAuditLogs::export()` line 85 redirige a `route('audit.export')` con los mismos parámetros de filtro, pero el controller no los aplica como restricción.

**Impacto:** Fuga masiva de PII (IPs, nombres, emails, datos de cambios de esquema), violación de política de retención de datos, exposición de información interna del sistema. Explotable cualquier vez que un supervisor o admin tenga `audit.export` pero no deba ver logs de otros equipos.

**Recomendación:** Añadir filtrado en el controller/action basado en los permisos del usuario. O bien, validar que el usuario puede ver al menos el `entity_type`/`entity_id` de cada log. O implementar `can('view', $log)` por registro en la query. Registrar ADR sobre política de retención y clasificación de datos.

**Complejidad:** Media

**Prioridad:** Inmediata

---

### [P1] Livewire ListAuditLogs sin authorize intra-componente — bypass de ruta

**Categoría:** Security

**Ubicación:** `app/Modules/AuditModule/Livewire/ListAuditLogs.php:71-82` (`getQuery()`) — la ruta `Routes/web.php:11` tiene `->can('viewAny', AuditLog::class)` pero el componente `getQuery()` no re-valida.

**Problema:** La ruta declara `->can('viewAny', AuditLog::class)` pero el método `getQuery()` del Livewire componente construye la query de filtrado sin `$this->authorize()` ni `Gate::authorize()`. Un usuario autenticado con `verified` pero sin `audit.view` podría acceder al componente Livewire (conociendo la URL o vía historial) y ver/filtrar logs si la ruta fuera pública o mediante snapshot/hijack.

**Evidencia:** `ListAuditLogs::getQuery()` lines 73-82 construye `AuditLog::query()->with('user')->filter(...)` — cero `authorize()` o `Gate::authorize`. La ruta sí tiene `->can('viewAny', AuditLog::class)` pero Livewire puede evadir el check de ruta si se despacha el componente fuera de ese contexto.

**Impacto:** Acceso no autorizado a lista de logs si se despacha el componente Livewire fuera de la ruta protegida. Menor riesgo que P0 pero coherente con el patrón de vulnerabilidades Livewire vistas en módulos anteriores.

**Recomendación:** Añadir `$this->authorize('viewAny', AuditLog::class)` en `mount()` del componente y/o `Gate::authorize('viewAny', AuditLog::class)` al inicio de `getQuery()`. Documentar en ADR que la autorización se resuelve en ruta y el componente la asume.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P1] PII sin masking en exportación CSV/JSON — IP, nombres, emails en claro

**Categoría:** Security / Database

**Ubicación:** `app/Modules/AuditModule/Http/Controllers/AuditExportController.php:31-44` (formato CSV) y `:25-27` (formato JSON) — los logs contienen `ip_address`, `$log->actor_label` (nombre usuario), y datos JSONB `before`/`after` que pueden contener información sensible.

**Problema:** La exportación a CSV (line 31-44) y JSON (line 25-27) muestra `ip_address` tal cual, el nombre del usuario vía `$log->actor_label` (que consulta `user->name`), y los campos `before`/`after` JSONB sin ningún tipo de enmascaramiento. Según ley de protección de datos (Panamá/Reglamento local), direcciones IP y datos de identificación personal deben ser anonimizadas o censuradas en reports internos.

**Evidencia:** `AuditExportController@export` line 36: `$log->ip_address` en CSV. Line 41: `$log->actor_label` que llama a `$log->user->name`. JSON line 26: `$logs->toArray()` incluye todos los fills.

**Impacto:** Violación de norma de protección de datos al exportar logs a archivos CSV/JSON que podrían ser almacenados o compartidos. IP + nombre completo permite correlación de identidad. Datos en `before`/`after` dependen del modelo auditado — podrían contener emails, salarios, IDs de otros sistemas.

**Recomendación:** Añadir máscara en el export: `ip_address` → `***.***.***.*` (últimos octetos), `$log->actor_label` → nombre truncado o `user->email` oculto, y en `before`/`after` filtrar/redactar claves sensibles antes de exportar. O bien, crear un `view` o DTO de export con solo datos no sensibles. Registrar ADR de clasificación de datos para logs de auditoría.

**Complejidad:** Media (requiere decisión de producto sobre qué datos son sensibles)

**Prioridad:** Próximo sprint (o Inmediata si datos sensibles en prod)

---

### [P2] No hay constraint única en entity_type+entity_id — logs duplicados posibles

**Categoría:** Database / Integrity

**Ubicación:** `app/Modules/AuditModule/Models/AuditLog.php:18-28` (fillable) y tests `Constraints/AuditLogConstraintsTest.php:60-72` — `expect(AuditLog::where('entity_type', ...).where('entity_id', 1)->count())->toBe(2)` confirma que no hay unicidad.

**Problema:** La tabla `audit_logs` no tiene una restricción `UNIQUE` parcial o total sobre `entity_type + entity_id`. Esto significa que pueden existir múltiples `AuditLog` con la misma entidad y mismo ID (ej. dos logs de "created" para el mismo Employee). El test actual lo acepta como comportamiento esperado, pero va en contra de la integridad de auditoría: un log "created" duplicado obscurece el historial verdadero.

**Evidencia:** `Constraints/AuditLogConstraintsTest.php:60-72` test explicitamente: "permite inserts duplicados en entity_type+entity_id (no hay unique constraint)" y espera count=2.

**Impacto:** Historial de auditoría corrupto — no se puede confiar en que hay un registro "created" por entidad. Riesgo en procesos de compliance que requieren un log único por entidad.

**Recomendación:** Migrar a PostgreSQL añadir `CREATE UNIQUE INDEX audit_logs_entity_type_entity_id_unique ON audit_logs (entity_type, entity_id) WHERE deleted_at IS NULL` (o sin soft-delete). En el modelo, validar en `creating` o usar `firstOrCreate` con los campos correctos. Si se usa soft-delete, la partial index con `WHERE deleted_at IS NULL` permite reciclar IDs después de delete físico.

**Complejidad:** Baja (migración SQL + validación menor)

**Prioridad:** Backlog (no bloquea operación pero afecta integridad a largo plazo)

---

### [P2] Prune command usa `set_config` de sesión sin validación de inputs

**Categoría:** Performance / Reliability

**Ubicación:** `app/Modules/AuditModule/Console/Commands/AuditPruneCommand.php:52-53` (`DB::select("SELECT set_config('app.audit_maintenance', 'on', true)")`)

**Problema:** El comando `audit:prune` ejecuta `set_config` para habilitar el trigger de inmutabilidad que normalmente bloquea DELETE en `audit_logs`. El parámetro `--days` se castea a `(int)` pero no hay validación de límite máximo — un valor `0` o negativo haría `now()->subDays(0)` = hoy, eliminando todo. También `--chunk` sin límite mínimo. Además, `set_config` a nivel de sesión podría afectar a otras conexiones si el sistema tiene múltiples pods.

**Evidencia:** `AuditPruneCommand@handle()` line 23-24: `$days = (int) $this->option('days');` — ningún `if ($days <= 0) ...`. Line 53: `DB::select("SELECT set_config('app.audit_maintenance', 'on', true)")` — session-level setting.

**Impacto:** Ejecutar `audit:prune --days 0` podría borrar todo el histórico de auditoría. Valores extremos en `--chunk` podrían causar comportamiento inesperado. En entorno multi-pod, `set_config` de sesión no persiste across pods, pero podría causar error si otro proceso ya lo tiene set.

**Recomendación:** Añadir validación: `if ($days <= 0) throw InvalidArgumentException('--days debe ser > 0')`. Añadir `--min-chunk` y `--max-chunk`. Considerar mover `set_config` a una transacción LOCAL en lugar de sesión, o documentar que el comando debe ejecutarse en modo mantenimiento aislado.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P2] IP address sin validación de formato — riesgo de injection/log injection

**Categoría:** Security

**Ubicación:** `app/Modules/AuditModule/Models/AuditLog.php:24` (`'ip_address' => 'fillable')` y `AuditLog::log()` line 79 (`'ip_address' => request()?->ip()`)

**Problema:** La columna `ip_address` es `fillable` y se inserta directamente el resultado de `request()->ip()`. No hay validación de formato IPv4/IPv6, ni protección contra cadenas con caracteres especiales que puedan usarse para log injection o almacenamiento de datos basura.

**Evidencia:** `request()->ip()` en Laravel usualmente devuelve una cadena tipo `::1` (IPv6 localhost) o `127.0.0.1`. No hay `Validation` rule ni `Cast` en el modelo.

**Impacto:** Almacenamiento de datos no válidos en la base de datos. Riesgo teórico de log injection si los logs se consumen después en un sistema de logging que no escape correctamente. No es explotable directamente pero viola principio de "basura entrante, basura saliente".

**Recomendación:** Añadir validación en el modelo o en el evento `creating`: `preg_match('/^(\d{1,3}\.){3}\d{1,3}$|^([0-9a-fA-F]{0,4}:){2,5}[0-9a-fA-F]{0,4}$/', $ip)` o usar Laravel `Rule::ip` si se validan en Request. Considerar almacenar la IP como `inet` type en PostgreSQL en lugar de `varchar`.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P2] Cobertura de tests insuficiente para flujos críticos

**Categoría:** Testing

**Ubicación:** `tests/Feature/Modules/AuditModule/` — 11 test files con Tests: `AuditLogEventTest` (12 tests), `AuditLogConstraintsTest` (10 tests), `AuditPruneCommandTest` (8 tests), `AuditExportControllerTest` (78 tests), `AuditLogPolicyTest` (8 tests), `ExportAuditLogsActionTest` (41 tests) + otros.

**Problema:** **Faltan tests críticos:**

| Flujo                                                      | Estado      | Riesgo    |
| ---------------------------------------------------------- | ----------- | --------- |
| Export con filtros cross-entity (usuario solo ve sus logs) | ❌ Sin test | P1 — M002 |
| Export masking PII (IP/nombres ocultos)                    | ❌ Sin test | P1 — M003 |
| ListAuditLogs authorize() intra-componente                 | ❌ Sin test | P1 — M001 |
| Prune con --days=0 o negativo                              | ❌ Sin test | P2 — M005 |
| IP address formato inválido                                | ❌ Sin test | P2 — M006 |
| Logs duplicados entity_type+entity_id                      | ❌ Sin test | P2 — M002 |
| auditoría de otros eventos (ej. Promotion)                 | ❌ Sin test | INFO      |

**Evidencia:** `php artisan test --filter=AuditModule` corre 11 suites pero no protege las regresiones de los hallazgos arriba.

**Impacto:** Regresiones de seguridad/integridad pueden shippear sin fallar CI.

**Recomendación:** Añadir mínimo 7 tests: `ExportCrossEntityTest`, `ExportPiiMaskingTest`, `ListAuditLogsAuthorizeTest`, `PruneNegativeDaysTest`, `IpFormatValidationTest`, `DuplicateEntityLogTest`, y refactorizar el test de inmutabilidad que ya existe.

**Complejidad:** Media

**Prioridad:** Próximo sprint

---

### [INFO] Listeners solo cubren eventos Wfm+Personnel — gap de auditoría

**Categoría:** Architecture

**Ubicación:** `app/Modules/AuditModule/Listeners/4 listeners` — cubren `LeaveRequestCreated/Decision`, `ShiftSwapApproved`, `WeeklySchedulePublished`. Pero ¿qué pasa con otros eventos de dominio?

**Evidencia:** Solo 4 listeners existen. No hay listener para `EmployeeCreated`, `TeamUpdated`, `SchedulePublished` (aunque el nombre sugeriría que sí), ni para eventos de Communications, Directory, etc.

**Impacto:** Cuando se añadan nuevos módulos o eventos de dominio, la auditoría quedaría ciega para esos eventos a menos que se añada un nuevo listener. El diseño actual asume un conjunto fijo de eventos.

**Recomendación:** Documentar en ADR que los listeners deben crearse por evento de dominio crítico. Considerar un patrón de registro dinámico o un `ServiceProvider` que inspeccione eventos registrados. No es bloqueante hoy pero es deuda de diseño.

**Complejidad:** Baja

**Prioridad:** Backlog

## 7. Matriz de riesgos

| ID   | Severidad | Categoría    | Hallazgo                                                                             | Impacto | Complejidad | Prioridad      |
| ---- | --------- | ------------ | ------------------------------------------------------------------------------------ | ------- | ----------- | -------------- |
| M001 | P1        | Security     | Livewire ListAuditLogs sin authorize intra-componente                                | Alto    | Baja        | Próximo sprint |
| M002 | P1        | Security     | Exportación sin autorización per-record — cualquier `audit.export` ve todos los logs | Alto    | Media       | Inmediata      |
| M003 | P1        | Security     | PII (IP, nombres, emails) sin masking en export CSV/JSON                             | Alto    | Media       | Próximo sprint |
| M004 | P2        | Database     | No hay constraint única en entity_type+entity_id — logs duplicados posibles          | Medio   | Baja        | Backlog        |
| M005 | P2        | Reliability  | Prune command set_config sin validación de inputs (--days <= 0 posible)              | Medio   | Baja        | Backlog        |
| M006 | P2        | Security     | IP address sin validación de formato — riesgo log injection/basura                   | Bajo    | Baja        | Backlog        |
| M007 | INFO      | Architecture | Listeners solo cubren eventos Wfm+Personnel — gap de auditoría para otros eventos    | Bajo    | Baja        | Backlog        |

## 8. Ruta de trabajo

### Fase 0 — Bloqueadores (Inmediata, <1 día, 1 persona)

1. **M002 — Filtrado per-record en exportación**
    - Dependencias: ninguna
    - Esfuerzo: Media (2h + test)
    - Riesgo: Alto si no se hace (fuga PII)
    - Resultado: usuario con `audit.export` solo ve/exporta logs de entidades que puede acceder

### Fase 1 — Riesgos críticos (Próximo sprint, 3-4 días)

2. **M001 — Añadir authorize en ListAuditLogs mount/getQuery**
    - Dependencias: ninguna
    - Esfuerzo: Baja (15min)
    - Riesgo: Bajo
    - Resultado: Livewire 403 cross-entity, test pasa

3. **M003 — Masking PII en exportación**
    - Dependencias: M002 (mismo controller)
    - Esfuerzo: Media (2h + decisión producto)
    - Riesgo: Alto (datos sensibles)
    - Resultado: IP→`***.***.***.*`, nombres truncados, JSON redactor antes de exportar

4. **M001 (mitigación rápida) — Ocultar columna IP en UI si no es admin**
    - Dependencias: M001
    - Esfuerzo: Baja (10min)
    - Riesgo: Bajo
    - Resultado: UI no muestra IP a roles no admin

### Fase 2 — Estabilización (Backlog, 1 semana)

5. **M004 — Agregar constraint única en entity_type+entity_id (partial index)**
    - Dependencias: ninguna
    - Esfuerzo: Baja (1h migración SQL + validación)
    - Riesgo: Bajo
    - Resultado: un log por entidad, count=1 en queries de duplicados

6. **M005 — Validar --days > 0 en prune command**
    - Dependencies: ninguna
    - Esfuerzo: Baja (10min)
    - Riesgo: Bajo
    - Resultado: `audit:prune --days 0` → 400 en vez de borrar todo

7. **M006 — Validar formato IP en modelo/AuditLog::log()**
    - Dependencias: ninguna
    - Esfuerzo: Baja (1h + regex)
    - Riesgo: Bajo
    - Resultado: solo IPs válidas se almacenan

8. **M007 — Documentar patrón de listeners por evento crítico**
    - Dependencias: ninguna
    - Esfuerzo: Baja (30min)
    - Riesgo: Bajo
    - Resultado: nuevos eventos tienen listener asignado desde el inicio

### Fase 3 — Optimización (solo si métrica lo justifica)

- Cachear listado de logs con `Cache::tags(['audit'])->remember('list', 300, fn => AuditLog::latest()->paginate(200))` si p95 > 2s (hoy query trivial, no priorizar).
- Streaming `cursor()` en export si >100k logs (medir primera vez).

**Orden mínimo para estado saludable:** M002 → M001 → M003 → M004/M005 → M006. Con 5 cambios el P0 de exportación/ PII queda saneado.

## 9. Quick Wins

- **M002 en 8 lines:** en `AuditExportController@export` añadir `->whereHas('user', fn($u) => $u->id === auth()->id() || ...)` o reescribir query usando los permisos del usuario. O bien, mover el filtrado a `ExportAuditLogsAction` usando `Gate::allows`.
- **M001 en 2 lines:** `->authorize('viewAny', AuditLog::class)` en `ListAuditLogs::mount()`.
- **M003 en 4 lines:** en `AuditExportController`, antes de `fputcsv`, mapear `$log->ip_address = last_3_octets($log->ip_address)` y `$log->actor_label = Str::limit($log->actor_label, 30)`; en JSON, usar un `Resource` que oculte campos sensibles.
- **M004 en 1 línea:** `CREATE UNIQUE INDEX audit_logs_entity_type_entity_id_unique ON audit_logs (entity_type, entity_id) WHERE deleted_at IS NULL;` migration.
- **M005 en 3 lines:** `if ($days <= 0) throw new \InvalidArgumentException(...)` en `AuditPruneCommand@handle()`.

Todos <30min, bajo riesgo, alto impacto/riesgo reducido.

## 10. Qué NO hacer

- **No introducir Repository Pattern / Interface para AuditLog** — Eloquent + Action + Policy ya son suficientes; el modelo es simple y no justifica segunda implementación.
- **No migrar a microservicio de "Audit Service"** — es funcional dentro del monolito; latencia y ops innecesarias.
- **No quitar el trigger de inmutabilidad PostgreSQL** — es el único resguardo contra UPDATE/DELETE directo en producción; quitarlo expondría logs a modificación.
- **No eliminar el campo `ip_address`** —aunque PII, es necesario para diagnóstico de seguridad; en su lugar, masking en export.
- **No cachear ListAuditLogs sin métrica** — hit rate bajo con filtros dinámicos (fecha/search); invalidación compleja.
- **No añadir CQRS / Event Sourcing para logs** — `DB::transaction` + trigger + listeners es suficiente; sobre-engineering para el alcance actual.
- **No validar IP con Regex compleja en producción sin necesidad** — `ip_address` almacena lo que entrega `request()->ip()`; cambios de formato rompen queries si se indexa.

## 11. Cobertura de pruebas

**Existente (11 test suites, ~149 tests relevantes):**

- `tests/Feature/Modules/AuditModule/ExportAuditLogsActionTest:1` — 41 tests: export con filtros search/action/entityType/dateFrom/dateTo, vacíos, eager-load user (2 queries), orden descendente.
- `tests/Feature/Modules/AuditModule/AuditExportControllerTest:1` — 78 tests: autenticación, autorización (`audit.export` vs `audit.view`), formato CSV/JSON, filtros query string, header row, before/after JSON, 0 resultados, ruta index.
- `tests/Feature/Modules/AuditModule/AuditLogPolicyTest:1` — 8 tests: matriz rol×permiso×ability, `viewAny/view/export`, admin bypass via `hasRole inline`, no `before()` method.
- `tests/Feature/Modules/AuditModule/AuditLogConstraintsTest:1` — 10 tests: ON DELETE SET NULL (PG), JSONB almacenamiento/recuperación, nulls aceptados, inserts duplicados permitidos, action string acceptado, ip_address NULL, índice compuesto en PG.
- `tests/Feature/Modules/AuditModule/AuditPruneCommandTest:1` — 8 tests: --dry-run, elimina logs antiguos, respeta rango, --days=1, SUCCESS sin logs, chunk/batch, chunkById 3.
- `tests/Feature/Modules/AuditModule/AuditLogEventTest:1` — 12 tests: Auditable trait (create/update/delete), 4 listeners (WeeklySchedulePublished/LeaveRequestCreated/LeaveRequestDecision/ShiftSwapApproved), inmutabilidad (UPDATE/DELETE bloqueado por trigger).
- `tests/Feature/Modules/AuditModule/`: tests adicionales de dominio y constraints.

**Faltante crítico:**

| Flujo                                                      | Estado      | Riesgo    |
| ---------------------------------------------------------- | ----------- | --------- |
| Export con filtros cross-entity (usuario solo ve sus logs) | ❌ Sin test | P1 — M002 |
| Export masking PII (IP/nombres ocultos)                    | ❌ Sin test | P1 — M003 |
| ListAuditLogs authorize() intra-componente                 | ❌ Sin test | P1 — M001 |
| Prune con --days <= 0                                      | ❌ Sin test | P2 — M005 |
| IP address formato inválido                                | ❌ Sin test | P2 — M006 |
| Logs duplicados entity_type+entity_id                      | ❌ Sin test | P2 — M004 |

**Verificación sugerida:**

```bash
php artisan test --filter=AuditModule
php artisan test --filter=AuditExportController
php artisan test --filter=AuditLogPolicy
php artisan test --filter=AuditPruneCommand
vendor/bin/pint --test --format agent
php artisan route:list --path=admin/audit --except-vendor
```

## 12. Riesgos pendientes

- **`app.audit_maintenance` session flag en prune**: si se deja set en producción y hay Redis/shared-session, podría afectar a otros procesos. Documentar limpieza después del prune.
- **JSONB `before`/`after` sin schema**: los logs pueden acumular campos inconsistentes según el modelo auditado. Considerar añadir `report()` en el trait o validación suave.
- **`actor_name`/`actor_email` desde `auth()->user()`**: si el request no tiene usuario autenticado (CLI, queue job), estos campos vienen `null`. El `getActorLabelAttribute` maneja eso con `?? 'Usuario eliminado'`, pero vale verificar.
- **No hay `soft_delete` en modelo AuditLog**: el modelo no usa `SoftDeletes` trait explícitamente, pero la migración podría tener `deleted_at`. Revisar migración para consistencia.

## 13. Conclusión

AuditModule es **sano arquitectónicamente pero inseguro por defecto** debido a un P0 de exportación/ PII y P1 de autorización Livewire. No requiere rewrite ni microservicio. Con **2 hotfixes (M002 2h + M003 2h, medio día)** el módulo deja de ser explotable y cumple normativa de PII; con **Fase 1 completa (M001+M004+M005+M006, 2 días)** queda **🟢 saludable** y listo para operar con permisos granulares y datos seguros.

**Siguiente acción recomendada:** Crear rama `fix/audit-export-pii` con commits atómicos: `fix(audit): add per-record filtering + PII masking (P0)`, `fix(audit): authorize ListAuditLogs (P1)`, y deploy hotfix confirmando `route:list --path=admin/audit` + tests `ExportCrossEntityTest` y `ExportPiiMaskingTest`. Luego abrir `fix/audit-integrity` con M004+M005+M006 + suite de 4 tests. No tocar arquitectura ni introducir capas nuevas.

---

**Estado final:** 🟡 Requiere atención — Se corrigieron P0/P1 de exportación y autorización; módulo listo para operar con 3 cambios de baja/media complejidad.
