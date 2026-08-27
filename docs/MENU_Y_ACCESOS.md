# Mapa de Navegación y Matriz de Accesos — HorariosWFM

> **Fuente única:** `app/Shared/Helpers/MenuHelper.php:30` `buildItems()` · `filterByPermission():327` · `userCan():357` (OR lógico, `PermissionDoesNotExist` silenciado)
> **Stack:** Laravel 13 + Spatie Permission · `getSidebarItems($user, $counts)` se invoca desde layout con `Auth::user()`
> **Fecha:** 2026-08-27 · **Autor:** TECH_WRITER

> Nota: Si una ruta no aparece en tu sidebar, no es bug de UI — `filterByPermission` la eliminó. Revisa `admin/roles` que tu rol tenga el permiso listado. Los arrays de permisos son **OR** (basta tener uno).

---

## 1. Leyenda de acceso

| Regla en `MenuHelper`                | Quién ve el item                                                                                                                             | Ejemplo                              |
| ------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------ |
| Sin `permission`                     | **Todo usuario autenticado** (`auth` + `verified`)                                                                                           | `Dashboard:35`                       |
| `'permission' => 'X'`                | Solo quien tenga `X` vía `hasPermissionTo()`                                                                                                 | `schedules.view_team:60`             |
| `'permission' => ['A','B']`          | **OR** — basta tener `A` **o** `B`                                                                                                           | `Comunicaciones:158` requiere 1 de 5 |
| Padre con permiso + hijo con permiso | Debe pasar **ambos** filtros (padre visible Y hijo visible). Si todos los hijos se filtran, el padre se oculta (`empty submenu → null:344`). | `Mi Equipo:57`                       |

> Advertencia: `getFooterItems():372` (Configuración / Cerrar Sesión) **no tiene filtro de permiso** — siempre visible.

---

## 2. Matriz completa por sección

### 📊 Dashboard

| Vista     | Ruta (`route`) | Pattern      | Funcionalidad                            | Acceso                 |
| --------- | -------------- | ------------ | ---------------------------------------- | ---------------------- |
| Dashboard | `dashboard`    | `dashboard*` | KPIs resumidos, accesos directos por rol | **Todos** autenticados |

### 🗓 Mi Trabajo — Autogestión del operador

> Sección sin permiso a nivel padre → visible para todos. Es el “portal del agente”.

| Vista                     | Ruta                      | Pattern                    | Funcionalidad (Job to be Done)                                                                                  | Acceso |
| ------------------------- | ------------------------- | -------------------------- | --------------------------------------------------------------------------------------------------------------- | ------ |
| Mi Horario                | `schedules.my-schedule`   | `schedules/my-schedule*`   | Ver tu horario semanal publicado (semana actual + navegación)                                                   | Todos  |
| Mi Jornada                | `schedules.my-day`        | `schedules/my-day*`        | Vista “hoy”: turno, breaks, adherencia en curso                                                                 | Todos  |
| Solicitar Permiso         | `schedules.leave-request` | `schedules/leave-request*` | Crear `LeaveRequest` (vacaciones, permiso médico, personal) → dispara `LeaveRequestCreated` → `WorkflowsModule` | Todos  |
| Solicitar Cambio de Turno | `schedules.swap-request`  | `schedules/swap-request*`  | Crear `ShiftSwapRequest` con compañero → aprobación multinivel                                                  | Todos  |
| Historial de Permisos     | `schedules.leave-history` | `schedules/leave-history*` | Listar mis `LeaveRequests` con estado (pending/approved/rejected)                                               | Todos  |
| Historial de Cambios      | `schedules.swap-history`  | `schedules/swap-history*`  | Listar mis swaps (enviados/recibidos)                                                                           | Todos  |

### 👥 Mi Equipo — Visibilidad de supervisor/coordinador

> Padre requiere `schedules.view_team:60`. Si no lo tienes, **toda la sección desaparece**.

| Vista                   | Ruta                          | Pattern                        | Funcionalidad                                                   | Acceso                                               | Nota                         |
| ----------------------- | ----------------------------- | ------------------------------ | --------------------------------------------------------------- | ---------------------------------------------------- | ---------------------------- |
| Dashboard del Equipo    | `schedules.team-dashboard`    | `schedules/team-dashboard*`    | KPIs agregados del equipo (adherencia, ausentismo)              | `schedules.view_team`                                | Lectura                      |
| Mi Equipo               | `schedules.my-team`           | `schedules/my-team*`           | Roster del equipo, telefonía, estados Finesse                   | `schedules.view_team`                                |                              |
| Aprobar Permisos        | `schedules.manager-approvals` | `schedules/manager-approvals*` | Bandeja de aprobación de `LeaveRequests` del equipo             | `schedules.approve_requests`                         | Más restrictivo que el padre |
| Aprobaciones Pendientes | `workflows.pending`           | `workflows/pending*`           | Inbox genérico `WorkflowRequest` (vacaciones, swaps, otros)     | `workflows.viewAny`                                  | Motor `WorkflowsModule`      |
| Resumen de Solicitudes  | `schedules.request-summary`   | `schedules/reports/requests*`  | Reporte consolidado de solicitudes por período                  | `schedules.view_team`                                |                              |
| Excepciones             | `schedules.exceptions`        | `schedules/exceptions*`        | Ver/gestionar `ScheduleExceptions` (justificaciones, ausencias) | `schedules.view_team` **OR** `wfm.exceptions.manage` | OR: basta uno                |

### 📋 Planificación — WFM Admin / Analista

> Padre: `schedules.view_all:75`. **Solo planners**. 13 sub-vistas.

| Vista                   | Ruta                                   | Pattern                                                    | Funcionalidad                                                               | Acceso                        |
| ----------------------- | -------------------------------------- | ---------------------------------------------------------- | --------------------------------------------------------------------------- | ----------------------------- |
| Planificación Semanal   | `schedules.planning`                   | `schedules/planning*`                                      | Crear/editar `WeeklySchedule` + `WeeklyScheduleAssignments` por día/turno   | `schedules.manage`            |
| Forecast                | `operations.forecast`                  | `operations/forecast*`                                     | Generar `ForecastGroup→Version→Scenario→Intervals` (AnalyticsModule)        | Hereda `schedules.view_all`   |
| Dotación                | `operations.staffing`                  | `operations/staffing*`                                     | Ver `StaffingRequirements` por intervalo                                    | Hereda `schedules.view_all`   |
| Capacidad               | `operations.capacity`                  | `operations/capacity*`                                     | `CapacityPlan` + `CapacityIntervals` → `coverage/gap/skill_gap`             | Hereda `schedules.view_all`   |
| Merma                   | `operations.shrinkage`                 | `operations/shrinkage*`                                    | `HistoricalShrinkage` por categoría                                         | Hereda `schedules.view_all`   |
| Escenarios              | `operations.scenarios`                 | `operations/scenarios*`                                    | Comparar escenarios de forecast (what-if)                                   | Hereda `schedules.view_all`   |
| Turnos Base             | `schedules.shifts`                     | `schedules/shifts*`                                        | CRUD catálogo `Schedules` (nombre, `start_time/end_time`, `break/lunch`)    | `schedules.manage`            |
| Actividades Intradía    | `schedules.intraday-activities.manage` | `schedules/intraday-activities*`                           | Gestionar `IntradayActivities` del día                                      | `wfm.intraday.manage`         |
| Actividades Programadas | `schedules.scheduled-activities`       | `schedules/scheduled-activities*`                          | CRUD `ScheduledActivityDefinitions`                                         | `wfm.catalogs.scheduled_defs` |
| Aprobar Cambios         | `schedules.wfm-approvals`              | `schedules/wfm-approvals*`                                 | Bandeja WFM para aprobar/rechazar `ShiftSwaps` ya pre-aprobados por manager | `wfm.swaps.manage`            |
| Catálogos               | `schedules.activity-types`             | `schedules/activity-types\|absence-reasons\|agent-states*` | CRUD `ActivityTypes`, `AbsenceReasonCodes`, `AgentStates`                   | `wfm.catalogs.activities`     |
| Configuración Operativa | `schedules.operational-settings`       | `schedules/operational-settings*`                          | `OperationalSettings` (umbrales SLA, shrinkage)                             | `wfm.settings.manage`         |

> Nota: `Forecast/Dotación/Capacidad/Merma/Escenarios` no tienen `permission` hijo — las ve **todo planner** con `schedules.view_all`. Si quieres restringir solo a `analytics.view`, añade permiso hijo (ver ADR-0014).

### 🔄 Operación — Torre de Control (Real-time)

> Padre: `operations.view:96`

| Vista            | Ruta                      | Pattern                                 | Funcionalidad                                              | Acceso              |
| ---------------- | ------------------------- | --------------------------------------- | ---------------------------------------------------------- | ------------------- |
| Torre de Control | `operations.dashboard`    | `operations/dashboard\|control-tower*`  | Dashboard NOC: colas, agentes logueados, SLA               | `operations.view`   |
| Tiempo Real      | `operations.realtime`     | `operations/realtime*`                  | Estados Finesse en vivo (`AgentRealtimeStates` polling 5s) | `wfm.realtime.view` |
| Disponibilidad   | `operations.availability` | `operations/availability*`              | Adherencia vs schedule por intervalo                       | `operations.view`   |
| Estado de Colas  | `operations.queues`       | `operations/queues\|queue-performance*` | `CallQueues` + `CsqRealtimeStats` (CUIC)                   | `operations.view`   |
| Intervalos       | `operations.intervals`    | `operations/intervals*`                 | Detalle `AgentIntervalMetrics` 15min                       | `operations.view`   |
| Llamadas en Vivo | `operations.calls`        | `operations/calls*`                     | `CallRecords` en curso                                     | `operations.view`   |

### 📊 Analítica — Reportería avanzada

> Padre: `analytics.view:111`

| Vista                     | Ruta                            | Pattern                          | Funcionalidad                                           | Acceso                                       |
| ------------------------- | ------------------------------- | -------------------------------- | ------------------------------------------------------- | -------------------------------------------- |
| KPIs                      | `operations.kpis`               | `operations/kpis*`               | `DailyKpi` (occupancy, adherence, AHT, shrinkage, FCR)  | `analytics.view`                             |
| Tendencias                | `operations.trends`             | `operations/trends*`             | Series temporales por cola/equipo                       | `analytics.view`                             |
| Skills                    | `operations.skills`             | `operations/skills*`             | Brecha de habilidades (`QueueSkill` vs `EmployeeSkill`) | `analytics.view`                             |
| Comparativos              | `operations.comparison`         | `operations/comparison*`         | Benchmark equipo vs equipo, período vs período          | `analytics.view`                             |
| Explorador de Datos       | `operations.explorer`           | `operations/explorer*`           | Query builder sobre `fact_*`                            | `analytics.view`                             |
| **Desempeño** (sub-grupo) | —                               | —                                | —                                                       | `analytics.view`                             |
| ├─ Scorecard              | `operations.performance`        | `operations/performance*`        | Scorecard histórico por agente/día                      | `analytics.view`                             |
| ├─ Dashboard de Agente    | `operations.agent-performance`  | `operations/agent-performance*`  | Drill-down `AgentPerformanceSummaryDTO`                 | `analytics.view`                             |
| ├─ Resumen por Equipo     | `operations.team-performance`   | `operations/team-performance*`   | Agregado por `team_id`                                  | `analytics.view` **+** `schedules.view_team` |
| └─ Productividad          | `operations.advanced-analytics` | `operations/advanced-analytics*` | `productivity/conformance` (AnalyticsModule)            | `analytics.view`                             |

> Advertencia: `Resumen por Equipo` requiere **ambos** permisos (`analytics.view` del padre + `schedules.view_team` del hijo).

### ⭐ Calidad

> Padre: `quality.evaluations.view:133`

| Vista            | Ruta                         | Pattern                       | Funcionalidad                                               | Acceso                       |
| ---------------- | ---------------------------- | ----------------------------- | ----------------------------------------------------------- | ---------------------------- |
| Evaluaciones     | `quality.evaluations.index`  | `quality/evaluaciones*`       | Listar `Evaluations` con filtros                            | `quality.evaluations.view`   |
| Nueva Evaluación | `quality.evaluations.create` | `quality/evaluaciones/crear*` | Crear evaluación con rúbrica `CriteriaVersion` + `RedFlags` | `quality.evaluations.create` |

### 📞 Centro de Contacto — Módulo Cisco

> Padre: `call_records.viewAny:146`

| Vista         | Ruta                               | Pattern                             | Funcionalidad                                                             | Acceso                        |
| ------------- | ---------------------------------- | ----------------------------------- | ------------------------------------------------------------------------- | ----------------------------- |
| Mi Panel      | `contact-center.agent-dashboard`   | `contact-center/my-dashboard*`      | Vista “agente” — mis `CallRecords` + `AgentCallPerformance`               | Hereda `call_records.viewAny` |
| Panel General | `contact-center.general-dashboard` | `contact-center/general-dashboard*` | Vista supervisor — todas las colas, `fact_calls`                          | Hereda `call_records.viewAny` |
| Llamadas      | `contact-center.calls.index`       | `contact-center/calls*`             | Buscador `CallRecords` (por `phone_number`, `citizen_identifier` cifrado) | `call_records.viewAny`        |

### 📢 Comunicaciones — Red interna

> Padre OR de 5 permisos `news.create|polls.manage|shoutouts.manage|communications.manage|communications.moderate:158`. Basta tener **uno**.

| Vista                   | Ruta                                    | Pattern                            | Funcionalidad                                    | Acceso                    |
| ----------------------- | --------------------------------------- | ---------------------------------- | ------------------------------------------------ | ------------------------- |
| Noticias                | `communications.news.index`             | `admin/communications/news*`       | CRUD `News` (draft → pending_review → published) | `news.create`             |
| Encuestas               | `communications.polls.index`            | `admin/communications/polls*`      | CRUD `Polls` + `PollResponses`                   | `polls.manage`            |
| Reconocimientos         | `communications.shoutouts.index`        | `admin/communications/shoutouts*`  | CRUD `Shoutouts` + `Reactions`                   | `shoutouts.manage`        |
| Categorías              | `communications.admin.categories.index` | `admin/communications/categories*` | CRUD `Categories` (polimórficas)                 | `communications.manage`   |
| Etiquetas               | `communications.admin.tags.index`       | `admin/communications/tags*`       | CRUD `Tags`                                      | `communications.manage`   |
| Moderación de Contenido | `communications.moderation.index`       | `admin/communications/moderation*` | Cola de moderación (news/polls/shoutouts)        | `communications.moderate` |

### 🎫 Soporte — Helpdesk

> Sin permiso padre — visible para todos.

| Vista              | Ruta                  | Pattern                | Funcionalidad                                    | Acceso            |
| ------------------ | --------------------- | ---------------------- | ------------------------------------------------ | ----------------- |
| Mis Tickets        | `helpdesk.my-tickets` | `helpdesk/my-tickets*` | Crear/ver mis `HelpdeskTickets`                  | `helpdesk.view`   |
| Bandeja de Soporte | `helpdesk.manage`     | `helpdesk/manage*`     | Gestionar todos los tickets, asignar agente, SLA | `helpdesk.manage` |

### 📚 Base de Conocimiento

> Padre OR `knowledge.viewAny|knowledge.manage:183`

| Vista                 | Ruta               | Pattern                   | Funcionalidad                         | Acceso              |
| --------------------- | ------------------ | ------------------------- | ------------------------------------- | ------------------- |
| Buscar Artículos      | `knowledge.index`  | `knowledge*`              | Buscar `KnowledgeArticles` publicados | `knowledge.viewAny` |
| Nuevo Artículo        | `knowledge.create` | `admin/knowledge/create*` | Crear artículo (versionado)           | `knowledge.manage`  |
| Administrar Artículos | `knowledge.admin`  | `admin/knowledge*`        | Listar/editar/archivar todos          | `knowledge.manage`  |

### 🏢 Directorio de Unidades

> Padre: `directory.manage:195`

| Vista           | Ruta               | Pattern                   | Funcionalidad                        | Acceso             |
| --------------- | ------------------ | ------------------------- | ------------------------------------ | ------------------ |
| Listar Unidades | `directory.index`  | `admin/directory*`        | Ver `DirectoryBuilding→Unit→Service` | `directory.manage` |
| Nueva Unidad    | `directory.create` | `admin/directory/create*` | Crear unidad + `door_id`, contacto   | `directory.manage` |

### 📊 Reportes

> Padre: `menu.reports:206`

| Vista                | Ruta                         | Pattern                      | Funcionalidad                                 | Acceso                                  |
| -------------------- | ---------------------------- | ---------------------------- | --------------------------------------------- | --------------------------------------- |
| Reportes             | `reports.index`              | `reportes*`                  | Índice genérico de reportes                   | `menu.reports`                          |
| Reporte Diario       | `operations.daily-report`    | `operations/reporte-diario*` | `DailyKpi` + `QueueDailyMetrics` del día      | `menu.reports` **+** `operations.view`  |
| Marco de Reportes    | `operations.reports`         | `operations/reports*`        | Constructor de reportes ad-hoc sobre `fact_*` | `menu.reports` **+** `operations.view`  |
| Reportes de Personal | `personnel.staffing-summary` | `personnel/reports*`         | Resumen dotación vs demanda                   | `menu.reports` **+** `reports.staffing` |

### 📚 Documentación — Wiki del sistema

> Sin permiso padre — visible para todos.

| Vista                 | Ruta                           | Pattern                | Funcionalidad                           | Acceso            |
| --------------------- | ------------------------------ | ---------------------- | --------------------------------------- | ----------------- |
| Artículos             | `documentation.index`          | `docs*`                | Wiki pública `WikiArticles` (published) | Todos             |
| Administrar Artículos | `documentation.admin.articles` | `admin/documentation*` | CRUD wiki + `is_published/sort_order`   | `articles.manage` |

### 🗃 Archivos — Filesystem

> Sin permiso padre — visible para todos.

| Vista                  | Ruta                         | Pattern      | Funcionalidad                   | Acceso |
| ---------------------- | ---------------------------- | ------------ | ------------------------------- | ------ |
| Explorador de Archivos | `filesystem.index`           | `filesystem` | Navegar `Folders/Files`         | Todos  |
| Centro de Descargas    | `filesystem.download-center` | `descargas*` | Descargas masivas, `FileShares` | Todos  |

### ⚙️ Administración — Backoffice

> Padre OR de 12 permisos `employees.view|directorates.viewAny|...|audit.view:239`. Contiene 2 sub-grupos anidados.

#### Personas y Organización

| Vista                   | Ruta                              | Pattern                      | Funcionalidad                        | Acceso                 |
| ----------------------- | --------------------------------- | ---------------------------- | ------------------------------------ | ---------------------- |
| Listar Empleados        | `employees.index`                 | `employees`                  | Ver `Employees`                      | `employees.view`       |
| Crear Empleado          | `employees.create`                | `employees/create*`          | Crear `Employee` + `User` opcional   | `employees.create`     |
| Importar Empleados      | `employees.import`                | `employees/import*`          | `EmployeeImportBatches` CSV masivo   | `employees.import`     |
| Asignaciones de Equipos | `employees.teams.manage`          | `employees/teams/manage*`    | `TeamMembers` historial              | `teams.members.manage` |
| Direcciones             | `organization.directorates.index` | `organization/directorates*` | CRUD `Directorates`                  | `directorates.viewAny` |
| Departamentos           | `organization.departments.index`  | `organization/departments*`  | CRUD `Departments`                   | `departments.viewAny`  |
| Cargos                  | `organization.positions.index`    | `organization/positions*`    | CRUD `Positions`                     | `positions.viewAny`    |
| Equipos                 | `organization.teams.index`        | `organization/teams*`        | CRUD `Teams` + `supervisor_id→users` | `teams.viewAny`        |
| Ubicaciones             | `location.index`                  | `location*`                  | `Provinces→Districts→Townships`      | `directorates.viewAny` |
| Usuarios                | `users.index`                     | `admin/users*`               | CRUD `Users` + `Roles`               | `users.view`           |
| Roles y Permisos        | `roles.index`                     | `admin/roles*`               | CRUD `Roles/Permissions` (Spatie)    | `roles.view`           |

#### Catálogos y Sistema

| Vista                      | Ruta                                  | Pattern                             | Funcionalidad                                | Acceso                  |
| -------------------------- | ------------------------------------- | ----------------------------------- | -------------------------------------------- | ----------------------- |
| Criterios de Evaluación    | `quality.criteria.index`              | `quality/criterios`                 | CRUD `QualityCriteria`                       | `quality.criteria.view` |
| Criterios por Cola         | `quality.queues.criteria`             | `quality/colas/criterios*`          | `QualityQueueCriteria` mapping               | `quality.criteria.view` |
| Colas de Evaluación        | `quality.queues.index`                | `quality/colas`                     | `CallQueues` marcadas `is_quality_evaluable` | `quality.queues.manage` |
| Colas del Contact Center   | `contact-center.admin.queues.index`   | `contact-center/catalogs/queues*`   | CRUD `CallQueues` + `Channel`, `QueueSkill`  | `call_queues.manage`    |
| Canales                    | `contact-center.admin.channels.index` | `contact-center/catalogs/channels*` | CRUD `Channels`                              | `channels.manage`       |
| Subtipos de Caso           | `contact-center.admin.subtypes.index` | `contact-center/catalogs/subtypes*` | CRUD `CaseSubtypes`                          | `case_subtypes.manage`  |
| Cuotas de Almacenamiento   | `filesystem.quotas`                   | `filesystem/quotas*`                | `StorageQuotas`                              | `admin.system`          |
| Mantenimiento del Sistema  | `admin.system.maintenance`            | `admin/system/maintenance*`         | `cache:monitor --prune`, `horizon:terminate` | `admin.system`          |
| Auditoría                  | `audit.index`                         | `admin/audit*`                      | `AuditLogs` inmutables + exportación         | `audit.view`            |
| Notificaciones del Sistema | `admin.notifications`                 | `admin/notifications*`              | `NotificationConfigs` + `AlertRules`         | `admin.system`          |

### Footer — Siempre visible

| Vista         | Ruta           | Funcionalidad                | Acceso |
| ------------- | -------------- | ---------------------------- | ------ |
| Configuración | `profile.edit` | Editar perfil, 2FA, password | Todos  |
| Cerrar Sesión | `logout`       | `POST logout`                | Todos  |

---

## 3. Roles sugeridos (mapeo permiso → rol negocio)

| Rol negocio                  | Permisos clave                                                                         | Secciones que verá                                                    |
| ---------------------------- | -------------------------------------------------------------------------------------- | --------------------------------------------------------------------- |
| **Agente / Operador**        | _(ninguno especial)_                                                                   | Dashboard, Mi Trabajo, Soporte (Mis Tickets), Documentación, Archivos |
| **Coordinador / Supervisor** | `schedules.view_team`, `schedules.approve_requests`, `workflows.viewAny`               | + Mi Equipo completo                                                  |
| **WFM Analyst**              | `schedules.view_all`, `schedules.manage`, `wfm.*`, `analytics.view`, `operations.view` | + Planificación, Operación, Analítica, Reportes                       |
| **Calidad**                  | `quality.evaluations.view`, `quality.evaluations.create`                               | + Calidad                                                             |
| **Contact Center Admin**     | `call_records.viewAny`, `call_queues.manage`, `channels.manage`                        | + Centro de Contacto, Catálogos de colas                              |
| **Comunicaciones**           | `news.create`, `polls.manage`, `shoutouts.manage`                                      | + Comunicaciones                                                      |
| **Admin Sistema**            | `admin.system`, `audit.view`, `users.view`, `roles.view`, `employees.*`                | + Administración completa                                             |

---

## 4. Deuda detectada en el menú

1. **Inconsistencia `Planificación:Forecast`** — cualquier `schedules.view_all` ve forecast aunque no tenga `analytics.view`. Ver ADR-0014.
2. **Directorio crea sin permiso separado** — `directory.create:198` hereda `directory.manage`.
3. **Footer sin `markActive` dinámico** — `getFooterItems():372` usa `request()->routeIs()` solo para Configuración.

---

_Generado automáticamente desde `MenuHelper.php`. No editar a mano — regenerar tras cambiar permisos._
