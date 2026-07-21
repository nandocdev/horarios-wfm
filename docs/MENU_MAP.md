# Mapa de Menú del Sistema — horarios-wfm

> **Propósito:** Describir cada entrada del menú con su funcionalidad, ruta y
> componente asociado, e identificar funcionalidades coincidentes que puedan
> centralizarse o simplificarse.

---

## Convenciones

- `[P: permiso]` — Entrada protegida por permiso; visible solo si el usuario lo posee.
- `(C)` — Controlador Blade tradicional (no Livewire).
- La navegación se genera desde `MenuHelper.php` que alimenta `sidebar.blade.php`.

---

## 1. 📊 Dashboard

| Entrada       | Ruta             | Componente                            | Módulo     | ¿Qué hace?                                                                                                                                                                                                                                                                                               |
| ------------- | ---------------- | ------------------------------------- | ---------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Dashboard** | `GET /dashboard` | `OperationsModule\Livewire\Dashboard` | Operations | Panel principal con KPIs (programados, conectados, ausentes, cobertura), alertas de SLA por cola, distribución de estados, tarjetas de cobertura por hora, puntajes por equipo, incidentes de asistencia, tendencias. Muestra diferente contenido según rol (admin→todo, manager→equipo, operador→self). |

**Solapamiento:** Existe una segunda ruta `GET /operations/dashboard` que apunta al **mismo componente** `Dashboard`. La ruta está registrada pero no aparece en el menú. Debe eliminarse `operations.dashboard`.

---

## 2. 🗓 Mi Trabajo

| Entrada                       | Ruta                                        | Componente                                       | Módulo | ¿Qué hace?                                                                                                                                                                                              |
| ----------------------------- | ------------------------------------------- | ------------------------------------------------ | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Mi Horario**                | `GET /schedules/my-schedule/{week?}/{day?}` | `WfmModule\Livewire\MySchedule`                  | WFM    | Visor semanal de turnos del empleado autenticado. Muestra asignaciones por día, excepciones y actividades intradía. Selector de semana y día.                                                           |
| **Mi Día**                    | `GET /schedules/my-day`                     | `WfmModule\Livewire\MyDay`                       | WFM    | Jornada del día actual en tiempo real. Muestra estado actual del agente, entrada real vs programada, desglose de tiempos (talk, ready, ACW), llamadas atendidas, SLA, adherencia y eventos próximos.    |
| **Mis Métricas**              | `GET /schedules/my-metrics`                 | `WfmModule\Livewire\MyMetrics`                   | WFM    | Métricas detalladas día por día del empleado autenticado. Desglose de tiempos, estadísticas de llamadas, rendimiento por cola, KPIs generales, shrinkage, transiciones de estado. Navegación por fecha. |
| **Solicitar Permiso**         | `GET /schedules/leave-request/{type?}`      | `WfmModule\Livewire\RequestLeave`                | WFM    | Formulario de solicitud de permiso. Soporta tipos `quarterly` (con balance, tope 8h/trimestre) y `compensatory`. Valida contra asignaciones de horario.                                                 |
| **Solicitar Cambio de Turno** | `GET /schedules/swap-request`               | `WfmModule\Livewire\RequestShiftSwap`            | WFM    | Formulario de solicitud de intercambio de turno. Selecciona rango de fechas y compañero (mismo cargo, turno diferente). Valida que ambos tengan asignaciones. Envía notificación al destinatario.       |
| **Mis Solicitudes**           | `GET /schedules/swap-history`               | `WfmModule\Livewire\SwapRequestHistory`          | WFM    | Historial de solicitudes de intercambio del usuario (como solicitante o destinatario). Paginado con badges de estado. Acciones: aceptar/rechazar/cancelar. Modal con detalle de horarios.               |
| **Notificaciones**            | `GET /notifications`                        | `CoreModule\Livewire\Shared\NotificationHistory` | Core   | Lista paginada de notificaciones del usuario. Marcar como leídas (individual/todas).                                                                                                                    |

---

## 3. 👥 Mi Equipo `[P: schedules.view_team]`

| Entrada                    | Ruta                               | Componente                            | Módulo                                | ¿Qué hace?                                                                                                                                                                                                          |
| -------------------------- | ---------------------------------- | ------------------------------------- | ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Mi Equipo**              | `GET /schedules/my-team`           | `WfmModule\Livewire\MyTeam`           | WFM                                   | Vista del equipo para supervisores/chiefs. Muestra miembros, asignaciones semanales, excepciones, permisos pendientes, intercambios recientes, creación inline de incidencias. Selector de equipo para power users. |
| **Aprobar Permisos**       | `GET /schedules/manager-approvals` | `WfmModule\Livewire\ManagerApprovals` | WFM `[P: schedules.approve_requests]` | Bandeja de aprobación de permisos de subordinados. Aprobar/rechazar.                                                                                                                                                |
| **Resumen de Solicitudes** | `GET /schedules/reports/requests`  | `WfmModule\Livewire\RequestSummary`   | WFM `[P: reports.requests]`           | Panel resumen de solicitudes con conteos por estado (total/pendientes/aprobados/rechazados) y distribución por tipo de permiso.                                                                                     |

---

## 4. 📋 Planificación `[P: schedules.view_all]`

| Entrada                      | Ruta                                        | Componente                                     | Módulo                                 | ¿Qué hace?                                                                                                                                                                               |
| ---------------------------- | ------------------------------------------- | ---------------------------------------------- | -------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Planificación Semanal**    | `GET /schedules/planning`                   | `WfmModule\Livewire\WeeklyPlanning`            | WFM `[P: schedules.manage]`            | Gestión de planificación semanal. Lista semanas con conteos de asignaciones por equipo. Crear nuevas semanas, publicar borradores. Punto de entrada para planificar por equipo/empleado. |
| **Turnos Base**              | `GET /schedules/shifts`                     | `WfmModule\Livewire\ManageSchedules`           | WFM `[P: schedules.manage]`            | CRUD de definiciones de turnos base (nombre, hora inicio/fin, almuerzo, descanso, total minutos).                                                                                        |
| **Actividades Intradía**     | `GET /schedules/intraday-activities/manage` | `WfmModule\Livewire\ManageIntradayActivities`  | WFM `[P: wfm.intraday.manage]`         | Componente dual-role: WFM define períodos aprobados (equipo + fecha + rango + cupo máximo). Coordinadores asignan operadores en esos cupos.                                              |
| **Actividades Programadas**  | `GET /schedules/scheduled-activities`       | `WfmModule\Livewire\ManageScheduledActivities` | WFM `[P: wfm.catalogs.scheduled_defs]` | CRUD de definiciones de actividades programadas (nombre, tipo de actividad, color, activo).                                                                                              |
| **Excepciones de Horario**   | `GET /schedules/exceptions`                 | `WfmModule\Livewire\ManageScheduleExceptions`  | WFM `[P: wfm.exceptions.manage]`       | Gestión de excepciones/incidencias de horario. Buscar por empleado, filtrar por fecha, motivo, estado. CRUD con modal.                                                                   |
| **Tipos de Actividad**       | `GET /schedules/activity-types`             | `WfmModule\Livewire\ManageActivityTypes`       | WFM `[P: wfm.catalogs.activities]`     | CRUD de catálogo de tipos de actividad (ej: entrenamiento, reunión, coaching).                                                                                                           |
| **Motivos de Ausencia**      | `GET /schedules/absence-reasons`            | `WfmModule\Livewire\ManageAbsenceReasons`      | WFM `[P: wfm.catalogs.absences]`       | CRUD de catálogo de códigos de motivo de ausencia.                                                                                                                                       |
| **Estados de Agente**        | `GET /schedules/agent-states`               | `WfmModule\Livewire\ManageAgentStates`         | WFM `[P: wfm.catalogs.agent_states]`   | CRUD de catálogo de estados de agente (READY, TALKING, NOT_READY, etc.).                                                                                                                 |
| **Aprobar Cambios de Turno** | `GET /schedules/wfm-approvals`              | `WfmModule\Livewire\WfmSwapApprovals`          | WFM `[P: wfm.swaps.manage]`            | Panel de aprobación WFM de intercambios. Tabs: pendientes (incluye aceptados-por-par) e historial. Aprobar/rechazar con motivo. Los aprobados se aplican automáticamente.                |

---

## 5. 🔄 Operaciones `[P: operations.view]`

| Entrada                        | Ruta                                            | Componente                                                | Módulo                                | ¿Qué hace?                                                                                                                                                                                                                                     |
| ------------------------------ | ----------------------------------------------- | --------------------------------------------------------- | ------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Monitoreo en Tiempo Real**   | `GET /operations/realtime`                      | `OperationsModule\Livewire\RealtimeMonitoring`            | Operations                            | Grid de agentes en tiempo real con filtros por equipo, cargo, cola, estado, motivo. Muestra estado actual, duración, info de cola. Auto-refresh. Resumen de distribución de estados.                                                           |
| **Reporte Diario**             | `GET /operations/reporte-diario`                | `OperationsModule\Livewire\DailyReport`                   | Operations                            | Reporte operativo diario con dos vistas: operador (KPIs individuales) y equipo. Navegación por fecha. Muestra adherencia, productividad, utilización, estadísticas de llamadas.                                                                |
| **Disponibilidad Intradía**    | `GET /operations/availability`                  | `OperationsModule\Livewire\IntradayAvailability`          | Operations                            | Dashboard de disponibilidad intradía. Compara agentes programados vs conectados por franja horaria. Muestra adherencia, desglose talking/ready/not-ready y períodos de riesgo. Auto-refresh 10s.                                               |
| **Desempeño por Cola**         | `GET /operations/queue-performance`             | `OperationsModule\Livewire\QueuePerformanceReport`        | Operations                            | Reporte de rendimiento por cola con volumen de llamadas, SLA, AHT y otras métricas por fecha.                                                                                                                                                  |
| **Scorecard de Desempeño**     | `GET /operations/performance`                   | `OperationsModule\Livewire\PerformanceScorecard`          | Operations                            | Scorecard estandarizado de desempeño para agentes. Filtros persistentes en URL (fecha, empleado, equipo, período). Búsqueda.                                                                                                                   |
| **Dashboard de Agente**        | `GET /operations/agent-performance/{employee?}` | `OperationsModule\Livewire\AgentPerformanceDashboard`     | Operations                            | Vista detallada de desempeño de un agente. Día configurable. Supervisores pueden seleccionar empleados.                                                                                                                                        |
| **Dashboard de Productividad** | `GET /operations/advanced-analytics`            | `OperationsModule\Livewire\AdvancedProductivityDashboard` | Operations                            | Analítica avanzada de productividad usando AgentDailyMetric. Muestra PWI promedio, disponibilidad, AHT, ocupación, top/bottom performers. Filtro por equipo.                                                                                   |
| **Resumen por Equipo**         | `GET /operations/team-performance`              | `OperationsModule\Livewire\TeamPerformanceSummary`        | Operations `[P: schedules.view_team]` | Resumen de rendimiento por equipo. Tabla ordenable con utilización, productividad, talk time, adherencia. Drill-down a detalle del operador. Selector de fecha.                                                                                |
| **Marco de Reportes**          | `GET /operations/reports`                       | `OperationsModule\Livewire\ReportingFrameworkIndex`       | Operations                            | Hub central de reportes que organiza los informes disponibles en 4 capas: WFM Core (adherencia/cobertura), Productividad Operativa (PWI/scorecard), Rendimiento por Cola (SLA), Ausentismo (permisos/incapacidades). Enlace a exportación PDF. |

> **Nota:** `ReportingFrameworkIndex` actúa como un meta-navegador / tabla de contenidos que enlaza a los demás reportes.

---

## 6. ⭐ Calidad `[P: quality.evaluations.view]`

| Entrada                | Ruta                              | Componente                                      | Módulo                                    | ¿Qué hace?                                                                                                                   |
| ---------------------- | --------------------------------- | ----------------------------------------------- | ----------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| **Evaluaciones**       | `GET /quality/evaluaciones`       | `QualityModule\Livewire\EvaluationIndex`        | Quality                                   | Lista paginada de evaluaciones de calidad. Filtros por cola, equipo, empleado, rango de fechas, estado. Columnas ordenables. |
| **Nueva Evaluación**   | `GET /quality/evaluaciones/crear` | `QualityModule\Livewire\TeamEvaluationSelector` | Quality `[P: quality.evaluations.create]` | Seleccionar equipo → empleado para iniciar evaluación. Muestra conteo de evaluaciones del empleado en la semana actual.      |
| **Criterios**          | `GET /quality/criterios`          | `QualityModule\Livewire\CriteriaList`           | Quality `[P: quality.criteria.view]`      | Lista de criterios de evaluación de calidad.                                                                                 |
| **Criterios por Cola** | `GET /quality/colas/criterios`    | `QualityModule\Livewire\ManageQueueCriteria`    | Quality `[P: quality.criteria.view]`      | Asignar criterios a colas. Selector de cola, agregar/quitar criterios inline, versionado, reordenar, activar/desactivar.     |
| **Colas**              | `GET /quality/colas`              | `QualityModule\Livewire\QueueList`              | Quality `[P: quality.queues.manage]`      | CRUD de definiciones de colas de calidad (código, nombre, descripción, activo).                                              |

---

## 7. 📞 Centro de Contacto `[P: call_records.viewAny]`

| Entrada              | Ruta                                    | Componente                                | Módulo                              | ¿Qué hace?                                                                                                                      |
| -------------------- | --------------------------------------- | ----------------------------------------- | ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| **Llamadas**         | `GET /contact-center/calls`             | `ConnectModule\Livewire\ListCallRecords`  | Connect                             | Lista paginada de registros de llamadas. Filtros por estado, rango de fechas, empleado, cola, canal, subtipo. Modal de detalle. |
| **Colas**            | `GET /contact-center/catalogs/queues`   | `ConnectModule\Livewire\ListCallQueues`   | Connect `[P: call_queues.manage]`   | CRUD de catálogo de colas de llamada (nombre, código, activo, meta AHT).                                                        |
| **Canales**          | `GET /contact-center/catalogs/channels` | `ConnectModule\Livewire\ListChannels`     | Connect `[P: channels.manage]`      | CRUD de catálogo de canales de comunicación (nombre, código, activo).                                                           |
| **Subtipos de Caso** | `GET /contact-center/catalogs/subtypes` | `ConnectModule\Livewire\ListCaseSubtypes` | Connect `[P: case_subtypes.manage]` | CRUD de subtipos de caso con asociación a cola.                                                                                 |

---

## 8. 📢 Comunicaciones

| Entrada             | Ruta                                  | Componente                                    | Módulo                                 | ¿Qué hace?                                                                                                                                                                        |
| ------------------- | ------------------------------------- | --------------------------------------------- | -------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Inicio**          | `GET /`                               | `CommunicationsModule\Livewire\Home`          | Communications                         | Landing page principal. Muestra últimas noticias, shoutouts (reconocimientos) con reacciones, encuestas activas con votación, comentarios en noticias. Modal para crear shoutout. |
| **Noticias**        | `GET /admin/communications/news`      | `CommunicationsModule\Livewire\ListNews`      | Communications `[P: news.create]`      | CRUD admin de artículos de noticias. Búsqueda, paginación, borrado.                                                                                                               |
| **Encuestas**       | `GET /admin/communications/polls`     | `CommunicationsModule\Livewire\ListPolls`     | Communications `[P: polls.manage]`     | CRUD admin de encuestas. Búsqueda, paginación, borrado.                                                                                                                           |
| **Reconocimientos** | `GET /admin/communications/shoutouts` | `CommunicationsModule\Livewire\ListShoutouts` | Communications `[P: shoutouts.manage]` | CRUD admin de shoutouts. Búsqueda, paginación, borrado.                                                                                                                           |

---

## 9. 🎫 Soporte `[P: helpdesk.view]`

| Entrada                  | Ruta                       | Componente                              | Módulo                             | ¿Qué hace?                                                                                                                                       |
| ------------------------ | -------------------------- | --------------------------------------- | ---------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Mis Tickets**          | `GET /helpdesk/my-tickets` | `HelpdeskModule\Livewire\MyTickets`     | Helpdesk                           | Lista de tickets del usuario con formulario de creación. Asunto, descripción, categoría, prioridad. Historial paginado.                          |
| **Bandeja de Soporte**   | `GET /helpdesk/manage`     | `HelpdeskModule\Livewire\ManageTickets` | Helpdesk `[P: helpdesk.manage]`    | Gestión de tickets para agentes de soporte. Filtros por estado, categoría, prioridad, SLA. Búsqueda. Acción de auto-asignación.                  |
| **Base de Conocimiento** | `GET /knowledge`           | `KnowledgeModule\Livewire\OperatorView` | Knowledge `[P: knowledge.viewAny]` | Navegador de base de conocimiento para operadores. Búsqueda por palabra clave, filtro por cola, categoría y tags. Detalle del artículo por slug. |

---

## 10. 📚 Documentación

| Entrada                   | Ruta                                | Componente                                              | Módulo                               | ¿Qué hace?                                                                                                             |
| ------------------------- | ----------------------------------- | ------------------------------------------------------- | ------------------------------------ | ---------------------------------------------------------------------------------------------------------------------- |
| **Artículos**             | `GET /docs`                         | `DocumentationModule\Livewire\Public\WikiArticleIndex`  | Documentation                        | Listado público de artículos wiki. Búsqueda y filtro por categoría. Detalle por slug.                                  |
| **Administrar Artículos** | `GET /admin/documentation/articles` | `DocumentationModule\Livewire\Admin\ManageWikiArticles` | Documentation `[P: articles.manage]` | CRUD admin de artículos wiki. Modal de creación/edición con título, contenido, categoría, tags, estado de publicación. |

---

## 11. 🗃 Archivos

| Entrada                      | Ruta                     | Componente                                 | Módulo                         | ¿Qué hace?                                                                                                                                                                                  |
| ---------------------------- | ------------------------ | ------------------------------------------ | ------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Explorador de Archivos**   | `GET /filesystem`        | `FilesystemModule\Livewire\FileBrowser`    | Filesystem                     | Gestor de archivos completo con árbol de carpetas, subida multi-archivo, creación de carpetas, compartir con usuarios/roles, búsqueda, modos de vista (mis archivos / compartidos conmigo). |
| **Centro de Descargas**      | `GET /descargas`         | `FilesystemModule\Livewire\DownloadCenter` | Filesystem                     | Centro de descargas público listando archivos públicos. Búsqueda por nombre.                                                                                                                |
| **Cuotas de Almacenamiento** | `GET /filesystem/quotas` | `FilesystemModule\Livewire\QuotaManager`   | Filesystem `[P: admin.system]` | Gestión admin de cuotas de almacenamiento por rol o por usuario.                                                                                                                            |

---

## 12. ⚙️ Administración

### Empleados `[P: employees.view]`

| Entrada                | Ruta                    | Componente                                              | Módulo    |
| ---------------------- | ----------------------- | ------------------------------------------------------- | --------- |
| **Listar Empleados**   | `GET /employees`        | `EmployeeController@index` (C)                          | Personnel |
| **Crear Empleado**     | `GET /employees/create` | `EmployeeController@create` (C)                         | Personnel |
| **Importar Empleados** | `GET /employees/import` | `EmployeeController@import` (C) `[P: employees.import]` | Personnel |

### Organigrama `[P: directorates.viewAny]`

| Entrada           | Ruta                             | Componente                                     | Módulo       |
| ----------------- | -------------------------------- | ---------------------------------------------- | ------------ |
| **Direcciones**   | `GET /organization/directorates` | `OrganizationModule\Livewire\ListDirectorates` | Organization |
| **Departamentos** | `GET /organization/departments`  | `OrganizationModule\Livewire\ListDepartments`  | Organization |
| **Cargos**        | `GET /organization/positions`    | `OrganizationModule\Livewire\ListPositions`    | Organization |

### Otras entradas de Administración

| Entrada                       | Ruta                                   | Componente                                 | Módulo         | Permiso                   |
| ----------------------------- | -------------------------------------- | ------------------------------------------ | -------------- | ------------------------- |
| **Equipos**                   | `GET /organization/teams`              | `PersonnelModule\Livewire\ListTeams`       | Personnel      | `teams.viewAny`           |
| **Ubicaciones**               | `GET /location`                        | `LocationController@index` (C)             | Geo            | —                         |
| **Usuarios**                  | `GET /admin/users`                     | `CoreModule\Livewire\Users\ListUsers`      | Core           | `users.view`              |
| **Roles y Permisos**          | `GET /admin/roles`                     | `CoreModule\Livewire\Roles\ListRoles`      | Core           | `roles.view`              |
| **Configuración Operativa**   | `GET /schedules/operational-settings`  | `WfmModule\Livewire\OperationalSettings`   | WFM            | `wfm.settings.manage`     |
| **Auditoría**                 | `GET /admin/audit`                     | `AuditModule\Livewire\ListAuditLogs`       | Audit          | `audit.view`              |
| **Categorías y Etiquetas**    | `GET /admin/communications/categories` | `CategoryController@index` (C)             | Communications | `communications.manage`   |
| **Moderación de Contenido**   | `GET /admin/communications/moderation` | `ContentModerationController@index` (C)    | Communications | `communications.moderate` |
| **Reportes de Personal**      | `GET /personnel/reports/staffing`      | `PersonnelModule\Livewire\StaffingSummary` | Personnel      | `reports.staffing`        |
| **Mantenimiento del Sistema** | `GET /admin/system/maintenance`        | `CoreModule\Livewire\SystemMaintenance`    | Core           | `admin.system`            |

---

## Análisis de Solapamientos y Centralización

### 🔴 Hallazgos Críticos (acción recomendada inmediata)

#### H1. Ruta duplicada del Dashboard

```
GET /dashboard       → OperationsModule\Livewire\Dashboard  ← en el menú
GET /operations/dashboard → OperationsModule\Livewire\Dashboard  ← NO en el menú
```

**Ambas apuntan al mismo componente.** La ruta `operations.dashboard` existe en `OperationsModule/Routes/web.php` y es funcionalmente idéntica a `dashboard`. Debe eliminarse la segunda para evitar confusión y doble indexación.

---

### 🟡 Funcionalidades Candidatas a Centralización

#### C1. Catálogos CRUD (6 componentes casi idénticos)

| Componente                      | Ubicación                    | Entradas en menú        |
| ------------------------------- | ---------------------------- | ----------------------- |
| `ManageActivityTypes`           | WFM → Planificación          | Tipos de Actividad      |
| `ManageAbsenceReasons`          | WFM → Planificación          | Motivos de Ausencia     |
| `ManageAgentStates`             | WFM → Planificación          | Estados de Agente       |
| `ManageSchedules` (Turnos Base) | WFM → Planificación          | Turnos Base             |
| `ManageScheduledActivities`     | WFM → Planificación          | Actividades Programadas |
| `ListCallQueues`                | Connect → Centro de Contacto | Colas                   |
| `ListChannels`                  | Connect → Centro de Contacto | Canales                 |
| `ListCaseSubtypes`              | Connect → Centro de Contacto | Subtipos de Caso        |
| `ListTeams`                     | Personnel → Admin            | Equipos                 |
| `QueueList`                     | Quality → Calidad            | Colas (Calidad)         |
| `CriteriaList`                  | Quality → Calidad            | Criterios               |
| `ManageQueueCriteria`           | Quality → Calidad            | Criterios por Cola      |
| `ListDirectorates`              | Organization → Admin         | Direcciones             |
| `ListDepartments`               | Organization → Admin         | Departamentos           |
| `ListPositions`                 | Organization → Admin         | Cargos                  |
| `ListNews`                      | Communications               | Noticias (admin)        |
| `ListPolls`                     | Communications               | Encuestas (admin)       |
| `ListShoutouts`                 | Communications               | Reconocimientos (admin) |
| `ManageWikiArticles`            | Documentation                | Administrar Artículos   |
| `ManageKnowledgeArticles`       | Knowledge                    | (no visible en menú)    |

**Patrón común:** CRUD con tabla paginada, modal/botón de creación, búsqueda por nombre, activo/inactivo. Al menos 20 componentes que podrían simplificarse con un `CrudCatalog` trait o un único componente genérico configurable por atributos (columns, fields, filters). Se estima un ahorro de ~60% del código duplicado.

---

#### C2. Base de Conocimiento vs Wiki de Documentación

| Aspecto          | `KnowledgeModule`                                                    | `DocumentationModule`                 |
| ---------------- | -------------------------------------------------------------------- | ------------------------------------- |
| Usuario target   | Operador en piso de llamadas                                         | Público general interno               |
| URL              | `/knowledge`                                                         | `/docs`                               |
| Componente view  | `OperatorView`                                                       | `WikiArticleIndex`                    |
| Componente admin | `ManageKnowledgeArticles`                                            | `ManageWikiArticles`                  |
| Modelo           | `KnowledgeArticle` + categorías + tags + colas + versiones           | `WikiArticle` + categorías + tags     |
| Funcionalidad    | Asociación a colas, versionado semántico, prioridad, filtro por cola | Categorías planas, publicación simple |

**Son casos de uso distintos** — KnowledgeModule es operativo (artículos vinculados a colas del call center), DocumentationModule es una wiki institucional. **No deben fusionarse**, pero podrían compartir un componente de visualización de artículos (vista pública) y un componente admin CRUD de contenido con estado de publicación, ya que la estructura es similar.

**Propuesta:** Crear un trait `HasContentPublishing` (status, published_at, author_id) y un componente `ArticleViewer` reutilizable que ambos módulos puedan instanciar con sus propios modelos y relaciones.

---

#### C3. Módulo de Calidad — Evaluaciones

| Componente               | Ruta                         | Función                                     |
| ------------------------ | ---------------------------- | ------------------------------------------- |
| `EvaluationIndex`        | `quality.evaluations.index`  | Lista evaluaciones + filtros                |
| `TeamEvaluationSelector` | `quality.evaluations.create` | Selección de empleado para nueva evaluación |
| `CriteriaList`           | `quality.criteria.index`     | CRUD de criterios                           |
| `QueueList`              | `quality.queues.index`       | CRUD de colas de calidad                    |
| `ManageQueueCriteria`    | `quality.queues.criteria`    | Asignar criterios a colas                   |

**Propuesta:** `EvaluationIndex` y `TeamEvaluationSelector` podrían unificarse en una sola página con vista de lista y botón "Nueva Evaluación" inline. **Los catálogos (criterios, colas)** tienen exactamente el mismo patrón que los catálogos de C1.

---

#### C4. Operaciones — Dashboards de Productividad

| Componente                      | Función principal                            |
| ------------------------------- | -------------------------------------------- |
| `PerformanceScorecard`          | Scorecard individual por agente              |
| `AgentPerformanceDashboard`     | Dashboard detallado de agente                |
| `AdvancedProductivityDashboard` | Analítica avanzada con top/bottom performers |
| `TeamPerformanceSummary`        | Resumen por equipo con drill-down            |
| `QueuePerformanceReport`        | Reporte por cola                             |

**Estos 5 componentes** muestran KPIs de productividad desde diferentes ángulos (individuo, equipo, cola, general). Existe superposición de datos: todos consultan `AgentDailyMetric` y/o `PerformanceService`. 

**Propuesta:** Centralizar la capa de datos en un único `ProductivityDataService` (ya existe `PerformanceService` como punto de partida). Evaluar si `AgentPerformanceDashboard` y `PerformanceScorecard` pueden fusionarse en una sola vista con tabs (scorecard / detalle). `AdvancedProductivityDashboard` y `TeamPerformanceSummary` también se superponen — el primero es esencialmente un team summary con analytics extra.

---

#### C5. Flujo de Aprobaciones

| Componente                           | Qué aprueba              | Ubicación en menú                              | Roles              |
| ------------------------------------ | ------------------------ | ---------------------------------------------- | ------------------ |
| `ManagerApprovals`                   | Permisos de subordinados | Mi Equipo → Aprobar Permisos                   | Supervisor/Manager |
| `WfmSwapApprovals`                   | Intercambios de turno    | Planificación → Aprobar Cambios de Turno       | WFM Team           |
| `PendingApprovals` (WorkflowsModule) | Flujos de trabajo        | NO en menú (ruta existe: `/workflows/pending`) | Admin              |

**Propuesta:** Las tres funcionalidades siguen el mismo patrón: listar items pendientes + acción de aprobar/rechazar + notificación. Podría crearse un `ApprovalDashboard` genérico en `WorkflowsModule` que cada módulo alimente vía un contrato (Interface). Esto eliminaría la duplicación en los 3 componentes.

---

#### C6. CRUD de Comunicaciones (3 componentes)

`ListNews`, `ListPolls`, `ListShoutouts` son virtualmente idénticos: tabla paginada, filtro de búsqueda, botón crear, modal de formulario, acción de borrado.

**Propuesta:** Un solo componente `ContentManager` configurable por tipo de contenido (news/poll/shoutout) con fields definidos desde el service provider de CommunicationsModule. Alternativa menos invasiva: trait `ManagesContentList`.

---

### 🟢 Observaciones Menores

| #   | Observación                        | Detalle                                                                                                                                                                                                       |
| --- | ---------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| O1  | `WorkflowsModule` invisible        | El módulo Workflows tiene una ruta `GET /workflows/pending` → `PendingApprovals` pero no aparece en el menú ni en `MenuHelper`. ¿Debe mostrarse en Planificación o Admin?                                     |
| O2  | Admin con 15 entradas de 6 módulos | Administración mezcla cosas de Personnel, Organization, Geo, Core, WFM, Audit, Communications. Considerar agrupar por subsecciones lógicas más claras (Configuración del Sistema / Organización / Seguridad). |
| O3  | `StaffingSummary` en Admin         | "Reportes de Personal" (`/personnel/reports/staffing`) está en Admin pero es un reporte, no configuración. Podría moverse a Operaciones o Mi Equipo.                                                          |
| O4  | `ListUsers` y `ListRoles`          | Solo `ListRoles` está en Admin. `CreateUser`/`EditUser` son componentes Livewire que existen pero no tienen entrada de menú directa (se accede desde `ListUsers` vía enlaces). Correcto así.                  |

---

## Resumen de Acciones Recomendadas

| Prioridad   | Acción                                                         | Impacto           | Beneficio                                       |
| ----------- | -------------------------------------------------------------- | ----------------- | ----------------------------------------------- |
| 🔴 **Alta**  | Eliminar ruta duplicada `operations.dashboard`                 | Bajo              | Elimina confusión y URL muerta                  |
| 🟡 **Media** | Crear `CrudCatalog` trait o componente genérico (C1)           | Alto (~20 clases) | Reduce ~60% de código CRUD duplicado            |
| 🟡 **Media** | Centralizar `ProductivityDataService` (C4)                     | Medio             | Reduce duplicación de consultas en 5 dashboards |
| 🟡 **Media** | Fusionar PerformanceScorecard + AgentPerformanceDashboard (C4) | Medio             | Simplifica navegación en Operaciones            |
| 🟡 **Media** | Crear `ContentManager` unificado para Comms (C6)               | Medio             | 3 componentes → 1 configurable                  |
| 🟡 **Media** | Crear `HasContentPublishing` trait (C2)                        | Medio             | Compartido por Knowledge + Documentation        |
| 🟢 **Baja**  | Evaluar `ApprovalDashboard` genérico (C5)                      | Bajo              | Estandariza 3 aprobaciones distintas            |
| 🟢 **Baja**  | Revisar agrupación Admin (O2, O3)                              | Bajo              | Mejora UX de navegación                         |
| 🟢 **Baja**  | Decidir visibilidad de WorkflowsModule (O1)                    | Bajo              | Cierra funcionalidad existente                  |

---

*Documento generado a partir del árbol de menú del sidebar y el mapeo de rutas/componentes del sistema. Julio 2026.*
