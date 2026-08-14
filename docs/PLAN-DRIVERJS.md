# Plan de Implementación de Driver.js — HorariosWFM

> **Propósito:** Evaluar e implementar progresivamente **Driver.js** para tours
> guiados, onboarding contextual y ayuda de navegación en HorariosWFM.
> Referencia oficial: https://driverjs.com/

---

## 0. Estado actual de la integración (evaluación de la rama)

La rama `feature/driverjs-onboarding-tours` ya contiene una **base funcional**
(commit `9cd6c92`). Antes de escalar tours, hay que conocer qué está hecho y qué
falta.

### Lo que ya existe

| Pieza                                                                                              | Ubicación                                              | Estado                                                     |
| -------------------------------------------------------------------------------------------------- | ------------------------------------------------------ | ---------------------------------------------------------- |
| Dependencia `driver.js@1.8.0`                                                                      | `package.json`                                         | Instalada                                                  |
| Manager de tours (instancia única, persistencia, filtrado de pasos)                                | `resources/js/tours/driver-manager.js`                 | Funciona                                                   |
| Definiciones de 6 tours                                                                            | `resources/js/tours/definitions.js`                    | Definidas, selectores **no cableados** salvo Control Tower |
| Bootstrap global (`window.WfmTour`, `startWfmTour`, `livewire:navigated`, evento `wfm:start-tour`) | `resources/js/tours/index.js`                          | Funciona                                                   |
| Theming del popover (light/dark, contraste)                                                        | `resources/css/app.css`                                | Funciona                                                   |
| Botón reutilizable `x-wfm.tour-button`                                                             | `resources/views/components/wfm/tour-button.blade.php` | Funciona                                                   |
| Prop `tour`/`tourAuto` en `page-header`                                                            | `resources/views/components/wfm/page-header.blade.php` | Funciona                                                   |
| Atributos `data-tour` en la Torre de Control                                                       | `control-tower-dashboard.blade.php`                    | Único cableado real                                        |

### Problemas detectados en la base actual

1. **Selectores fantasma:** los tours `wfm-planning`, `my-schedule`,
   `contact-center-calls`, `quality-evaluations` y `team-assignments` apuntan a
   `[data-tour="…"]` que **no existen** en las vistas. El filtro de
   `driver-manager.js` elimina los pasos inexistentes → el tour se descarta en
   silencio o no arranca. Ninguna vista usa aún `:tour`/`tour-auto` en el
   `page-header`.
2. **`wfm-planning` mal dirigido:** está definido contra una "matriz de
   horarios" (`planning-grid`), pero `weekly-planning` es una **lista de
   semanas**; la matriz vive en `team-weekly-planning` / `employee-weekly-planning`.
3. **Persistencia por dispositivo:** `localStorage` (`wfm_tours_completed`) no
   distingue usuario ni sincroniza entre navegadores. En terminales compartidas
   de call center un usuario nuevo verá los tours como "vistos" por otro.
4. **Sin versionado:** no existe forma de volver a mostrar un tour tras
   actualizarlo (clave `tour.feature.v2` del plan).|
5. **Timing frágil:** `autoStartIfPending` usa `setTimeout(600ms)`; si Livewire
   no terminó de renderizar (lazy loading, widgets), el tour no arranca.
6. **Navegación SPA sin limpieza:** si un tour está activo y el usuario navega
   con `wire:navigate`, la instancia no se destruye explícitamente en
   `livewire:navigated`.
7. **Steps dentro de modales Flux:** driver.js resalta sobre DOM real; un paso
   que apunte dentro de un modal abierto requiere lanzar el tour *después* de
   abrir el modal. Hoy no hay soporte explícito.

La conclusión es que **la fundación es correcta y debe conservarse**; el plan
endurece la infraestructura y luego escala los tours por módulo.

---

## 1. Resumen ejecutivo

Driver.js aporta valor real en tres lugares de HorariosWFM:

- **Onboarding de vistas complejas** (Torre de Control, dashboards operativos):
  un usuario nuevo no descubre por sí solo KPIs, widgets y filtros de una
  pantalla densa.
- **Guided workflows** (planificación semanal, asignación de equipos,
  importaciones, evaluación de calidad): flujos de varios pasos donde conviene
  guiar.
- **Ayuda contextual** (botón "Guía" reutilizable) en vistas de alta densidad.

**No** debe usarse como reemplazo de labels, tooltips, empty states o documentación.

La estrategia es un **monolito modular con infraestructura compartida mínima**:

- Todo lo genérico vive en `resources/js/tours/` (un único manager, un registry
  de definiciones, theming).
- Cada módulo expone únicamente atributos `data-tour="<id>"` en sus vistas
  Blade y, donde exista, el botón de guía en su `page-header`.
- Los tours se identifican como `module.feature` y **no se repiten la lógica**
  entre módulos.

**Recomendación de prioridad:** validar con el **MVP Torre de Control** (ya
cableada), luego onboarding de empleado (Mi Horario / Mi Día / Dashboard), luego
flujos operativos WFM (planificación, asignación de equipos, importaciones),
después calidad/operaciones complejas, y por último estandarizar.

---

## 2. Estrategia recomendada

### Principios

1. **Menos es más.** Solo tours donde el usuario no puede descubrir la función
   por sí mismo (P0/P1). No "turismo" por las vistas.
2. **Los selectores viven en el módulo.** La infraestructura JS es global; el
   contenido (qué resaltar y qué texto) pertenece a la vista del módulo que lo
   usa. Esto respeta `app/Modules/{Module}/`.
3. **Un solo manager, cero abstracciones nuevas.** No crear `TourService`,
   `TourRegistry` en PHP, ni envoltorios que repitan la API de driver.js. El
   `TourManager` JS existente es suficiente.
4. **Persistencia simple primero.** `localStorage` versionado para el MVP;
   evolución a persistencia por usuario si el negocio lo exige (ver §3).
5. **Nunca bloquear el flujo.** El tour siempre se puede cerrar (`allowClose`),
   nunca es obligatorio y no se auto-inicia en exceso.
6. **Robustez sobre velocidad.** Esperar a que los elementos existan realmente
   antes de `drive()`; destruir sobre navegación SPA.

### Decisiones marco

| Decisión                  | Elección                                                                              | Justificación                                    |
| ------------------------- | ------------------------------------------------------------------------------------- | ------------------------------------------------ |
| Dónde instalar            | `npm i driver.js` (ya hecho)                                                          | Librería oficial                                 |
| Cómo cargar               | Import en `driver-manager.js` (Vite bundle principal)                                 | driver.js ≈ 8 kB gzip; no justifica lazy loading |
| Dónde centralizar config  | `resources/js/tours/definitions.js`                                                   | Único lugar de definiciones                      |
| Dónde vivir los elementos | Atributos `data-tour="…"` en cada vista de módulo                                     | Monolito modular                                 |
| Cómo iniciar              | Manual (botón Guía), `data-tour-auto` (primera visita), `$dispatch('wfm:start-tour')` | Tres triggers, sin exceso                        |
| Cómo detener              | `driver.destroy()` + marcar completado                                                | Siempre                                          |

---

## 3. Arquitectura propuesta

### 3.1 Estructura de archivos

```
resources/js/
├── app.js                      # import './tours' (ya hecho)
└── tours/
    ├── index.js                # bootstrap global + listeners (ya existe)
    ├── driver-manager.js       # TourManager singleton (ya existe, endurecer)
    └── definitions.js          # registry module.feature → steps (ya existe)

resources/views/
├── components/wfm/
│   ├── tour-button.blade.php   # botón "Guía" reutilizable (ya existe)
│   └── page-header.blade.php   # prop tour/tourAuto (ya existe)
└── (cada módulo)
    └── Resources/Views/livewire/*.blade.php   # data-tour="module.feature"
```

### 3.2 Identificación de tours

Formato obligatorio `module.feature` (opcional `.v<N>` tras actualizaciones):

```
operations.control-tower
wfm.planning-week
wfm.my-schedule
connect.calls
quality.evaluations
personnel.team-assignments
```

> **Acción:** renombrar las claves actuales de `definitions.js`
> (`wfm-planning` → `wfm.planning-week`, `control-tower` →
> `operations.control-tower`, `contact-center-calls` → `connect.calls`,
> `quality-evaluations` → `quality.evaluations`, `team-assignments` →
> `personnel.team-assignments`, `my-schedule` → `wfm.my-schedule`).

### 3.3 Persistencia

**MVP (conservar):** `localStorage` con clave versionada
`wfm_tours_completed_v1` guardando un mapa `{ "module.feature": versionSeen }`.
Cada definición declara `version`. Si `versionSeen < definition.version` el tour
vuelve a mostrarse (esto resuelve "tour actualizado → mostrar de nuevo").

**Evolución opcional (solo si hay terminales compartidas):** mover a una tabla
`user_tour_progress` (`user_id`, `tour_key`, `version`, `completed_at`,
`skipped_at`) o reutilizar la infraestructura de settings por usuario ya
existente en `CoreModule`. No crear antes de que el negocio lo pida.

### 3.4 Activación

| Trigger        | Uso                                    | Cómo                                                                               |
| -------------- | -------------------------------------- | ---------------------------------------------------------------------------------- |
| Manual         | Todos los tours, siempre disponible    | `<x-wfm.tour-button :tour="'module.feature'" />`                                   |
| Primera visita | Solo P0 (onboarding de vista compleja) | `data-tour-auto="module.feature"` en `page-header` + listener `livewire:navigated` |
| Desde Livewire | Flujos guiados (post-acción)           | `$dispatch('wfm:start-tour', { tour: 'module.feature' })`                          |

Regla: **un solo tour auto-iniciado por sesión** y nunca en páginas con
auto-refresh pesado.

### 3.5 Navegación y ciclo de vida Livewire

- Escuchar `livewire:navigated` (ya existe) para: (a) auto-iniciar el tour de la
  nueva vista y (b) **destruir** cualquier driver activo antes de navegar
  (`livewire:navigate` no destruye overlays por sí solo).
- Antes de `drive()`, **esperar a que existan los elementos**: reemplazar el
  `setTimeout(600)` por un helper `waitForElements(steps, timeout)` que
  reintenta con `requestAnimationFrame`/`MutationObserver` y falla con log en
  consola (no romper la app). driver.js 1.x no resuelve esperas automáticamente.
- Los widgets Livewire con auto-refresh re-renderizan su contenedor: usar como
  ancla **contenedores estables** (`data-tour` en el wrapper), no elementos
  internos que se recrean.
- Steps dentro de modales Flux: no soportados de forma fiable mientras el modal
  esté cerrado. Si un paso debe resaltar contenido de un modal, el tour se
  lanza **después** de abrirlo (trigger manual o `$dispatch` al abrir el modal)
  o ese paso se omite.

### 3.6 Robustez

- Filtrar pasos sin elemento existente (ya se hace) **pero** registrar con
  `console.warn` cuáles se omitieron para detectar selectores rotos en
  producción.
- Instancia única (ya se hace): `destroy()` antes de `drive()`.
- `onDestroyStarted`: marcar completado (ya se hace). Considerar marcar
  "visto" también al cerrar con ESC o clic en overlay (comportamiento actual).
- Theming: conservar el CSS existente; validar contraste AA en dark/light.

---

## 4. Inventario de vistas analizadas

Leyenda prioridad candidata: **P0** crítica / **P1** alta / **P2** media /
**P3** baja / **—** no aplica (no usar Driver.js).

### 4.1 OperationsModule — Operaciones

| Ruta                                                                                                                                                      | Componente                           | Función                                          | Usuario        | Compl. UI  | Flujo     | Candidata          | Prioridad |
| --------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------ | ------------------------------------------------ | -------------- | ---------- | --------- | ------------------ | --------- |
| `/dashboard`                                                                                                                                              | `Dashboard`                          | Panel rol-aware: KPIs, SLA, estados, cobertura   | Todos          | Alta       | Consulta  | Sí                 | **P1**    |
| `/operations/control-tower`                                                                                                                               | `ControlTower\ControlTowerDashboard` | Torre de control: KPIs, colas, alertas, forecast | Supervisor/WFM | Alta       | Operación | Sí (ya cableada)   | **P0**    |
| `/operations/realtime`                                                                                                                                    | `RealtimeMonitoring`                 | Grid de agentes en vivo, filtros, auto-refresh   | Supervisor     | Media      | Operación | Sí                 | **P1**    |
| `/operations/availability`                                                                                                                                | `IntradayAvailability`               | Disponibilidad intradía, riesgo de cobertura     | WFM            | Media      | Consulta  | Sí                 | **P2**    |
| `/operations/reporte-diario`                                                                                                                              | `DailyReport`                        | Reporte diario operador/equipo                   | Supervisor     | Media      | Consulta  | Sí                 | **P2**    |
| `/operations/queue-performance`                                                                                                                           | `QueuePerformanceReport`             | Rendimiento por cola                             | WFM            | Media      | Consulta  | Sí                 | **P2**    |
| `/operations/performance`                                                                                                                                 | `PerformanceScorecard`               | Scorecard de desempeño                           | Supervisor/WFM | Media      | Consulta  | Sí                 | **P2**    |
| `/operations/agent-performance/{employee}`                                                                                                                | `AgentPerformanceDashboard`          | Detalle de agente                                | Supervisor     | Media      | Consulta  | Parcial (tooltips) | **P3**    |
| `/operations/advanced-analytics`                                                                                                                          | `AdvancedProductivityDashboard`      | Productividad avanzada                           | WFM            | Media      | Consulta  | Sí                 | **P2**    |
| `/operations/team-performance`                                                                                                                            | `TeamPerformanceSummary`             | Resumen por equipo                               | Supervisor     | Media      | Consulta  | Parcial            | **P3**    |
| `/operations/reports`                                                                                                                                     | `ReportingFrameworkIndex`            | Hub de reportes                                  | Todos          | Baja       | Consulta  | No (hub simple)    | **—**     |
| `/operations/{capacity,intervals,kpis,forecast,trends,scenarios,shrinkage,skills,staffing,comparison,explorer,calls,capacity-analysis,staffing-analysis}` | Dashboards de analítica              | Diversos reportes                                | WFM/Analista   | Media/Alta | Consulta  | Parcial            | **P3**    |

### 4.2 WfmModule — Planificación

| Ruta                                                                                                                      | Componente                 | Función                   | Usuario    | Compl. UI | Flujo             | Candidata                      | Prioridad |
| ------------------------------------------------------------------------------------------------------------------------- | -------------------------- | ------------------------- | ---------- | --------- | ----------------- | ------------------------------ | --------- |
| `/schedules/my-schedule/{week}/{day}`                                                                                     | `MySchedule`               | Mi horario semanal        | Operador   | Media     | Consulta          | Sí                             | **P1**    |
| `/schedules/my-day`                                                                                                       | `MyDay`                    | Jornada en tiempo real    | Operador   | Media     | Consulta          | Sí                             | **P1**    |
| `/schedules/my-team`                                                                                                      | `MyTeam`                   | Equipo del supervisor     | Supervisor | Media     | Operación         | Sí                             | **P1**    |
| `/schedules/leave-request/{type}`                                                                                         | `RequestLeave`             | Solicitar permiso         | Operador   | Baja      | Creación          | Parcial (paso único)           | **P3**    |
| `/schedules/swap-request`                                                                                                 | `RequestShiftSwap`         | Solicitar cambio de turno | Operador   | Baja      | Creación          | Parcial                        | **P3**    |
| `/schedules/swap-history`                                                                                                 | `SwapRequestHistory`       | Historial de swaps        | Operador   | Baja      | Consulta          | No                             | **—**     |
| `/schedules/manager-approvals`                                                                                            | `ManagerApprovals`         | Aprobar permisos          | Supervisor | Baja      | Aprobación        | Sí (workflow)                  | **P2**    |
| `/schedules/request-summary`                                                                                              | `RequestSummary`           | Resumen de solicitudes    | WFM        | Baja      | Consulta          | No                             | **—**     |
| `/schedules/planning`                                                                                                     | `WeeklyPlanning`           | Lista de semanas          | WFM        | Baja      | Consulta/Creación | Parcial (puerta de entrada)    | **P2**    |
| `/schedules/planning/{week}/team/{team}`                                                                                  | `TeamWeeklyPlanning`       | Matriz por equipo         | WFM        | **Alta**  | Edición           | **Sí (guía core)**             | **P0**    |
| `/schedules/planning/{week}/teams`                                                                                        | `WeeklyPlanningTeams`      | Turnos base por equipo    | WFM        | Media     | Edición           | Parcial                        | **P2**    |
| `/schedules/planning/{week}/employee/{employee}`                                                                          | `EmployeeWeeklyPlanning`   | Matriz por empleado       | WFM        | Alta      | Edición           | Sí                             | **P1**    |
| `/schedules/planning/{week}/import`                                                                                       | `ImportWeeklySchedule`     | Importar planilla         | WFM        | Alta      | Importación       | **Sí (guided)**                | **P1**    |
| `/schedules/intraday-activities/manage`                                                                                   | `ManageIntradayActivities` | Actividades intradía      | WFM/Coord. | Media     | Creación          | Sí                             | **P2**    |
| `/schedules/exceptions`                                                                                                   | `ManageScheduleExceptions` | Excepciones de horario    | WFM        | Media     | CRUD              | Parcial                        | **P2**    |
| `/schedules/leave-history`                                                                                                | `LeaveRequestHistory`      | Historial de permisos     | WFM        | Baja      | Consulta          | No                             | **—**     |
| Catálogos (`shifts`, `activity-types`, `absence-reasons`, `agent-states`, `scheduled-activities`, `operational-settings`) | `Manage*`                  | CRUD de catálogos         | WFM/Admin  | Baja      | CRUD              | **No (tooltips/empty states)** | **—**     |
| `/schedules/wfm-approvals`                                                                                                | `WfmSwapApprovals`         | Aprobar intercambios      | WFM        | Media     | Aprobación        | Sí                             | **P2**    |

### 4.3 ConnectModule — Centro de Contacto

| Ruta                                         | Componente         | Función                        | Usuario       | Compl. UI | Flujo     | Candidata | Prioridad |
| -------------------------------------------- | ------------------ | ------------------------------ | ------------- | --------- | --------- | --------- | --------- |
| `/contact-center/calls`                      | `ListCallRecords`  | Registro de llamadas + filtros | Supervisor/QA | Media     | Consulta  | Sí        | **P1**    |
| `/contact-center/my-dashboard`               | `AgentDashboard`   | Panel del agente               | Operador      | Media     | Operación | Sí        | **P1**    |
| `/contact-center/general-dashboard`          | `GeneralDashboard` | Panel general                  | Supervisor    | Media     | Consulta  | Sí        | **P2**    |
| `/contact-center/calls/create`               | `CreateCallRecord` | Alta manual de llamada         | Operador      | Media     | Creación  | Parcial   | **P2**    |
| Catálogos (`queues`, `channels`, `subtypes`) | `List*`            | CRUD de catálogos              | Admin         | Baja      | CRUD      | No        | **—**     |

### 4.4 QualityModule — Calidad

| Ruta                                                   | Componente                                      | Función                     | Usuario  | Compl. UI | Flujo      | Candidata            | Prioridad |
| ------------------------------------------------------ | ----------------------------------------------- | --------------------------- | -------- | --------- | ---------- | -------------------- | --------- |
| `/quality/evaluaciones`                                | `EvaluationIndex`                               | Lista + filtros             | QA       | Media     | Consulta   | Sí                   | **P2**    |
| `/quality/evaluaciones/crear`                          | `TeamEvaluationSelector`                        | Seleccionar equipo/empleado | QA       | Baja      | Creación   | Parcial              | **P3**    |
| `/quality/evaluaciones/crear/{employee}`               | `EvaluationForm`                                | Rúbrica + audio player      | QA       | **Alta**  | Evaluación | **Sí (guided core)** | **P0**    |
| `/quality/evaluaciones/{eval}`                         | `EvaluationDetail`                              | Detalle de evaluación       | QA       | Media     | Consulta   | Sí                   | **P2**    |
| `/quality/evaluaciones/{eval}/feedback`                | `FeedbackForm`                                  | Feedback al agente          | QA       | Media     | Creación   | Parcial              | **P2**    |
| `/quality/evaluaciones/{eval}/calibrar`                | `CalibrationForm`                               | Calibración                 | QA       | Media     | Evaluación | Parcial              | **P2**    |
| Catálogos (`criterios`, `colas`, `criterios por cola`) | `Criteria*`, `QueueList`, `ManageQueueCriteria` | CRUD/mapeo                  | QA/Admin | Baja      | CRUD       | **No**               | **—**     |

### 4.5 PersonnelModule — Personal

| Ruta                                          | Componente                  | Función                   | Usuario    | Compl. UI | Flujo            | Candidata            | Prioridad |
| --------------------------------------------- | --------------------------- | ------------------------- | ---------- | --------- | ---------------- | -------------------- | --------- |
| `/employees`                                  | `EmployeeController@index`  | Lista de empleados        | RRHH       | Baja      | Consulta         | No                   | **—**     |
| `/employees/import`                           | `EmployeeController@import` | Importación masiva        | RRHH       | Media     | Importación      | Sí (guided)          | **P1**    |
| `/employees/teams/manage`                     | `ManageTeamAssignments`     | Drag & drop de asignación | RRHH/Sup.  | **Alta**  | Edición          | **Sí (guided core)** | **P0**    |
| `/organization/teams/{team}/members`          | `ManageTeamMembers`         | Miembros de equipo        | Supervisor | Media     | Edición          | Parcial              | **P2**    |
| `/organization/teams/{team}/transfer`         | `TeamMemberTransfer`        | Transferencia             | RRHH       | Baja      | Edición          | No                   | **—**     |
| `/personnel/reports/staffing`                 | `StaffingSummary`           | Resumen staffing          | RRHH/WFM   | Media     | Consulta         | Parcial              | **P3**    |
| CRUD (`employees/{id}`, `teams`, create/edit) | `Create/Edit*`              | Formularios               | RRHH       | Baja      | Creación/Edición | No (labels)          | **—**     |

### 4.6 OrganizationModule — Organigrama

| Ruta                                                        | Componente                  | Candidata            |
| ----------------------------------------------------------- | --------------------------- | -------------------- |
| `/organization/{directorates,departments,positions}` + CRUD | `List*/Create*/Edit*/Show*` | **No** (CRUD simple) |

### 4.7 CoreModule / Admin

| Ruta                                     | Componente                                 | Candidata |
| ---------------------------------------- | ------------------------------------------ | --------- |
| `/notifications`, `/admin/notifications` | `NotificationHistory`, `NotificationAdmin` | **No**    |
| `/admin/users`, `/admin/roles`           | `Users\ListUsers`, `Roles\ListRoles`       | **No**    |
| `/admin/system/maintenance`              | `SystemMaintenance`                        | **No**    |
| `/admin/audit`                           | `ListAuditLogs`                            | **No**    |

### 4.8 Otros módulos

| Ruta                               | Componente                       | Módulo         | Candidata                                 |
| ---------------------------------- | -------------------------------- | -------------- | ----------------------------------------- |
| `/`                                | `Home`                           | Communications | Parcial (tour de feed/encuestas, P2)      |
| `/helpdesk/my-tickets`             | `MyTickets`                      | Helpdesk       | **Sí (P1)** — ticket con SLA              |
| `/helpdesk/manage`                 | `ManageTickets`                  | Helpdesk       | **Sí (P1)** — bandeja con SLA/filtros     |
| `/helpdesk/ticket/{t}`             | `TicketDetail`                   | Helpdesk       | No                                        |
| `/knowledge`                       | `OperatorView`                   | Knowledge      | **Sí (P2)** — búsqueda + filtros por cola |
| `/knowledge/{slug}`                | `KnowledgeArticleDetail`         | Knowledge      | No                                        |
| `/docs`                            | `WikiArticleIndex`               | Documentation  | No                                        |
| `/filesystem`                      | `FileBrowser`                    | Filesystem     | **Sí (P2)** — UI densa                    |
| `/descargas`, `/filesystem/quotas` | `DownloadCenter`, `QuotaManager` | Filesystem     | No                                        |
| `/workflows/pending`               | `PendingApprovals`               | Workflows      | Parcial (P2)                              |
| `/reportes/{category}/{subReport}` | `ReportGenerator`                | Reporting      | No (documentado en UI)                    |

---

## 5. Vistas candidatas y descartadas

### Candidatas (a tour)

`operations.control-tower`, `operations.dashboard`, `operations.realtime`,
`operations.availability`, `operations.daily-report`,
`operations.queue-performance`, `operations.performance`,
`operations.advanced-analytics`, `wfm.planning-week` (matriz),
`wfm.employee-planning`, `wfm.import-weekly`, `wfm.my-schedule`, `wfm.my-day`,
`wfm.my-team`, `wfm.manager-approvals`, `wfm.swap-approvals`,
`wfm.intraday-activities`, `personnel.team-assignments`, `personnel.import-employees`,
`connect.calls`, `connect.agent-dashboard`, `connect.general-dashboard`,
`quality.evaluations`, `quality.evaluation-form`, `quality.evaluation-detail`,
`helpdesk.my-tickets`, `helpdesk.manage`, `knowledge.operator-view`,
`filesystem.browser`, `communications.home`, `workflows.pending`.

### Descartadas (NO usar Driver.js)

- **Todos los CRUD de catálogos** (≥20 componentes): `ManageActivityTypes`,
  `ManageAbsenceReasons`, `ManageAgentStates`, `ManageSchedules` (turnos),
  `ManageScheduledActivities`, `ListChannels`, `ListCallQueues`,
  `ListCaseSubtypes`, `ListTeams`, `QueueList`, `CriteriaList`,
  `ManageQueueCriteria`, `ListDirectorates`, `ListDepartments`, `ListPositions`,
  `ListNews`, `ListPolls`, `ListShoutouts`, `ManageWikiArticles`,
  `ManageKnowledgeArticles`, `ListUsers`, `ListRoles`, `ListAuditLogs`,
  `CategoryController`, `TagController`, `ContentModerationController`. → Se
  resuelven con labels, placeholders y empty states.
- **Formularios simples de un paso** (`RequestLeave`, `RequestShiftSwap`,
  `CreateCallRecord`, create/edit de organización/personal): basta con la
  validación inline y labels. Un tour añadiría fricción.
- **Auth, perfil, notificaciones, geo, mantenimiento, cuotas, descargas.**
- **Historiales pasivos** (`SwapRequestHistory`, `LeaveRequestHistory`,
  `RequestSummary`, `TeamPerformanceSummary` detail).

**Regla de oro:** si un label, tooltip, empty state o la documentación
(DocumentationModule/KnowledgeModule) resuelven la duda, no se usa Driver.js.

---

## 6. Priorización de oportunidades

| Prioridad | Tour                                                                                                                                                                                                                                           | Por qué                                                                                                  |
| --------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| **P0**    | `operations.control-tower`                                                                                                                                                                                                                     | Pantalla densa con KPIs/widgets; sin guía el usuario no la comprende. Ya cableada (MVP).                 |
| **P0**    | `wfm.planning-week` (matriz por equipo)                                                                                                                                                                                                        | Flujo core de WFM con matriz de 7 días por empleado, colisiones, publicación.                            |
| **P0**    | `quality.evaluation-form`                                                                                                                                                                                                                      | Evaluación con reproductor de audio, rúbrica ponderada y errores fatales; difícil de completar sin guía. |
| **P0**    | `personnel.team-assignments`                                                                                                                                                                                                                   | Drag & drop de asignación masiva; primera vez poco intuitivo.                                            |
| **P1**    | `wfm.my-schedule`, `wfm.my-day`                                                                                                                                                                                                                | Onboarding de operador nuevo (cómo leer horario, qué acciones tiene).                                    |
| **P1**    | `operations.dashboard`, `operations.realtime`                                                                                                                                                                                                  | Landing y monitoreo en vivo.                                                                             |
| **P1**    | `connect.calls`, `connect.agent-dashboard`                                                                                                                                                                                                     | Filtros avanzados + panel de agente.                                                                     |
| **P1**    | `wfm.import-weekly`, `personnel.import-employees`                                                                                                                                                                                              | Guided workflows de importación (varios pasos, plantilla, validaciones).                                 |
| **P1**    | `helpdesk.my-tickets`, `helpdesk.manage`                                                                                                                                                                                                       | Formulario + bandeja con SLA.                                                                            |
| **P2**    | `wfm.my-team`, `wfm.manager-approvals`, `wfm.swap-approvals`, `wfm.intraday-activities`, `wfm.employee-planning`                                                                                                                               | Importantes pero comprensibles con esfuerzo.                                                             |
| **P2**    | `operations.availability`, `operations.daily-report`, `operations.queue-performance`, `operations.performance`, `operations.advanced-analytics`                                                                                                | Reportes consultables sin guía.                                                                          |
| **P2**    | `quality.evaluation-detail`, `connect.general-dashboard`, `knowledge.operator-view`, `filesystem.browser`, `communications.home`, `workflows.pending`                                                                                          | Ayuda contextual útil, no crítica.                                                                       |
| **P3**    | `operations.agent-performance`, `operations.team-performance`, dashboards de analítica restantes, `staffing-summary`, `quality.team-selector`, `quality.feedback`, `quality.calibration`, `wfm.planning` (índice), `wfm.weekly-planning-teams` | Bajo valor; resolver con UI/labels/documentación.                                                        |

---

## 7. Tours propuestos por módulo

## OperationsModule

### Tour: operations.control-tower
- **Vista:** `control-tower-dashboard.blade.php`
- **Ruta:** `operations.control-tower`
- **Usuario:** Supervisor, WFM
- **Objetivo:** Comprender la torre de control: KPIs, equipos/colas, estado operacional, alertas, gráficos, forecast.
- **Prioridad:** P0 — **Trigger:** Primera visita (`data-tour-auto`) + botón Guía (manual, siempre).
- **Pasos aproximados:**
  1. Header: selector de fecha/equipo y acciones.
  2. Hero KPIs: SLA, AHT, adherencia, volumen.
  3. Equipos y colas.
  4. Estado operacional y alertas.
  5. Ocupación/SLA-ASA (gráficos).
  6. Forecast vs real y timeline del día.
- **Elementos UI requeridos:** 6 contenedores `data-tour` (ya añadidos).
- **Dependencias:** ninguna.
- **Consideraciones Livewire:** los widgets auto-refrescan sus contenedores; los `data-tour` están en wrappers estables. Esperar render antes de `drive()`.
- **Complejidad:** Baja (selectores ya existen).
- **Riesgos:** overlays sobre gráficos Apex (redibujan al refrescar) → anclar al wrapper.

### Tour: operations.dashboard
- **Vista:** `dashboard.blade.php`
- **Ruta:** `dashboard`
- **Usuario:** Todos (contenido por rol)
- **Objetivo:** Explicar KPIs, alertas SLA, cobertura y distribución según rol.
- **Prioridad:** P1 — **Trigger:** Manual + auto en primera visita (solo rol operador).
- **Pasos aproximados:** 1) KPIs principales; 2) alertas de SLA; 3) distribución de estados; 4) tarjetas de cobertura; 5) puntajes por equipo/incidencias.
- **Elementos UI requeridos:** `data-tour` en cada tarjeta/wrapper.
- **Dependencias:** página rol-aware → definir pasos por rol.
- **Complejidad:** Media.
- **Riesgos:** el contenido cambia por rol y con auto-refresh.

### Tour: operations.realtime
- **Vista:** `realtime-monitoring.blade.php`
- **Ruta:** `operations.realtime`
- **Usuario:** Supervisor
- **Objetivo:** Entender el grid en vivo y sus filtros (equipo, cargo, cola, estado, motivo) y el resumen de estados.
- **Prioridad:** P1 — **Trigger:** Manual.
- **Pasos aproximados:** 1) filtros; 2) grid de agentes; 3) resumen de estados; 4) auto-refresh.
- **Complejidad:** Media — **Riesgos:** re-render frecuente del grid; anclar a contenedores estables.

## WfmModule

### Tour: wfm.planning-week (matriz por equipo)
- **Vista:** `team-weekly-planning.blade.php`
- **Ruta:** `schedules.planning.team`
- **Usuario:** Analista WFM
- **Objetivo:** Guiar la edición de la matriz: asignar turnos por día, ver totales, detectar colisiones y publicar.
- **Prioridad:** P0 — **Trigger:** Manual (al entrar a una semana) + `$dispatch` al crear/abrir semana.
- **Pasos aproximados:**
  1. Selector de semana/equipo.
  2. Matriz empleado × día (celdas editables).
  3. Asignación de turno base / excepción.
  4. Total semanal por empleado.
  5. Validación de colisiones.
  6. Guardar/Publicar.
- **Elementos UI requeridos:** `data-tour` en tabla, celdas de edición, botón guardar/publicar.
- **Dependencias:** este tour reemplaza al actual `wfm-planning` (que apuntaba al índice de semanas).
- **Consideraciones Livewire:** la edición re-renderiza filas → anclar a la tabla, no a celdas.
- **Complejidad:** Media.
- **Riesgos:** selectores de celdas dinámicas; colisiones cambian el DOM.

### Tour: wfm.import-weekly
- **Vista:** `import-weekly-schedule.blade.php`
- **Ruta:** `schedules.planning.import`
- **Usuario:** Analista WFM
- **Objetivo:** Guided workflow de importación (descargar plantilla, subir, revisar errores, confirmar).
- **Prioridad:** P1 — **Trigger:** Manual + `$dispatch` al abrir.
- **Pasos aproximados:** 1) plantilla; 2) subida; 3) validación/errores; 4) confirmación.
- **Complejidad:** Media — **Riesgos:** estados de progreso dinámicos (loading/error).

### Tour: wfm.my-schedule
- **Vista:** `my-schedule.blade.php`
- **Ruta:** `schedules.my-schedule`
- **Usuario:** Operador
- **Objetivo:** Onboarding de operador: leer la semana, selector de semana, entender badges (turno, excepción, hoy) y acciones.
- **Prioridad:** P1 — **Trigger:** Manual + auto primera visita (operador).
- **Pasos aproximados:** 1) selector de semana; 2) tarjetas de día; 3) badges de excepción/hoy; 4) acciones disponibles.
- **Complejidad:** Baja.
- **Riesgos:** low (vista estable).

### Tour: wfm.my-day
- **Vista:** `my-day.blade.php`
- **Ruta:** `schedules.my-day`
- **Usuario:** Operador
- **Objetivo:** Explicar el desglose de tiempos (talk/ready/ACW), adherencia, SLA y eventos próximos.
- **Prioridad:** P1 — **Trigger:** Manual + auto primera visita.
- **Pasos aproximados:** 1) estado actual; 2) entrada real vs programada; 3) desglose de tiempos; 4) SLA/adherencia; 5) eventos.
- **Complejidad:** Media.
- **Riesgos:** auto-refresh en tiempo real; anclar a tarjetas estables.

### Tour: wfm.my-team
- **Vista:** `my-team.blade.php`
- **Ruta:** `schedules.my-team`
- **Usuario:** Supervisor
- **Objetivo:** Ubicar miembros, asignaciones semanales, pendientes de aprobación e incidencias.
- **Prioridad:** P2 — **Trigger:** Manual.
- **Pasos aproximados:** 1) selector de equipo; 2) lista de miembros; 3) pestañas/acciones (permisos, swaps, incidencias).
- **Complejidad:** Media — **Riesgos:** tabs que cambian el DOM (resaltar por tab).

### Tour: wfm.manager-approvals / wfm.swap-approvals
- **Vistas:** `manager-approvals.blade.php`, `wfm-swap-approvals.blade.php`
- **Rutas:** `schedules.manager-approvals`, `schedules.wfm-approvals`
- **Usuario:** Supervisor / WFM
- **Objetivo:** Guided workflow de aprobación: lista pendiente → revisar detalle → aprobar/rechazar con motivo.
- **Prioridad:** P2 — **Trigger:** Manual.
- **Pasos aproximados:** 1) lista de pendientes; 2) detalle; 3) acción aprobar/rechazar; 4) historial.
- **Complejidad:** Media — **Riesgos:** modales de confirmación (ver §3.5).

## PersonnelModule

### Tour: personnel.team-assignments
- **Vista:** `manage-team-assignments.blade.php`
- **Ruta:** `employees.teams.manage`
- **Usuario:** RRHH, Supervisor
- **Objetivo:** Entender el tablero drag & drop: personal sin asignar, equipos destino, acciones de balanceo.
- **Prioridad:** P0 — **Trigger:** Manual + auto primera visita.
- **Pasos aproximados:**
  1. Panel de personal sin asignar.
  2. Tableros de equipos.
  3. Drag & drop / acciones de asignación.
  4. Guardar y confirmar.
- **Elementos UI requeridos:** `data-tour="team-assign-unassigned"` y `data-tour="team-assign-boards"` (ya referenciados en `definitions.js`, **faltan en el blade**).
- **Complejidad:** Media.
- **Riesgos:** drag & drop + re-render Livewire: los pasos del tour pueden solaparse con el contenedor; anclar a wrappers y sugerir no iniciar durante un drag.

### Tour: personnel.import-employees
- **Vista:** `import-employees.blade.php`
- **Ruta:** `employees.import`
- **Usuario:** RRHH
- **Objetivo:** Guided workflow de importación masiva de empleados.
- **Prioridad:** P1 — **Trigger:** Manual.
- **Pasos aproximados:** 1) descargar plantilla; 2) subir archivo; 3) revisar resultados/errores; 4) confirmar.
- **Complejidad:** Media — **Riesgos:** estados dinámicos.

## ConnectModule

### Tour: connect.calls
- **Vista:** `list-call-records.blade.php`
- **Ruta:** `contact-center.calls.index`
- **Usuario:** Supervisor, QA
- **Objetivo:** Explicar filtros avanzados (estado, fechas, empleado, cola, canal, subtipo) y el detalle en modal.
- **Prioridad:** P1 — **Trigger:** Manual.
- **Pasos aproximados:** 1) filtros; 2) tabla; 3) modal de detalle; 4) acciones (grabaciones, notas).
- **Complejidad:** Media.
- **Riesgos:** el paso de modal requiere abrirlo antes del tour (§3.5) o mostrarse como paso final sin modal.

### Tour: connect.agent-dashboard
- **Vista:** `agent-dashboard.blade.php`
- **Ruta:** `contact-center.agent-dashboard`
- **Usuario:** Operador
- **Objetivo:** Onboarding del panel del agente (estado, llamadas, KPIs del día).
- **Prioridad:** P1 — **Trigger:** Manual + auto primera visita.
- **Pasos aproximados:** 1) estado del agente; 2) cola/llamadas; 3) KPIs diarios.
- **Complejidad:** Media — **Riesgos:** datos en vivo.

## QualityModule

### Tour: quality.evaluation-form
- **Vista:** `evaluation-form.blade.php`
- **Ruta:** `quality.evaluations.form`
- **Usuario:** Analista QA
- **Objetivo:** Guided workflow de evaluación: reproductor de audio, rúbrica con pesos, errores fatales, guardado.
- **Prioridad:** P0 — **Trigger:** Manual al abrir formulario + `$dispatch` al seleccionar empleado.
- **Pasos aproximados:**
  1. Reproductor de audio.
  2. Criterios y ponderaciones.
  3. Errores fatales (anulan puntaje).
  4. Puntaje automático.
  5. Guardar y enviar.
- **Elementos UI requeridos:** `data-tour` en reproductor, sección de criterios, bloque de fatales, botón guardar.
- **Complejidad:** Alta.
- **Riesgos:** formulario largo con secciones colapsables; elementos que aparecen según respuestas → pasos condicionales difíciles; **limitar a 5-6 pasos y a lo estable**.

### Tour: quality.evaluations (índice)
- **Vista:** `evaluation-index.blade.php`
- **Ruta:** `quality.evaluations.index`
- **Usuario:** QA
- **Objetivo:** Ubicar filtros, columnas ordenables y el acceso a nueva evaluación.
- **Prioridad:** P2 — **Trigger:** Manual.
- **Pasos aproximados:** 1) filtros; 2) tabla ordenable; 3) botón nueva evaluación.
- **Complejidad:** Baja.

## HelpdeskModule

### Tour: helpdesk.my-tickets / helpdesk.manage
- **Vistas:** `my-tickets.blade.php`, `manage-tickets.blade.php`
- **Rutas:** `helpdesk.my-tickets`, `helpdesk.manage`
- **Usuario:** Operador / Soporte
- **Objetivo:** Guided workflow: crear ticket (formulario) / gestionar bandeja (filtros por SLA, auto-asignación).
- **Prioridad:** P1 — **Trigger:** Manual.
- **Pasos aproximados:** 1) formulario de creación; 2) categoría/prioridad; 3) historial. (Bandeja: filtros, SLA, auto-asignación).
- **Complejidad:** Media — **Riesgos:** modales de creación.

## KnowledgeModule / FilesystemModule

### Tour: knowledge.operator-view
- **Vista:** `operator-view.blade.php`
- **Ruta:** `knowledge.index`
- **Usuario:** Operador
- **Objetivo:** Búsqueda por palabra clave y filtros por cola/categoría/tag.
- **Prioridad:** P2 — **Trigger:** Manual.
- **Pasos:** 1) buscador; 2) filtros; 3) resultados.
- **Complejidad:** Baja.

### Tour: filesystem.browser
- **Vista:** `file-browser.blade.php`
- **Ruta:** `filesystem.index`
- **Usuario:** Todos
- **Objetivo:** Árbol de carpetas, subida, compartir, modos de vista.
- **Prioridad:** P2 — **Trigger:** Manual.
- **Pasos:** 1) árbol; 2) subida; 3) compartir; 4) búsqueda/modos de vista.
- **Complejidad:** Media — **Riesgos:** drag & drop de subida.

---

## 8. MVP inicial

**MVP = Torre de Control** (`operations.control-tower`).

Es el candidato ideal porque:
- Ya está cableada (atributos `data-tour` + definición existente).
- Es una vista P0 de alta densidad que demuestra el valor de la librería.
- Ejercita todos los mecanismos: botón manual, auto-inicio, persistencia,
  theming, navegación SPA y widgets Livewire.

### Criterios de éxito del MVP
1. El botón "Guía" arranca el tour desde la Torre de Control.
2. El tour se auto-inicia la primera vez y no se repite (persistencia).
3. Tras navegar a otra vista con `wire:navigate` y volver, no quedan overlays
   huérfanos.
4. Los 6 pasos se resuelven sobre los widgets reales (sin pasos omitidos).
5. Contraste AA en dark y light; cierre con ESC y "¡Entendido!".
6. En `console` no hay errores de selectores inexistentes.

### Entregables del MVP (endurecimiento de la base)
- Helper `waitForElements()` y eliminar el `setTimeout` fijo.
- Destrucción del driver activo en `livewire:navigated`.
- Renombrado de claves a `module.feature` + campo `version` + `localStorage`
  versionado.
- Log (`console.warn`) de pasos omitidos por selector inexistente.
- `data-tour-auto` + botón Guía en la cabecera de la Torre de Control.

---

## 9. Roadmap de implementación

```text
Fundación  (MVP: Torre de Control)
    ↓
Onboarding de empleado (Mi Horario, Mi Día, Dashboard, Agente)
    ↓
Flujos operativos WFM (Matriz de planificación, Importación, Equipos)
    ↓
Flujos complejos (Evaluación QA, Helpdesk, Aprobaciones)
    ↓
Contextual help (Knowledge, Filesystem, Comunicaciones, reportes P2)
    ↓
Optimización y estandarización (i18n, a11y, telemetría, revisión de selectores)
```

| Fase                           | Contenido                                                                                                                                                            | Estimación      |
| ------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------- |
| **F1 — Fundación (MVP)**       | Endurecer `driver-manager`, renombrado/versión, wiring Control Tower, tests de integridad                                                                            | 1 iteración     |
| **F2 — Onboarding empleado**   | `wfm.my-schedule`, `wfm.my-day`, `operations.dashboard`, `connect.agent-dashboard`                                                                                   | 1-2 iteraciones |
| **F3 — Flujos operativos WFM** | `wfm.planning-week`, `wfm.import-weekly`, `personnel.team-assignments`, `personnel.import-employees`                                                                 | 2-3 iteraciones |
| **F4 — Flujos complejos**      | `quality.evaluation-form`, `helpdesk.my-tickets`, `helpdesk.manage`, `wfm.manager-approvals`, `wfm.swap-approvals`                                                   | 2-3 iteraciones |
| **F5 — Contextual help**       | `connect.calls`, `operations.realtime`/reportes P2, `knowledge.operator-view`, `filesystem.browser`, `wfm.my-team`, `wfm.intraday-activities`, `quality.*` restantes | 2 iteraciones   |
| **F6 — Estandarización**       | i18n de textos, a11y, telemetría (¿usó el tour?), limpieza de selectores                                                                                             | Continua        |

**Orden por valor:** F1 → F3 (valor core WFM) → F2 (masividad: ~500-1000
operadores) → F4 → F5. Ajustar según feedback de usuarios reales.

---

## 10. Riesgos y mitigaciones

| Riesgo                                                | Prob. | Impacto | Mitigación                                                                                 |
| ----------------------------------------------------- | ----- | ------- | ------------------------------------------------------------------------------------------ |
| Selectores rotos por cambios en Flux UI / vistas      | Alta  | Medio   | `console.warn` de pasos omitidos + test de integridad que cruza `definitions.js` vs blades |
| Overlays huérfanos tras `wire:navigate`               | Media | Medio   | Destruir driver activo en `livewire:navigated`                                             |
| Widgets con auto-refresh rompen el highlight          | Media | Medio   | Anclar a contenedores estables, nunca a nodos internos recreados                           |
| Elementos dentro de modales Flux                      | Media | Medio   | No incluir pasos de modal cerrado; lanzar el tour tras abrir el modal                      |
| Tours auto-iniciados molestan en producción           | Media | Alto    | Un solo auto-tour por sesión; solo P0; siempre cerrable                                    |
| Persistencia por dispositivo (terminales compartidas) | Media | Medio   | MVP localStorage versionado; evaluar tabla por usuario solo si el negocio lo pide          |
| Bundle + JS global crece                              | Baja  | Bajo    | driver.js ≈ 8 kB gzip; no requiere lazy loading                                            |
| Tour largo (UX)                                       | Baja  | Medio   | Máximo 6-7 pasos; cortar o dividir en tours por pestaña                                    |
| Accesibilidad/contraste                               | Baja  | Medio   | Theming AA ya definido; probar navegación con teclado                                      |
| Cobertura de testing                                  | Media | Baja    | Tests de integridad + checklist manual de humo                                             |

---

## 11. Consideraciones de Livewire

1. **`wire:navigate` (SPA):** los tours deben re-arrancar por vista usando
   `livewire:navigated`. Nunca mantener un driver entre vistas.
2. **`$wire`/`$dispatch`:** iniciar tours desde PHP con
   `$this->dispatch('wfm:start-tour', tour: 'module.feature')` (evento ya
   escuchado en `index.js`).
3. **DOM dinámico:** los pasos se filtran por existencia real; usar el helper
   `waitForElements` para elementos lazy (widgets con `loading`).
4. **Re-render parcial:** al editar (p. ej. matriz de planificación) las filas
   se re-renderizan; los `data-tour` deben estar en el nodo padre estable.
5. **Modales Flux:** el overlay de driver.js y el de Flux coexisten; si el paso
   apunta dentro de un modal, se lanza después de abrirlo.
6. **Auto-refresh (10s, 30s):** evitar iniciar tours en vistas con auto-refresh
   activo durante una actualización; anclar a wrappers.
7. **Múltiples instancias:** ya resuelto con el singleton `TourManager`
   (`destroy()` antes de `drive()`).

---

## 12. Estrategia de testing

### Pruebas de comportamiento (prioridad alta)

1. **Integridad selectores ↔ definiciones (Pest, sin dependencias JS):**
   test que recorra los blades del repo y verifique que todo
   `data-tour="<id>"` presente en un blade tiene una entrada homónima en
   `definitions.js` y viceversa (que todo tour definido tiene al menos un
   selector que existe en algún blade). Implementación práctica: grepping
   sobre `definitions.js` y los `*.blade.php` desde PHP.
2. **Rendimiento de vista:** asegurar que añadir `data-tour` no rompe el
   render (smoke: las vistas candidatas responden 200).
3. **Checklist manual de humo (QA):**
   - El tour inicia desde el botón Guía y desde `data-tour-auto`.
   - Todos los pasos resuelven sus elementos (sin omitidos en consola).
   - Navegar con `wire:navigate` durante un tour no deja overlay.
   - Cerrar (ESC / ¡Entendido!) marca el tour como completado y no reaparece.
   - Bump de `version` en definición → el tour vuelve a mostrarse.
   - Elemento dinámico (widget lazy) aparece antes de `drive()`.
   - Dark/light contrast; teclado (Tab/Enter/Esc).
4. **Playwright (opcional, futuro):** si se adopta testing E2E, añadir un
   smoke que verifique que el overlay aparece y avanza.

### No probar
- Implementación interna de `TourManager` (métodos privados) salvo que falle
  comportamiento observable.

---

## 13. Criterios de aceptación para comenzar la implementación

Antes de escalar tours a todos los módulos se debe completar **F1 (MVP)** y
validar:

- [ ] `npm run build` sin errores y bundle estable.
- [ ] Torre de Control: tour funcional por botón y auto-inicio (una sola vez).
- [ ] Cero overlays huérfanos tras navegación SPA.
- [ ] `console.warn` informa pasos omitidos; sin excepciones JS.
- [ ] Test de integridad selectores↔definiciones en verde.
- [ ] Checklist manual de humo aprobado.
- [ ] Renombrado de claves a `module.feature` y versionado funcionando.

---

## 14. Conclusión: qué implementar primero, qué después y qué NO

### 🟢 Qué implementar primero (F1 + F3, máximo valor)
1. **Endurecer la fundación** (helper de espera, destroy en navegación,
   versionado, renombrado de claves, logs).
2. **Torre de Control** (`operations.control-tower`) — MVP, ya cableada.
3. **Matriz de planificación WFM** (`wfm.planning-week`) — el flujo core del
   producto.
4. **Asignación de equipos** (`personnel.team-assignments`) — drag & drop poco
   intuitivo.
5. **Importaciones** (`wfm.import-weekly`, `personnel.import-employees`).

### 🟡 Qué implementar después (F2, F4, F5)
6. Onboarding de operador: **Mi Horario**, **Mi Día**, **Dashboard**,
   **Panel del Agente** (masividad de usuarios).
7. **Evaluación de calidad** (`quality.evaluation-form`).
8. **Helpdesk** (tickets y bandeja) y **aprobaciones** WFM/supervisor.
9. Ayuda contextual P2: **Knowledge**, **Filesystem**, **Operaciones
   realtime/reportes**, **Mi Equipo**, **intradía**.

### 🔴 Qué NO implementar con Driver.js
- **CRUD de catálogos** (≥20 componentes) y **CRUD admin** (users, roles,
  auditoría, geo, comunicaciones, documentación, knowledge admin).
- **Formularios simples de un paso** (permisos, swaps, alta de llamada, CRUD de
  organigrama/personal).
- **Auth, perfil, notificaciones, mantenimiento, cuotas, descargas.**
- **Historiales pasivos** y **hubs** (`ReportingFrameworkIndex`, `ReportGenerator`).
- **Tours largos** (>7 pasos) y **auto-inicio en exceso**: si la solución es un
  label, tooltip, empty state o documentación, eso gana.
