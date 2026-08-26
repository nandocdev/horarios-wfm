# Análisis de Viabilidad — Modelo DDL Refactorizado (`docs/tmp/Model.md`)

**Fecha:** 2026-08-21
**Autor:** Revisión técnica (fx)
**Documento revisado:** `docs/tmp/Model.md` (22 secciones, ~120 tablas, ~2500 líneas)
**Stack declarado:** PostgreSQL 16 + Laravel 13, `bigserial`, nomenclatura en inglés

---

## 1. Resumen ejecutivo

El modelo propuesto es **ambicioso, coherente en su intención y bien organizado** por secciones de dependencia. Su mayor aporte es la formalización de decisiones de arquitectura que hoy están implícitas o dispersas: la separación `users` (actor/credencial) vs `employees` (sujeto operativo), la jerarquía organizacional dual, y la incorporación de módulos de forecasting, capacity, shrinkage, alertas y un mini-DW de analytics.

**Sin embargo, la viabilidad de implementarlo "tal cual" es baja-mediana** debido a tres categorías de problemas:

1. **Discrepancias irreversibles** entre el DDL propuesto y las migraciones actuales (cambios de targets de FKs, estrategia de IDs, tipos de timestamp).
2. **Inconsistencias internas** del propio documento (campos referenciados que no existen, comentarios que prometen tablas ausentes).
3. **Ruptura de lógica de aplicación existente** que depende de relaciones que el modelo cambia de raíz.

Se recomienda **no migrar de forma big-bang**. El documento debe servir como *blueprint de estado final*, con un plan de migración incremental por módulo.

---

## 2. Alcance del modelo propuesto

El DDL cubre 22 secciones:

| #   | Sección                        | Estado actual en el repo | Observación                                                                                                                                                           |
| --- | ------------------------------ | ------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Core (Auth, RBAC, Jobs, Cache) | ✅ Existe                 | Replica el estándar Laravel + Spatie Permission                                                                                                                       |
| 2   | Settings                       | ⚠️ Parcial                | `app_settings`, `operational_settings` existen; `notification_configs` y `alert_rules` son **nuevos**                                                                 |
| 3   | Geo (Panamá)                   | ⚠️ Parcial                | `provinces` existe pero **sin** `code`; `districts`, `townships` son nuevos                                                                                           |
| 4   | Organization                   | ⚠️ Parcial                | `organizational_units` es **nuevo y central**; `directorates`/`departments`/`positions` existen con diferencias                                                       |
| 5   | Personnel                      | ✅ Casi igual             | `employee_positions`, `team_members`, `skills`, `employee_skills`, `skill_history`, dependents, diseases, disabilities                                                |
| 6   | WFM Core (Schedules)           | ⚠️ Parcial                | Existe pero el propuesto modifica `weekly_schedules` y añade columnas de swap                                                                                         |
| 7   | Intraday & Attendance          | ⚠️ Parcial                | `activity_types`, `intraday_activities`, `attendance_incidents`, `incident_types` existen con diferencias de IDs                                                      |
| 8   | Connect (Cisco)                | ⚠️ Desfasado              | El propuesto **omite** campos añadidos en migraciones de ago-2026 (`node_id`, `contact_type`, `originator_*`, `destination_*`, `original_dialed_number`, `hold_time`) |
| 9   | Operations (Metrics)           | ⚠️ Parcial                | `agent_daily_metrics` propuesta con más columnas; `agent_interval_metrics` nuevo                                                                                      |
| 10  | Forecast & Capacity            | ❌ Nuevo                  | 7 tablas completamente nuevas, sin implementación                                                                                                                     |
| 11  | Quality                        | ⚠️ Parcial                | Existe pero requiere reconciliación de campos                                                                                                                         |
| 12  | Analytics (DW)                 | ❌ Nuevo                  | ~12 tablas (calendar dim, time interval dim, SCD2 snapshot, 6 fact tables)                                                                                            |
| 13  | Alerts & Events                | ❌ Nuevo                  | 2 tablas + dependencia de `alert_rules`                                                                                                                               |
| 14  | Workflow                       | ❌ Nuevo                  | Motor genérico de requests/approvals/delegations (3 tablas)                                                                                                           |
| 15  | Shrinkage                      | ❌ Nuevo                  | 2 tablas                                                                                                                                                              |
| 16  | Communications                 | ✅ Existe                 | Reescrito en DDL limpio, coherente con migraciones                                                                                                                    |
| 17  | Directory                      | ⚠️ Parcial                | El propuesto **elimina** `directory_contacts` e incrusta contacto en `directory_services`                                                                             |
| 18  | Knowledge                      | ✅ Existe                 | Coherente                                                                                                                                                             |
| 19  | Files                          | ✅ Existe                 | Coherente                                                                                                                                                             |
| 20  | Helpdesk                       | ⚠️ FKs cambiadas          | `creator_id`/`assigned_agent_id`/`author_id` pasan de `employees` → `users`                                                                                           |
| 21  | Documentation                  | ✅ Existe                 | Coherente                                                                                                                                                             |
| 22  | Audit                          | ✅ Existe                 | Añade trigger de inmutabilidad                                                                                                                                        |

---

## 3. Problemas críticos (bloqueantes)

### 3.1 Reescritura de targets de Foreign Keys `employees` → `users`

Esta es la decisión de mayor impacto y la **menos documentada en términos de migración**. El DDL propone que todos los "actores" (aprobadores, gestores, supervisores) apunten a `users` en lugar de `employees`:

| Tabla.columna                           | Actual (migración) | Propuesto (DDL) |
| --------------------------------------- | ------------------ | --------------- |
| `employees.parent_id`                   | → `employees`      | → `users`       |
| `teams.supervisor_id`                   | → `employees`      | → `users`       |
| `organizational_units.head_employee_id` | (no existe)        | → `users`       |
| `shift_swap_requests.requester_id`      | → `employees`      | → `users`       |
| `shift_swap_requests.recipient_id`      | → `employees`      | → `users`       |
| `shift_swap_approvals.approver_id`      | → `employees`      | → `users`       |
| `leave_request_approvals.approver_id`   | → `employees`      | → `users`       |
| `temporal_assignments.supervisor_id`    | → `employees`      | → `users`       |
| `helpdesk_tickets.creator_id`           | → `employees`      | → `users`       |
| `helpdesk_tickets.assigned_agent_id`    | → `employees`      | → `users`       |
| `helpdesk_ticket_comments.author_id`    | → `employees`      | → `users`       |

**Riesgo de aplicación:** El modelo `Employee` actual (`app/Modules/PersonnelModule/Models/Employee.php`) contiene lógica de autorización **dependiente de estas relaciones**:

- `manager()` → `belongsTo(Employee::class, 'parent_id')` — se rompe si `parent_id` apunta a `users`.
- `getAllSubordinateIds()` — CTE recursiva sobre `employees.parent_id` → `employees.id`. Cambiar el target a `users.id` rompe la recursión porque `users.id` ≠ `employees.id`.
- `hasCoordinatorRights()` — consulta `Team::where('supervisor_id', $this->id)`. Si `supervisor_id` apunta a `users.id`, la comparación con `$this->id` (employee) es incorrecta.
- `getManagedTeamIds()` — mismo problema; además verifica `$this->user?->hasRole('chief')`, mezclando ambos modelos.

**Recomendación:** La separación actor/sujeto es conceptualmente correcta, pero requiere **antes**:
1. Una migración de datos que traduzca cada `employee_id` actor a su `user_id` correspondivo (y definir política para empleados sin `user_id`).
2. Reescribir toda la lógica de autorización/visibilidad que hoy opera sobre `employees` como si fueran credenciales.
3. Decidir el caso de `organizational_units.head_employee_id`: el nombre sugiere `employees` pero el FK va a `users`. O se renombra la columna a `head_user_id` o se apunta a `employees`. La nota del DDL es contradictoria ("cabeza de la unidad" con FK a `users`).

### 3.2 Inconsistencia de estrategia de IDs (`bigserial` vs ULID)

El DDL estandariza todo a `bigserial`, pero al menos dos tablas existentes usan ULID como PK:

- `attendance_incidents` → `$table->ulid('id')->primary()`
- `queue_daily_metrics` → `$table->ulid('id')->primary()`

Cambiar ULID → bigserial en tablas con datos **no es reversible sin reescribir todas las FKs referenciantes**. Si estas tablas están vacías en producción, el cambio es trivial; si tienen datos, requiere migración de claves.

**Recomendación:** Documentar explícitamente la excepción o planificar la migración de IDs. Unificar a `bigserial` es razonable, pero no debe asumirse "gratuito".

### 3.3 Regresión de tipos de timestamp (`timestampTz` → `timestamp`)

Migraciones existentes del módulo WFM usan `timestampsTz()` (ej. `schedules`, `weekly_schedules`, `weekly_schedule_assignments`, `activity_types`, `intraday_activities`, `shift_swap_requests`, `leave_requests`, `helpdesk_*`).

El DDL propuesto usa `timestamp(0)` **sin zona horaria** en toda la base. Esto es una **regresión funcional**: pérdida de información de offset, comportamiento incorrecto en despliegues multi-zona, y ambigüedad en cambios de horario de verano.

**Recomendación:** Mantener `timestampTz` (`timestamptz`) de forma consistente. Si se desea `timestamp(0)` sin tz, debe ser una decisión consciente documentada, asumiendo que toda la app opera en una sola zona (UTC o America/Panama).

---

## 4. Problemas importantes (no bloqueantes pero requieren decisión)

### 4.1 El DDL no refleja el estado actual de `call_records`

Migraciones de agosto 2026 añadieron a `call_records`: `node_id`, `contact_type`, `originator_type`, `originator_id`, `destination_type`, `destination_id`, `original_dialed_number`, `hold_time`. El DDL propuesto **incluye estos campos** (líneas 1062–1107), pero el `status` tiene `DEFAULT 'pending_operator'` que debe verificarse contra la migración original `2026_04_07_000003`.

**Acción:** Confirmar que el DDL es la suma de todas las migraciones incrementales, no solo la original.

### 4.2 `positions.salary` eliminado sin reemplazo

El DDL elimina `salary` de `positions` (nota línea 374: "ver employee_compensation si se requiere") pero **no existe tabla `employee_compensation`** en ningún lugar del documento. El salario queda solo en `employees.salary`.

**Acción:** O se elimina la referencia a la tabla inexistente, o se define `employee_compensations` (histórico salarial SCD2, coherente con el patrón de `analytics_employee_snapshot`). Quedar a medio camino genera confusión.

### 4.3 `weekly_schedules` pierde unicidad de `week_start_date`

- Actual: `$table->date('week_start_date')->unique()` — una sola weekly_schedule por semana.
- Propuesto: elimina el UNIQUE simple y usa índice parcial `WHERE status = 'published'`.

Esto permite **múltiples drafts por semana**, lo cual es funcionalmente superior, pero es un cambio de invariantes que puede afectar validaciones de aplicación y tests existentes.

**Acción:** Auditar `WeeklySchedule` model y validadores que asuman unicidad de `week_start_date`.

### 4.4 `directory_contacts` eliminado, contacto incrustado en `directory_services`

La migración `2026_08_17_071331` crea `directory_contacts` (tabla separada, 1 unidad → N contactos). Luego `2026_08_17_081103_embed_contact_in_directory_services` mueve esos campos a `directory_services` (rol, extensión, email). El DDL propuesto refleja el estado "post-embed" pero **omite `door_id`** que existe en `directory_units` actual y aparece en `directory_services` del DDL (¿desplazamiento incorrecto?).

**Acción:** Verificar que `door_id` pertenece a `directory_units` o `directory_services` y alinear el DDL con la migración `2026_08_17_074912_move_door_to_directory_services`.

### 4.5 `news.content` degradado de `longText` a `text`

- Actual: `$table->longText('content')` (hasta 4 GB en Postgres `text`).
- Propuesto: `content text NOT NULL`.

En PostgreSQL `text` no tiene límite práctico (igual que `longText` se mapea a `text`), así que **técnicamente equivalente**, pero la semántica del Blueprint se pierde. No es bloqueante; registrar para evitar ruido en revisiones.

### 4.6 Tablas placeholder huérfanas

Existen tres migraciones que crean tablas vacías (`funcionarios`, `relaciones_laborales`, `cargos`) — todas con solo `id` + `timestamps`, sin modelo asociado (grep confirma cero usos en `app/`). El DDL propuesto **no las menciona**.

**Acción:** Decidir si se eliminan (drop migration) o se completan. Su presencia ensucia el schema y confunde el análisis de cobertura del DDL.

---

## 5. Módulos nuevos — viabilidad por bloque

### 5.1 Forecast & Capacity (7 tablas) — ✅ Viable como greenfield
- Sin dependencias de datos existentes (solo FKs a `users`, `call_queues`, `forecast_versions` auto-referenciadas).
- Implementar primero `forecast_groups` → `forecast_versions` → `forecast_scenarios` → `forecast_intervals`, luego capacity.
- Requiere modelo de datos históricos de `call_records`/`queue_daily_metrics` para alimentar `forecast_accuracy`.

### 5.2 Analytics/DW (12 tablas) — ⚠️ Viable pero diferir
- El patrón fact+dim con FK directa a OLTP es válido (evita ETL pesado), pero **duplica datos** de `call_records`, `weekly_schedule_assignments`, `agent_interval_metrics`, `quality_evaluations`, `schedule_exceptions`, `leave_requests`.
- `analytics_employee_snapshot` (SCD2) es el componente más valioso y debería implementarse **antes** que las fact tables, idealmente con observer que dispare en cambios de `employees`.
- Las fact tables solo tienen sentido cuando hay jobs de materialización programados. Sin consumidores de reporting, es schema muerto.
- **Riesgo:** `fact_calls.talk_seconds int2` (smallint, máx 32767) es insuficiente para acumulados. Debería ser `int4`. Mismo problema en `fact_schedule.scheduled_minutes`, `fact_agent_interval.*_seconds`. **Bug de tipo en el DDL.**

### 5.3 Workflow engine (3 tablas) — ✅ Viable, integrar con existentes
- `workflow_requests` (polimórfico `requestable_type/id`) puede unificar `shift_swap_requests` + `leave_requests` + futuros.
- `workflow_delegations` es un añadido de alto valor (delegación de aprobaciones por rango de fechas).
- **Decisión:** ¿reemplaza los workflows específicos existentes o los complementa? El DDL mantiene ambos, lo que puede generar duplicación de lógica de aprobación.

### 5.4 Alerts & Events — ⚠️ Viable tras Settings
- Depende de `alert_rules` (sección 2). Implementar configs primero.
- `alert_events.queue_id varchar(255)` — inconsistente: `queue_id` es FK numérico en `call_queues` pero aquí es `varchar`. Debería ser `int8` con FK o renombrarse a `queue_name`.

### 5.5 Shrinkage (2 tablas) — ✅ Viable y simple
- `historical_shrinkage.source_id varchar(255)` — polimórfico sin FK, coherente con patrón `origin_type/origin_id` ya usado en `schedule_exceptions`.

---

## 6. Inconsistencias internas del documento

| Línea     | Problema                                                            | Sugerencia                                                     |
| --------- | ------------------------------------------------------------------- | -------------------------------------------------------------- |
| 374       | Comentario referencia a `employee_compensation` (tabla inexistente) | Eliminar nota o definir la tabla                               |
| 393       | `organizational_units.head_employee_id` con FK a `users`            | Renombrar a `head_user_id` o apuntar FK a `employees`          |
| 1512      | `quality_queue_criteria.order` — `order` es palabra reservada SQL   | Renombrar a `sort_order` o `display_order`                     |
| 1682–1686 | `fact_calls.talk_seconds int2` (y campos similares)                 | Cambiar a `int4`; smallint desborda en acumulados              |
| 1713–1715 | `fact_schedule.scheduled_minutes int2`                              | Mismo: `int4`                                                  |
| 1733–1737 | `fact_agent_interval.*_seconds int2`                                | Mismo: `int4`                                                  |
| 1761      | `fact_absence.duration_minutes int2`                                | Aceptable (minutos por día < 32767), pero `int4` es más seguro |
| 1842      | `alert_events.queue_id varchar(255)`                                | Debería ser `int8` con FK o renombrar a `queue_name`           |
| 969       | `call_queues.is_quality_evaluable` reemplaza `quality_queues`       | Confirmar que no existe `quality_queues` residual              |

---

## 7. Riesgos de implementación

### 7.1 Dependencias circulares latentes
El DDL afirma estar ordenado por dependencia sin ALTERs circulares, pero `organizational_units` tiene `parent_id` auto-referencial (creable en un `CREATE TABLE`) **y** `head_employee_id` → `users`. Como `users` se crea antes, no hay ciclo. ✅ Correcto. Sin embargo, `employees.organizational_unit_id` referencia `organizational_units` que referencia `users` vía `head_employee_id` — verificar que el orden de creación en migraciones respete esto.

### 7.2 Índices parciales y constraints de exclusión
El uso de `EXCLUDE USING gist` (`intraday_no_overlap`) y `UNIQUE ... WHERE` (partial indexes) ya existe en migraciones actuales y es correcto. Mantener el patrón. El DDL lo refleja bien. ✅

### 7.3 Triggers de immutabilidad en `audit_logs`
El trigger `prevent_audit_log_modification` (línea 2488) es correcto y coherente con la migración `2026_07_30_100000_add_audit_logs_immutability_trigger`. Revisar si esa migración ya lo creó (de ser así, el DDL duplicaría la creación — usar `CREATE OR REPLACE FUNCTION` ya lo maneja, pero el `CREATE TRIGGER` fallaría si existe). Usar `DROP TRIGGER IF EXISTS` antes.

### 7.4 `notifications.data` como `json` vs `jsonb`
El DDL usa `data json NOT NULL` (línea 197). Laravel usa `json` por defecto, pero el resto del schema estandariza `jsonb`. Para consistencia y para habilitar consultas sobre el payload, considerar `jsonb`.

---

## 8. Plan de implementación recomendado

Por módulo, ordenado por dependencia y riesgo creciente:

### Fase 0 — Reconciliación del DDL (sin tocar BD)
1. Corregir las inconsistencias internas (sección 6).
2. Decidir la estrategia de timestamps (`timestampTz` uniforme).
3. Resolver el destino de `positions.salary` / `employee_compensation`.
4. Eliminar o completar tablas placeholder (`funcionarios`, `cargos`, `relaciones_laborales`).
5. Producir un **diff** tabla por tabla entre el DDL y `php artisan migrate:status` + schema real.

### Fase 1 — Módulos nuevos greenfield (sin riesgo a datos existentes)

> **ACTUALIZACIÓN 2026-08-21:** Tras inspección del repo, **todos los módulos de la Fase 1 ya están implementados**:
> - Geo: `provinces`, `districts`, `townships` existen (`database/migrations/2026_03_23_*`). Seeder `PanamaGeographySeeder` con CSVs en `database/data/`.
> - Settings: `notification_configs` en `CoreModule/Database/Migrations/`, `alert_rules` + `alert_events` + `alert_escalations` en `OperationsModule/Database/Migrations/`.
> - Shrinkage: `shrinkage_categories` + `historical_shrinkage` en `AnalyticsModule/Database/Migrations/` con seeder de 8 categorías.
> - Forecast & Capacity: 7 tablas (`forecast_groups`, `forecast_versions`, `forecast_scenarios`, `forecast_intervals`, `forecast_accuracy`, `capacity_plans`, `capacity_intervals`, `capacity_results`, `staffing_requirements`) en `AnalyticsModule/Database/Migrations/`.
> - Workflow: 3 tablas en `WorkflowsModule/Database/Migrations/` con modelos y enums.
>
> El trabajo pendiente son **5 brechas** entre el DDL y la implementación (ver sección 11).

### Fase 2 — Organization (con migración de datos)
- Crear `organizational_units` (auto-referencial, sin dependencia circular).
- Resolver la decisión `head_employee_id` → `users` vs `employees`.
- Poblar desde `directorates`/`departments` existentes vía job/seeder.
- Añadir `employees.organizational_unit_id` como nullable.

### Fase 3 — Reescritura de FKs actor → users (el paso de mayor riesgo)
- **Solo después** de garantizar que todo `employee` actor tiene `user_id` no nulo.
- Migración de datos: para cada tabla afectada, `UPDATE tabla SET columna = (SELECT user_id FROM employees WHERE id = tabla.columna)`.
- Reescribir FKs (drop + recreate).
- Refactorizar lógica de autorización en `Employee` model (`manager()`, `getAllSubordinateIds()`, `hasCoordinatorRights()`, `getManagedTeamIds()`).
- Tests de regresión de visibilidad/permisos.

### Fase 4 — Analytics/DW
- Implementar `analytics_employee_snapshot` (SCD2) con observer.
- Crear fact tables **vacías** con jobs de materialización pendientes.
- Programar jobs `daily` para poblar facts desde OLTP.

### Fase 5 — Alerts & Events
- Tras `alert_rules` de Fase 1.
- Definir consumidores (notificaciones, broadcast).

---

## 9. Verificación sugerida antes de aprobar el modelo

Antes de iniciar la implementación, validar lo siguiente contra la base real:

```bash
# Estado de migraciones
php artisan migrate:status

# Comparar estructura real vs DDL propuesto
php artisan db:table employees
php artisan db:table call_records
php artisan db:table weekly_schedules

# Confirmar que tablas placeholder están vacías
php artisan tinker --execute 'foreach (["funcionarios","cargos","relaciones_laborales"] as $t) { echo "$t: ".DB::table($t)->count()."\n"; }'
```

---

## 10. Conclusión

El modelo propuesto es un **excelente blueprint de estado final** que formaliza decisiones de arquitectura necesarias y añade módulos de valor real (forecast, capacity, analytics, workflow). Su estructura por dependencias es correcta y el uso de features de PostgreSQL (partial indexes, EXCLUDE, tstzrange, SCD2) es apropiado.

**No es implementable como un reemplazo directo** de las migraciones actuales. Los principales obstáculos son: la reescritura de FKs `employees` → `users` (con impacto directo en lógica de autorización), la inconsistencia de IDs (ULID vs bigserial), la regresión de tipos de timestamp, y varios bugs de tipo (`int2` para acumulados) que deben corregirse en el propio documento antes de generar migraciones.

**Recomendación:** Aceptar el DDL como documento de referencia, aplicar las correcciones de la sección 6, y ejecutar el plan por fases de la sección 8, priorizando módulos greenfield antes de tocar relaciones existentes.
