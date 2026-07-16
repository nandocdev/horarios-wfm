# Catálogo de Rutas y Vistas — HorariosWFM

> Mapeo completo de rutas, componentes Livewire, controladores, y vistas
> Organizado por la estructura del menú lateral (MenuHelper)
> Versión 1.0 — Julio 2026

---

## Convenciones

| Abreviatura | Significado                                                |
| ----------- | ---------------------------------------------------------- |
| **LW**      | Componente Livewire (ruta GET que renderiza un componente) |
| **CT**      | Controller (controlador HTTP tradicional con métodos)      |
| **CL**      | Closure (función anónima en línea en la ruta)              |
| **API**     | Ruta de API (retorna JSON, sin vista Blade)                |
| **PDF**     | Generación de PDF                                          |

Todas las rutas requieren `auth` salvo que se indique lo contrario. Rutas de administración requieren permisos específicos de Spatie.

---

## 1. Dashboard

### 1.1 Inicio — `home`

| Propiedad         | Valor                                                                                                                                                   |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/`                                                                                                                                                     |
| **Nombre**        | `home`                                                                                                                                                  |
| **Tipo**          | CL → LW                                                                                                                                                 |
| **Componente**    | `CommunicationsModule\Livewire\Home` (reg: `communications.home`)                                                                                       |
| **Vista**         | `communications::livewire.home`                                                                                                                         |
| **Módulo**        | CommunicationsModule                                                                                                                                    |
| **Middleware**    | Ninguno (guest → Home, auth → redirect a dashboard)                                                                                                     |
| **Funcionalidad** | Página de bienvenida/inicio. Si el usuario está autenticado, redirige a `dashboard`. Muestra noticias publicadas, encuestas activas, y reconocimientos. |

### 1.2 Dashboard Principal — `dashboard`

| Propiedad         | Valor                                                                                                                                                                                                                                                                                                                                                                                                          |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/dashboard`                                                                                                                                                                                                                                                                                                                                                                                                   |
| **Nombre**        | `dashboard`                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Tipo**          | LW                                                                                                                                                                                                                                                                                                                                                                                                             |
| **Componente**    | `OperationsModule\Livewire\Dashboard` (reg: `operations.dashboard`)                                                                                                                                                                                                                                                                                                                                            |
| **Vista**         | `operations::livewire.dashboard`                                                                                                                                                                                                                                                                                                                                                                               |
| **Módulo**        | OperationsModule                                                                                                                                                                                                                                                                                                                                                                                               |
| **Middleware**    | `auth`, `verified`                                                                                                                                                                                                                                                                                                                                                                                             |
| **Funcionalidad** | Dashboard principal con KPIs generales (llamadas del día, agentes en línea, nivel de servicio, AHT). Incluye widgets de estado en tiempo real, volumen por cola, alertas críticas, e incidencias recientes.                                                                                                                                                                                                    |
| **Vistas hijas**  | Los widgets son componentes Livewire anidados: `operations.widgets.hero-kpi-widget`, `operations.widgets.queue-stats-widget`, `operations.widgets.state-distribution-widget`, `operations.widgets.volume-comparison-widget`, `operations.widgets.critical-alerts-widget`, `operations.widgets.recent-incidents-widget`. Cada widget se renderiza dentro del dashboard y puede expandirse a su vista detallada. |

---

## 2. Mi Trabajo (Autogestión del Operador)

### 2.1 Mi Horario — `schedules.my-schedule`

| Propiedad         | Valor                                                                                                                                                                                                                                    |
| ----------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/my-schedule/{week?}/{day?}`                                                                                                                                                                                                  |
| **Nombre**        | `schedules.my-schedule`                                                                                                                                                                                                                  |
| **Tipo**          | LW                                                                                                                                                                                                                                       |
| **Componente**    | `WfmModule\Livewire\MySchedule` (reg: `wfm.my-schedule`)                                                                                                                                                                                 |
| **Vista**         | `wfm::livewire.my-schedule`                                                                                                                                                                                                              |
| **Módulo**        | WfmModule                                                                                                                                                                                                                                |
| **Funcionalidad** | Vista semanal del horario del operador autenticado. Muestra asignaciones por día, horas de entrada/salida, almuerzo, descansos. Navegación entre semanas. Parámetros opcionales: `week` (ID de weekly_schedule), `day` (día específico). |
| **Acciones**      | Ver detalle de día, navegar entre semanas. Enlace a solicitar permiso/cambio desde días específicos.                                                                                                                                     |

### 2.2 Mi Día — `schedules.my-day`

| Propiedad         | Valor                                                                                                                                                          |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/my-day`                                                                                                                                            |
| **Nombre**        | `schedules.my-day`                                                                                                                                             |
| **Tipo**          | LW                                                                                                                                                             |
| **Componente**    | `WfmModule\Livewire\MyDay` (reg: `wfm.my-day`)                                                                                                                 |
| **Vista**         | `wfm::livewire.my-day`                                                                                                                                         |
| **Módulo**        | WfmModule                                                                                                                                                      |
| **Funcionalidad** | Vista del día actual del operador. Muestra el horario del día, actividades intradía asignadas, estado actual (basado en telemetría Cisco), y próximos eventos. |
| **Acciones**      | Ver actividades intradía, registrar inicio/fin de jornada (futuro).                                                                                            |

### 2.3 Mis Métricas — `schedules.my-metrics`

| Propiedad         | Valor                                                                                                                                                                                 |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/my-metrics`                                                                                                                                                               |
| **Nombre**        | `schedules.my-metrics`                                                                                                                                                                |
| **Tipo**          | LW                                                                                                                                                                                    |
| **Componente**    | `WfmModule\Livewire\MyMetrics` (reg: `wfm.my-metrics`)                                                                                                                                |
| **Vista**         | `wfm::livewire.my-metrics`                                                                                                                                                            |
| **Módulo**        | WfmModule                                                                                                                                                                             |
| **Funcionalidad** | Panel de métricas personales del operador: llamadas atendidas, AHT, adherencia al horario, productividad, disponibilidad. Datos de `agent_daily_metrics`. Filtro por rango de fechas. |

### 2.4 Solicitar Permiso — `schedules.leave-request`

| Propiedad         | Valor                                                                                                                                                                                                                                                                                                    |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/leave-request/{type?}`                                                                                                                                                                                                                                                                       |
| **Nombre**        | `schedules.leave-request`                                                                                                                                                                                                                                                                                |
| **Tipo**          | LW                                                                                                                                                                                                                                                                                                       |
| **Componente**    | `WfmModule\Livewire\RequestLeave` (no registrado explícitamente)                                                                                                                                                                                                                                         |
| **Vista**         | `wfm::livewire.request-leave`                                                                                                                                                                                                                                                                            |
| **Módulo**        | WfmModule                                                                                                                                                                                                                                                                                                |
| **Middleware**    | `web`, `auth`                                                                                                                                                                                                                                                                                            |
| **Funcionalidad** | Formulario para solicitar permisos (trimestral o compensatorio). Incluye: selección de tipo, fecha, hora inicio/fin, día completo, motivo. Valida saldo disponible (480 min/trimestre para permisos trimestrales). Al enviar, ejecuta `CreateLeaveRequestAction` y dispara evento `LeaveRequestCreated`. |
| **Parámetros**    | `type` (opcional): `quarterly` o `compensatorio`                                                                                                                                                                                                                                                         |
| **Vistas hijas**  | `LeaveRequestHistory` — al enviar, redirige a `schedules.leave-history`                                                                                                                                                                                                                                  |

### 2.5 Solicitar Cambio de Turno — `schedules.swap-request`

| Propiedad         | Valor                                                                                                                                                                                                                                                                           |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/swap-request`                                                                                                                                                                                                                                                       |
| **Nombre**        | `schedules.swap-request`                                                                                                                                                                                                                                                        |
| **Tipo**          | LW                                                                                                                                                                                                                                                                              |
| **Componente**    | `WfmModule\Livewire\RequestShiftSwap` (no registrado explícitamente)                                                                                                                                                                                                            |
| **Vista**         | `wfm::livewire.request-shift-swap`                                                                                                                                                                                                                                              |
| **Módulo**        | WfmModule                                                                                                                                                                                                                                                                       |
| **Funcionalidad** | Formulario para solicitar intercambio de turno con otro operador. Selecciona compañero, fechas, turnos a intercambiar. Incluye validación de disponibilidad del recipiente. Al enviar, ejecuta acción y genera snapshots JSON de las asignaciones originales para trazabilidad. |

### 2.6 Mis Solicitudes — `schedules.swap-history`

| Propiedad         | Valor                                                                                                                                                                          |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **URI**           | `/schedules/swap-history`                                                                                                                                                      |
| **Nombre**        | `schedules.swap-history`                                                                                                                                                       |
| **Tipo**          | LW                                                                                                                                                                             |
| **Componente**    | `WfmModule\Livewire\SwapRequestHistory` (no registrado explícitamente)                                                                                                         |
| **Vista**         | `wfm::livewire.swap-request-history`                                                                                                                                           |
| **Módulo**        | WfmModule                                                                                                                                                                      |
| **Funcionalidad** | Historial de solicitudes de cambio de turno del operador. Muestra estado (pendiente, aprobado, rechazado), fechas, compañeros. Posibilidad de cancelar solicitudes pendientes. |

### 2.7 Historial de Permisos — `schedules.leave-history`

| Propiedad         | Valor                                                                                                  |
| ----------------- | ------------------------------------------------------------------------------------------------------ |
| **URI**           | `/schedules/leave-history`                                                                             |
| **Nombre**        | `schedules.leave-history`                                                                              |
| **Tipo**          | LW                                                                                                     |
| **Componente**    | `WfmModule\Livewire\LeaveRequestHistory` (no registrado explícitamente)                                |
| **Vista**         | `wfm::livewire.leave-request-history`                                                                  |
| **Módulo**        | WfmModule                                                                                              |
| **Funcionalidad** | Historial de solicitudes de permiso del operador. Lista filtrable por estado, tipo, y rango de fechas. |

### 2.8 Notificaciones — `notifications.index`

| Propiedad              | Valor                                                                                                                                                               |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**                | `/notifications`                                                                                                                                                    |
| **Nombre**             | `notifications.index`                                                                                                                                               |
| **Tipo**               | LW                                                                                                                                                                  |
| **Componente**         | `CoreModule\Livewire\Shared\NotificationHistory` (reg: `core.shared.notification-history`)                                                                          |
| **Vista**              | `core::livewire.shared.notification-history`                                                                                                                        |
| **Módulo**             | CoreModule                                                                                                                                                          |
| **Middleware**         | `auth`, `verified`                                                                                                                                                  |
| **Funcionalidad**      | Historial completo de notificaciones del usuario. Notificaciones del sistema (database) y broadcasts (WebSockets via Reverb). Marcar como leídas, filtrar por tipo. |
| **Componente anidado** | `core.shared.notification-bell` — Campana de notificaciones en el navbar (número de no leídas + dropdown).                                                          |

---

## 3. Mi Equipo (Supervisión)

### 3.1 Mi Equipo — `schedules.my-team`

| Propiedad         | Valor                                                                                                                                                                                                                            |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/my-team`                                                                                                                                                                                                             |
| **Nombre**        | `schedules.my-team`                                                                                                                                                                                                              |
| **Tipo**          | LW                                                                                                                                                                                                                               |
| **Componente**    | `WfmModule\Livewire\MyTeam` (no registrado explícitamente)                                                                                                                                                                       |
| **Vista**         | `wfm::livewire.my-team`                                                                                                                                                                                                          |
| **Módulo**        | WfmModule                                                                                                                                                                                                                        |
| **Middleware**    | `web`, `auth`, permiso opcional `schedules.view_team`                                                                                                                                                                            |
| **Funcionalidad** | Vista del equipo a cargo del supervisor. Muestra lista de operadores, su estado actual (vía telemetría Cisco), horario del día, métricas rápidas (adherencia, disponibilidad). Enlace a planificar equipo y aprobar solicitudes. |
| **Acciones**      | Click en operador → ver detalle. Enlace a aprobar permisos, resumen de solicitudes.                                                                                                                                              |

### 3.2 Aprobar Permisos — `schedules.manager-approvals`

| Propiedad         | Valor                                                                                                                                                                      |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/manager-approvals`                                                                                                                                             |
| **Nombre**        | `schedules.manager-approvals`                                                                                                                                              |
| **Tipo**          | LW                                                                                                                                                                         |
| **Componente**    | `WfmModule\Livewire\ManagerApprovals` (no registrado explícitamente)                                                                                                       |
| **Vista**         | `wfm::livewire.manager-approvals`                                                                                                                                          |
| **Módulo**        | WfmModule                                                                                                                                                                  |
| **Funcionalidad** | Bandeja de aprobación de permisos para supervisores. Muestra solicitudes pendientes del equipo. Permite aprobar o rechazar con comentario. Flujo multi-nivel (step_order). |

### 3.3 Resumen de Solicitudes — `schedules.request-summary`

| Propiedad         | Valor                                                                                                                               |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/reports/requests`                                                                                                       |
| **Nombre**        | `schedules.request-summary`                                                                                                         |
| **Tipo**          | LW                                                                                                                                  |
| **Componente**    | `WfmModule\Livewire\RequestSummary` (no registrado explícitamente)                                                                  |
| **Vista**         | `wfm::livewire.request-summary`                                                                                                     |
| **Módulo**        | WfmModule                                                                                                                           |
| **Middleware**    | `web`, `auth`, `can:reports.requests`                                                                                               |
| **Funcionalidad** | Reporte consolidado de solicitudes (permisos + cambios de turno) del equipo. Filtros por estado, tipo, rango de fechas. Exportable. |

---

## 4. Planificación (WFM)

### 4.1 Planificación Semanal — `schedules.planning`

| Propiedad         | Valor                                                                                                                                                                                               |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/planning`                                                                                                                                                                               |
| **Nombre**        | `schedules.planning`                                                                                                                                                                                |
| **Tipo**          | LW                                                                                                                                                                                                  |
| **Componente**    | `WfmModule\Livewire\WeeklyPlanning` (reg: `wfm.weekly-planning`)                                                                                                                                    |
| **Vista**         | `wfm::livewire.weekly-planning`                                                                                                                                                                     |
| **Módulo**        | WfmModule                                                                                                                                                                                           |
| **Middleware**    | `web`, `auth`, permiso `schedules.manage`                                                                                                                                                           |
| **Funcionalidad** | Visión general de planificación semanal. Muestra semanas, su estado (draft/published/archived). Permite crear nuevas semanas, publicar, y navegar a planificación detallada.                        |
| **Vistas hijas**  | `schedules.planning.import` (Importar CSV), `schedules.planning.teams` (Asignación por equipos), `schedules.planning.team` (Planificar equipo), `schedules.planning.employee` (Planificar empleado) |

### 4.2 Planificación por Equipos — `schedules.planning.teams`

| Propiedad         | Valor                                                                                                                                             |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/planning/{week}/teams`                                                                                                                |
| **Nombre**        | `schedules.planning.teams`                                                                                                                        |
| **Tipo**          | LW                                                                                                                                                |
| **Componente**    | `WfmModule\Livewire\WeeklyPlanningTeams` (reg: `wfm.weekly-planning-teams`)                                                                       |
| **Vista**         | `wfm::livewire.weekly-planning-teams`                                                                                                             |
| **Módulo**        | WfmModule                                                                                                                                         |
| **Funcionalidad** | Asignación de horarios base por equipo para una semana específica. Vista tabular: equipos vs días de la semana. Asigna `weekly_team_assignments`. |

### 4.3 Planificar Equipo — `schedules.planning.team`

| Propiedad         | Valor                                                                                                                                                   |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/planning/{week}/team/{team}`                                                                                                                |
| **Nombre**        | `schedules.planning.team`                                                                                                                               |
| **Tipo**          | LW                                                                                                                                                      |
| **Componente**    | `WfmModule\Livewire\TeamWeeklyPlanning` (reg: `wfm.team-weekly-planning`)                                                                               |
| **Vista**         | `wfm::livewire.team-weekly-planning`                                                                                                                    |
| **Módulo**        | WfmModule                                                                                                                                               |
| **Funcionalidad** | Planificación detallada para un equipo específico. Muestra miembros del equipo con sus asignaciones diarias. Permite modificar turnos por empleado/día. |

### 4.4 Planificar Empleado — `schedules.planning.employee`

| Propiedad         | Valor                                                                                                                                                                                                                      |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/planning/{week}/employee/{employee}`                                                                                                                                                                           |
| **Nombre**        | `schedules.planning.employee`                                                                                                                                                                                              |
| **Tipo**          | LW                                                                                                                                                                                                                         |
| **Componente**    | `WfmModule\Livewire\EmployeeWeeklyPlanning` (reg: `wfm.employee-weekly-planning`)                                                                                                                                          |
| **Vista**         | `wfm::livewire.employee-weekly-planning`                                                                                                                                                                                   |
| **Módulo**        | WfmModule                                                                                                                                                                                                                  |
| **Funcionalidad** | Planificación individual para un empleado en una semana. Vista día por día con selector de horario (turno base), horas personalizadas de entrada/salida/almuerzo/descanso. Guarda/actualiza `weekly_schedule_assignments`. |

### 4.5 Importar Planificación — `schedules.planning.import`

| Propiedad         | Valor                                                                                                                                                   |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/planning/{week}/import`                                                                                                                     |
| **Nombre**        | `schedules.planning.import`                                                                                                                             |
| **Tipo**          | LW                                                                                                                                                      |
| **Componente**    | `WfmModule\Livewire\ImportWeeklySchedule` (no registrado explícitamente)                                                                                |
| **Vista**         | `wfm::livewire.import-weekly-schedule`                                                                                                                  |
| **Módulo**        | WfmModule                                                                                                                                               |
| **Funcionalidad** | Importación masiva de planificación semanal via archivo CSV. Mapeo de columnas, previsualización, confirmación. Procesamiento por lotes con validación. |

### 4.6 Turnos Base — `schedules.shifts`

| Propiedad         | Valor                                                                                                                                                   |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/shifts`                                                                                                                                     |
| **Nombre**        | `schedules.shifts`                                                                                                                                      |
| **Tipo**          | LW                                                                                                                                                      |
| **Componente**    | `WfmModule\Livewire\ManageSchedules` (reg: `wfm.manage-schedules`)                                                                                      |
| **Vista**         | `wfm::livewire.manage-schedules`                                                                                                                        |
| **Módulo**        | WfmModule                                                                                                                                               |
| **Middleware**    | `web`, `auth`, permiso `schedules.manage`                                                                                                               |
| **Funcionalidad** | CRUD de turnos base (plantillas de horario). Cada turno tiene: nombre, hora inicio/fin, minutos totales/break/almuerzo, días permitidos, flags de pago. |
| **Formulario**    | `WfmModule\Livewire\Forms\ScheduleForm`                                                                                                                 |

### 4.7 Actividades Intradía — `schedules.intraday-activities.manage`

| Propiedad         | Valor                                                                                                                                                                                                                        |
| ----------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/intraday-activities/manage`                                                                                                                                                                                      |
| **Nombre**        | `schedules.intraday-activities.manage`                                                                                                                                                                                       |
| **Tipo**          | LW                                                                                                                                                                                                                           |
| **Componente**    | `WfmModule\Livewire\ManageIntradayActivities` (no registrado explícitamente)                                                                                                                                                 |
| **Vista**         | `wfm::livewire.manage-intraday-activities`                                                                                                                                                                                   |
| **Módulo**        | WfmModule                                                                                                                                                                                                                    |
| **Middleware**    | `web`, `auth`, permiso `wfm.intraday.manage`                                                                                                                                                                                 |
| **Funcionalidad** | Gestión de actividades intradía: aprobar períodos para equipos (con max_slots de capacidad), visualizar actividades asignadas, asignar slots libres a operadores. Usa PostgreSQL `tstzrange` con exclusión de solapamientos. |
| **Entidades**     | `ApprovedIntradayPeriod`, `IntradayActivity`, `IntradayActivityAssignment`                                                                                                                                                   |

### 4.8 Actividades Programadas — `schedules.scheduled-activities`

| Propiedad         | Valor                                                                                                                                                  |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **URI**           | `/schedules/scheduled-activities`                                                                                                                      |
| **Nombre**        | `schedules.scheduled-activities`                                                                                                                       |
| **Tipo**          | LW                                                                                                                                                     |
| **Componente**    | `WfmModule\Livewire\ManageScheduledActivities` (reg: `wfm.manage-scheduled-activities`)                                                                |
| **Vista**         | `wfm::livewire.manage-scheduled-activities`                                                                                                            |
| **Módulo**        | WfmModule                                                                                                                                              |
| **Middleware**    | `web`, `auth`, permiso `wfm.catalogs.scheduled_defs`                                                                                                   |
| **Funcionalidad** | CRUD de definiciones de actividades programadas (capacitaciones, reuniones, etc.). Nombre, tipo de actividad, duración default, ubicación, instructor. |
| **Formulario**    | `WfmModule\Livewire\Forms\ScheduledActivityForm`                                                                                                       |

### 4.9 Excepciones de Horario — `schedules.exceptions`

| Propiedad         | Valor                                                                                                                                                                                                               |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/exceptions`                                                                                                                                                                                             |
| **Nombre**        | `schedules.exceptions`                                                                                                                                                                                              |
| **Tipo**          | LW                                                                                                                                                                                                                  |
| **Componente**    | `WfmModule\Livewire\ManageScheduleExceptions` (no registrado explícitamente)                                                                                                                                        |
| **Vista**         | `wfm::livewire.manage-schedule-exceptions`                                                                                                                                                                          |
| **Módulo**        | WfmModule                                                                                                                                                                                                           |
| **Middleware**    | `web`, `auth`, permiso `wfm.exceptions.manage`                                                                                                                                                                      |
| **Funcionalidad** | Gestión de excepciones de horario: inasistencias, permisos no planificados, llegadas tardías, salidas tempranas. Catálogo de `absence_reason_codes`. Soporta origen polimórfico (leave_request, swap_manual, etc.). |
| **Formulario**    | `WfmModule\Livewire\Forms\ExceptionForm`                                                                                                                                                                            |

### 4.10 Tipos de Actividad — `schedules.activity-types`

| Propiedad         | Valor                                                                                                       |
| ----------------- | ----------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/activity-types`                                                                                 |
| **Nombre**        | `schedules.activity-types`                                                                                  |
| **Tipo**          | LW                                                                                                          |
| **Componente**    | `WfmModule\Livewire\ManageActivityTypes` (reg: `wfm.manage-activity-types`)                                 |
| **Vista**         | `wfm::livewire.manage-activity-types`                                                                       |
| **Módulo**        | WfmModule                                                                                                   |
| **Middleware**    | `web`, `auth`, permiso `wfm.catalogs.activities`                                                            |
| **Funcionalidad** | CRUD de tipos de actividad (capacitación, reunión, descanso, etc.). Nombre, color, flags productivo/pagado. |
| **Formulario**    | `WfmModule\Livewire\Forms\ActivityTypeForm`                                                                 |

### 4.11 Motivos de Ausencia — `schedules.absence-reasons`

| Propiedad         | Valor                                                                                       |
| ----------------- | ------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/absence-reasons`                                                                |
| **Nombre**        | `schedules.absence-reasons`                                                                 |
| **Tipo**          | LW                                                                                          |
| **Componente**    | `WfmModule\Livewire\ManageAbsenceReasons` (reg: `wfm.manage-absence-reasons`)               |
| **Vista**         | `wfm::livewire.manage-absence-reasons`                                                      |
| **Módulo**        | WfmModule                                                                                   |
| **Middleware**    | `web`, `auth`, permiso `wfm.catalogs.absences`                                              |
| **Funcionalidad** | CRUD de códigos de ausencia. Nombre, código corto, requiere adjunto, es justificado, color. |
| **Formulario**    | `WfmModule\Livewire\Forms\AbsenceReasonForm`                                                |

### 4.12 Estados de Agente — `schedules.agent-states`

| Propiedad         | Valor                                                                                                                    |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------ |
| **URI**           | `/schedules/agent-states`                                                                                                |
| **Nombre**        | `schedules.agent-states`                                                                                                 |
| **Tipo**          | LW                                                                                                                       |
| **Componente**    | `WfmModule\Livewire\ManageAgentStates` (reg: `wfm.manage-agent-states`)                                                  |
| **Vista**         | `wfm::livewire.manage-agent-states`                                                                                      |
| **Módulo**        | WfmModule                                                                                                                |
| **Middleware**    | `web`, `auth`, permiso `wfm.catalogs.agent_states`                                                                       |
| **Funcionalidad** | CRUD de catálogo de estados de agente. Mapeo entre código externo (Cisco) y nombre mostrado. Flag productivo, color hex. |
| **Formulario**    | `WfmModule\Livewire\Forms\AgentStateForm`                                                                                |

### 4.13 Aprobar Cambios de Turno (WFM) — `schedules.wfm-approvals`

| Propiedad         | Valor                                                                                                                                     |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/wfm-approvals`                                                                                                                |
| **Nombre**        | `schedules.wfm-approvals`                                                                                                                 |
| **Tipo**          | LW                                                                                                                                        |
| **Componente**    | `WfmModule\Livewire\WfmSwapApprovals` (no registrado explícitamente)                                                                      |
| **Vista**         | `wfm::livewire.wfm-swap-approvals`                                                                                                        |
| **Módulo**        | WfmModule                                                                                                                                 |
| **Middleware**    | `web`, `auth`, `can:wfm.swaps.manage`                                                                                                     |
| **Funcionalidad** | Bandeja de aprobación de swaps para personal WFM. Aprueba/rechaza solicitudes de intercambio de turno que requieren validación adicional. |

---

## 5. Operaciones

### 5.1 Monitoreo en Tiempo Real — `operations.realtime`

| Propiedad         | Valor                                                                                                                                                                                                                                                                                             |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/operations/realtime`                                                                                                                                                                                                                                                                            |
| **Nombre**        | `operations.realtime`                                                                                                                                                                                                                                                                             |
| **Tipo**          | LW                                                                                                                                                                                                                                                                                                |
| **Componente**    | `OperationsModule\Livewire\RealtimeMonitoring` (reg: `operations.realtime-monitoring`)                                                                                                                                                                                                            |
| **Vista**         | `operations::livewire.realtime-monitoring`                                                                                                                                                                                                                                                        |
| **Módulo**        | OperationsModule                                                                                                                                                                                                                                                                                  |
| **Middleware**    | `web`, `auth`, permiso `wfm.realtime.view`                                                                                                                                                                                                                                                        |
| **Funcionalidad** | Panel de monitoreo en vivo de agentes. Datos de `agent_realtime_states` (actualizado cada 5s por CiscoSync). Mapa de estados, filtros por equipo. Incluye `operations.agent-realtime-card` (tarjeta individual por agente) y `operations.agent-timeline` (línea de tiempo de estados del agente). |

### 5.2 Reporte Diario — `operations.daily-report`

| Propiedad         | Valor                                                                                                                                                                                                                          |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **URI**           | `/operations/reporte-diario`                                                                                                                                                                                                   |
| **Nombre**        | `operations.daily-report`                                                                                                                                                                                                      |
| **Tipo**          | LW                                                                                                                                                                                                                             |
| **Componente**    | `OperationsModule\Livewire\DailyReport` (reg: `operations.daily-report`)                                                                                                                                                       |
| **Vista**         | `operations::livewire.daily-report`                                                                                                                                                                                            |
| **Módulo**        | OperationsModule                                                                                                                                                                                                               |
| **Funcionalidad** | Reporte consolidado del día: llamadas por cola, agentes programados vs reales, AHT, nivel de servicio, incidencias de asistencia. Incluye asignaciones temporales (temporal_assignments) en el cálculo de personal disponible. |

### 5.3 Disponibilidad Intradía — `operations.availability`

| Propiedad         | Valor                                                                                                                                                                     |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/operations/availability`                                                                                                                                                |
| **Nombre**        | `operations.availability`                                                                                                                                                 |
| **Tipo**          | LW                                                                                                                                                                        |
| **Componente**    | `OperationsModule\Livewire\IntradayAvailability` (reg: `operations.intraday-availability`)                                                                                |
| **Vista**         | `operations::livewire.intraday-availability`                                                                                                                              |
| **Módulo**        | OperationsModule                                                                                                                                                          |
| **Funcionalidad** | Vista de disponibilidad intradía: agentes programados, en línea, en llamada, en pausa, ausentes. Proyección por intervalos de 30/60 min. Brecha entre programado vs real. |

### 5.4 Desempeño por Cola — `operations.queue-performance`

| Propiedad         | Valor                                                                                                                                              |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/operations/queue-performance`                                                                                                                    |
| **Nombre**        | `operations.queue-performance`                                                                                                                     |
| **Tipo**          | LW                                                                                                                                                 |
| **Componente**    | `OperationsModule\Livewire\QueuePerformanceReport` (reg: `operations.queue-performance-report`)                                                    |
| **Vista**         | `operations::livewire.queue-performance-report`                                                                                                    |
| **Módulo**        | OperationsModule                                                                                                                                   |
| **Funcionalidad** | Reporte de desempeño por cola (CSQ): llamadas atendidas, abandonadas, nivel de servicio, AHT, ASA. Datos de `csq_realtime_stats` + `call_records`. |

### 5.5 Scorecard de Desempeño — `operations.performance`

| Propiedad         | Valor                                                                                                                                                    |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/operations/performance`                                                                                                                                |
| **Nombre**        | `operations.performance`                                                                                                                                 |
| **Tipo**          | LW                                                                                                                                                       |
| **Componente**    | `OperationsModule\Livewire\PerformanceScorecard` (reg: `operations.performance-scorecard`)                                                               |
| **Vista**         | `operations::livewire.performance-scorecard`                                                                                                             |
| **Módulo**        | OperationsModule                                                                                                                                         |
| **Funcionalidad** | Scorecard comparativo de agentes: productividad, adherencia, calidad (integración QualityModule), asistencia. Vista de tabla con ordenamiento y filtros. |

### 5.6 Dashboard de Agente — `operations.agent-performance`

| Propiedad         | Valor                                                                                                                                                                         |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/operations/agent-performance/{employee?}`                                                                                                                                   |
| **Nombre**        | `operations.agent-performance`                                                                                                                                                |
| **Tipo**          | LW                                                                                                                                                                            |
| **Componente**    | `OperationsModule\Livewire\AgentPerformanceDashboard` (reg: `operations.agent-performance-dashboard`)                                                                         |
| **Vista**         | `operations::livewire.agent-performance-dashboard`                                                                                                                            |
| **Módulo**        | OperationsModule                                                                                                                                                              |
| **Funcionalidad** | Dashboard detallado de un agente específico. Métricas históricas, tendencias, comparativas contra equipo y metas (KPI goals de `operational_settings`). Selector de empleado. |
| **PDF**           | `/operations/reports/performance/{employee}/pdf` → `EmployeePerformanceReport` PDF descargable.                                                                               |

### 5.7 Dashboard de Productividad — `operations.advanced-analytics`

| Propiedad         | Valor                                                                                                                                                               |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/operations/advanced-analytics`                                                                                                                                    |
| **Nombre**        | `operations.advanced-analytics`                                                                                                                                     |
| **Tipo**          | LW                                                                                                                                                                  |
| **Componente**    | `OperationsModule\Livewire\AdvancedProductivityDashboard` (reg: `operations.advanced-productivity-dashboard`)                                                       |
| **Vista**         | `operations::livewire.advanced-productivity-dashboard`                                                                                                              |
| **Módulo**        | OperationsModule                                                                                                                                                    |
| **Funcionalidad** | Dashboard avanzado de productividad con gráficos (ApexCharts): tendencias semanales/mensuales, distribución de tiempos, capacidad vs demanda, heatmap por hora/día. |

### 5.8 Resumen por Equipo — `operations.team-performance`

| Propiedad         | Valor                                                                                                                            |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/operations/team-performance`                                                                                                   |
| **Nombre**        | `operations.team-performance`                                                                                                    |
| **Tipo**          | LW                                                                                                                               |
| **Componente**    | `OperationsModule\Livewire\TeamPerformanceSummary` (reg: `operations.team-performance-summary`)                                  |
| **Vista**         | `operations::livewire.team-performance-summary`                                                                                  |
| **Módulo**        | OperationsModule                                                                                                                 |
| **Middleware**    | `web`, `auth`, permiso `schedules.view_team`                                                                                     |
| **Funcionalidad** | Resumen de desempeño por equipo: KPIs consolidados (ocupación, disponibilidad, AHT, adherencia), ranking de equipos, tendencias. |

### 5.9 Marco de Reportes — `operations.reports`

| Propiedad         | Valor                                                                                                                                                                                           |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/operations/reports`                                                                                                                                                                           |
| **Nombre**        | `operations.reports`                                                                                                                                                                            |
| **Tipo**          | LW                                                                                                                                                                                              |
| **Componente**    | `OperationsModule\Livewire\ReportingFrameworkIndex` (reg: `operations.reporting-index`)                                                                                                         |
| **Vista**         | `operations::livewire.reporting-framework-index`                                                                                                                                                |
| **Módulo**        | OperationsModule                                                                                                                                                                                |
| **Funcionalidad** | Marco/índice de reportes disponibles. Lista de reportes predefinidos con descripciones, parámetros y enlaces de descarga (PDF/CSV). Punto de entrada único para todos los reportes del sistema. |

---

## 6. Calidad (QualityModule)

### 6.1 Evaluaciones — `quality.evaluations.index`

| Propiedad         | Valor                                                                                                                                                 |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/evaluaciones`                                                                                                                                       |
| **Nombre**        | `evaluations.index`                                                                                                                                   |
| **Tipo**          | LW                                                                                                                                                    |
| **Componente**    | `QualityModule\Livewire\EvaluationIndex` (reg: `quality.evaluation-index`)                                                                            |
| **Vista**         | `quality::livewire.evaluation-index`                                                                                                                  |
| **Módulo**        | QualityModule                                                                                                                                         |
| **Middleware**    | `can:quality.evaluations.view`                                                                                                                        |
| **Funcionalidad** | Listado de evaluaciones de calidad. Filtros por evaluador, evaluado, cola, estado, rango de fechas. Vista de tabla con scores, red flags, y acciones. |

### 6.2 Nueva Evaluación — `quality.evaluations.create`

| URI                   | Nombre               |
| --------------------- | -------------------- |
| `/evaluaciones/crear` | `evaluations.create` |

| Propiedad         | Valor                                                                                                      |
| ----------------- | ---------------------------------------------------------------------------------------------------------- |
| **Tipo**          | LW                                                                                                         |
| **Componente**    | `QualityModule\Livewire\TeamEvaluationSelector` (no registrado explícitamente)                             |
| **Vista**         | `quality::livewire.team-evaluation-selector`                                                               |
| **Middleware**    | `can:quality.evaluations.create`                                                                           |
| **Funcionalidad** | Selector de empleado a evaluar. Paso 1 del flujo de evaluación. Lista operadores del equipo del evaluador. |

### 6.3 Formulario de Evaluación — `quality.evaluations.form`

| URI                              | Nombre             |
| -------------------------------- | ------------------ |
| `/evaluaciones/crear/{employee}` | `evaluations.form` |

| Propiedad         | Valor                                                                                                                                                                                                                                               |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Tipo**          | LW                                                                                                                                                                                                                                                  |
| **Componente**    | `QualityModule\Livewire\EvaluationForm` (reg: `quality.evaluation-form`)                                                                                                                                                                            |
| **Vista**         | `quality::livewire.evaluation-form`                                                                                                                                                                                                                 |
| **Middleware**    | `can:quality.evaluations.create`                                                                                                                                                                                                                    |
| **Funcionalidad** | Formulario de evaluación para un empleado específico. Carga criterios activos de la cola del empleado, permite puntuar cada criterio, detectar red flags, agregar observaciones. Calcula score total. Usa `EvaluationFormData` (Livewire Form DTO). |
| **Acción**        | Ejecuta `CreateEvaluationAction` y dispara evento `EvaluationCompleted`.                                                                                                                                                                            |

### 6.4 Detalle de Evaluación — `quality.evaluations.show`

| URI                          | Nombre             |
| ---------------------------- | ------------------ |
| `/evaluaciones/{evaluation}` | `evaluations.show` |

| Propiedad         | Valor                                                                                                                    |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------ |
| **Tipo**          | LW                                                                                                                       |
| **Componente**    | `QualityModule\Livewire\EvaluationDetail` (reg: `quality.evaluation-detail`)                                             |
| **Vista**         | `quality::livewire.evaluation-detail`                                                                                    |
| **Middleware**    | `can:quality.evaluations.view`                                                                                           |
| **Funcionalidad** | Detalle completo de una evaluación: puntajes por criterio, red flags, observaciones, historial de cambios (calibración). |

### 6.5 Feedback de Evaluación — `quality.feedback.create`

| URI                                   | Nombre            |
| ------------------------------------- | ----------------- |
| `/evaluaciones/{evaluation}/feedback` | `feedback.create` |

| Propiedad         | Valor                                                                                                                         |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| **Tipo**          | LW                                                                                                                            |
| **Componente**    | `QualityModule\Livewire\FeedbackForm` (reg: `quality.feedback-form`)                                                          |
| **Vista**         | `quality::livewire.feedback-form`                                                                                             |
| **Middleware**    | `can:quality.feedback.create`                                                                                                 |
| **Funcionalidad** | Formulario de feedback para una evaluación completada. El evaluador redacta observaciones y recomendaciones para el evaluado. |

### 6.6 Calibración — `quality.calibrations.create`

| URI                                   | Nombre                |
| ------------------------------------- | --------------------- |
| `/evaluaciones/{evaluation}/calibrar` | `calibrations.create` |

| Propiedad         | Valor                                                                                                                                                                |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Tipo**          | LW                                                                                                                                                                   |
| **Componente**    | `QualityModule\Livewire\CalibrationForm` (reg: `quality.calibration-form`)                                                                                           |
| **Vista**         | `quality::livewire.calibration-form`                                                                                                                                 |
| **Middleware**    | `can:quality.calibrations.create`                                                                                                                                    |
| **Funcionalidad** | Formulario de calibración (re-evaluación). Permite ajustar el score de una evaluación existente, registrando el score anterior y nuevo en `quality_calibration_log`. |

### 6.7 Criterios — `quality.criteria.index`

| URI          | Nombre           |
| ------------ | ---------------- |
| `/criterios` | `criteria.index` |

| Propiedad         | Valor                                                                                                                                       |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| **Tipo**          | LW                                                                                                                                          |
| **Componente**    | `QualityModule\Livewire\CriteriaList` (reg: `quality.criteria-list`)                                                                        |
| **Vista**         | `quality::livewire.criteria-list`                                                                                                           |
| **Middleware**    | `can:quality.criteria.view`                                                                                                                 |
| **Funcionalidad** | Listado de criterios de evaluación. Cada criterio tiene versiones (histórico de cambios). Vista de árbol con versiones activas e inactivas. |

### 6.8 Crear/Editar Criterio — `quality.criteria.create` / `quality.criteria.edit`

| URI                            | Nombre            |
| ------------------------------ | ----------------- |
| `/criterios/crear`             | `criteria.create` |
| `/criterios/{criteria}/editar` | `criteria.edit`   |

| Propiedad         | Valor                                                                                                                               |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| **Tipo**          | LW                                                                                                                                  |
| **Componente**    | `QualityModule\Livewire\CriteriaForm` (reg: `quality.criteria-form`)                                                                |
| **Vista**         | `quality::livewire.criteria-form`                                                                                                   |
| **Middleware**    | `can:quality.criteria.create` o `can:quality.criteria.update`                                                                       |
| **Funcionalidad** | Formulario para crear/editar criterios y sus versiones. Define texto del criterio, puntaje máximo, descripción, fechas de vigencia. |

### 6.9 Colas de Calidad — `quality.queues.index`

| URI      | Nombre         |
| -------- | -------------- |
| `/colas` | `queues.index` |

| Propiedad         | Valor                                                                                            |
| ----------------- | ------------------------------------------------------------------------------------------------ |
| **Tipo**          | LW                                                                                               |
| **Componente**    | `QualityModule\Livewire\QueueList` (reg: `quality.queue-list`)                                   |
| **Vista**         | `quality::livewire.queue-list`                                                                   |
| **Middleware**    | `can:quality.queues.manage`                                                                      |
| **Funcionalidad** | CRUD de colas de calidad. Cada cola tiene código (ej: CM-Tr, AU, Farm), nombre, activo/inactivo. |

### 6.10 Criterios por Cola — `quality.queues.criteria`

| URI                | Nombre            |
| ------------------ | ----------------- |
| `/colas/criterios` | `queues.criteria` |

| Propiedad         | Valor                                                                                                                       |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------- |
| **Tipo**          | LW                                                                                                                          |
| **Componente**    | `QualityModule\Livewire\ManageQueueCriteria` (reg: `quality.manage-queue-criteria`)                                         |
| **Vista**         | `quality::livewire.manage-queue-criteria`                                                                                   |
| **Middleware**    | `can:quality.criteria.view`                                                                                                 |
| **Funcionalidad** | Asignación de criterios-versiones a colas. Define orden de aparición, activación/desactivación. Tabla de asignación masiva. |

---

## 7. Centro de Contacto

### 7.1 Mis Datos (Dashboard Agente) — `contact-center.agent-dashboard`

| Propiedad         | Valor                                                                                                                                        |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/contact-center/my-dashboard`                                                                                                               |
| **Nombre**        | `contact-center.agent-dashboard`                                                                                                             |
| **Tipo**          | LW                                                                                                                                           |
| **Componente**    | `ConnectModule\Livewire\AgentDashboard` (auto-descubierto)                                                                                   |
| **Vista**         | `connect::livewire.agent-dashboard`                                                                                                          |
| **Módulo**        | ConnectModule                                                                                                                                |
| **Funcionalidad** | Dashboard personal del agente: métricas del día (llamadas, AHT, TMO), llamadas recientes, estado actual. Selector de rango (hoy/semana/mes). |

### 7.2 Dashboard General — `contact-center.general-dashboard`

| Propiedad         | Valor                                                                                                               |
| ----------------- | ------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/contact-center/general-dashboard`                                                                                 |
| **Nombre**        | `contact-center.general-dashboard`                                                                                  |
| **Tipo**          | LW                                                                                                                  |
| **Componente**    | `ConnectModule\Livewire\GeneralDashboard` (auto-descubierto)                                                        |
| **Vista**         | `connect::livewire.general-dashboard`                                                                               |
| **Módulo**        | ConnectModule                                                                                                       |
| **Funcionalidad** | Dashboard general del centro de contacto: estado de colas, agentes en línea, llamadas en espera, nivel de servicio. |

### 7.3 Llamadas — `contact-center.calls.index`

| Propiedad         | Valor                                                                                                                  |
| ----------------- | ---------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/contact-center/calls`                                                                                                |
| **Nombre**        | `contact-center.calls.index`                                                                                           |
| **Tipo**          | LW                                                                                                                     |
| **Componente**    | `ConnectModule\Livewire\ListCallRecords` (auto-descubierto)                                                            |
| **Vista**         | `connect::livewire.list-call-records`                                                                                  |
| **Módulo**        | ConnectModule                                                                                                          |
| **Middleware**    | `auth`, permiso `call_records.viewAny`                                                                                 |
| **Funcionalidad** | Listado de llamadas con filtros (fecha, cola, agente, estado, ciudadano). Tabla paginada con detalles de cada llamada. |

### 7.4 Crear Llamada — `contact-center.calls.create`

| Propiedad         | Valor                                                                                                      |
| ----------------- | ---------------------------------------------------------------------------------------------------------- |
| **URI**           | `/contact-center/calls/create`                                                                             |
| **Nombre**        | `contact-center.calls.create`                                                                              |
| **Tipo**          | LW                                                                                                         |
| **Componente**    | `ConnectModule\Livewire\CreateCallRecord` (auto-descubierto)                                               |
| **Vista**         | `connect::livewire.create-call-record`                                                                     |
| **Módulo**        | ConnectModule                                                                                              |
| **Funcionalidad** | Formulario para crear un registro de llamada manual. Datos: número, cola, subtipo, ciudadano, descripción. |
| **Formulario**    | `ConnectModule\Livewire\Forms\CreateCallRecordForm`                                                        |

### 7.5 Editar Llamada — `contact-center.calls.edit`

| Propiedad         | Valor                                                                                                               |
| ----------------- | ------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/contact-center/calls/{callRecord}/edit`                                                                           |
| **Nombre**        | `contact-center.calls.edit`                                                                                         |
| **Tipo**          | LW                                                                                                                  |
| **Componente**    | `ConnectModule\Livewire\EditCallRecord` (auto-descubierto)                                                          |
| **Vista**         | `connect::livewire.edit-call-record`                                                                                |
| **Módulo**        | ConnectModule                                                                                                       |
| **Funcionalidad** | Edición de un registro de llamada existente. Permite completar datos faltantes (descripción, subtipo, disposición). |
| **Formulario**    | `ConnectModule\Livewire\Forms\CompleteCallRecordForm`                                                               |

### 7.6 Colas — `contact-center.admin.queues.index`

| Propiedad         | Valor                                                                |
| ----------------- | -------------------------------------------------------------------- |
| **URI**           | `/contact-center/catalogs/queues`                                    |
| **Nombre**        | `contact-center.admin.queues.index`                                  |
| **Tipo**          | LW                                                                   |
| **Componente**    | `ConnectModule\Livewire\ListCallQueues` (auto-descubierto)           |
| **Vista**         | `connect::livewire.list-call-queues`                                 |
| **Módulo**        | ConnectModule                                                        |
| **Middleware**    | `auth`, permiso `call_queues.manage`                                 |
| **Funcionalidad** | CRUD de colas (CSQs). Nombre, canal asociado, descripción, AHT goal. |
| **Formulario**    | `ConnectModule\Livewire\Forms\CallQueueForm`                         |

### 7.7 Canales — `contact-center.admin.channels.index`

| Propiedad         | Valor                                                    |
| ----------------- | -------------------------------------------------------- |
| **URI**           | `/contact-center/catalogs/channels`                      |
| **Nombre**        | `contact-center.admin.channels.index`                    |
| **Tipo**          | LW                                                       |
| **Componente**    | `ConnectModule\Livewire\ListChannels` (auto-descubierto) |
| **Vista**         | `connect::livewire.list-channels`                        |
| **Módulo**        | ConnectModule                                            |
| **Middleware**    | `auth`, permiso `channels.manage`                        |
| **Funcionalidad** | CRUD de canales de atención (Voz, Chat, Web, etc.).      |
| **Formulario**    | `ConnectModule\Livewire\Forms\ChannelForm`               |

### 7.8 Subtipos de Caso — `contact-center.admin.subtypes.index`

| Propiedad         | Valor                                                                     |
| ----------------- | ------------------------------------------------------------------------- |
| **URI**           | `/contact-center/catalogs/subtypes`                                       |
| **Nombre**        | `contact-center.admin.subtypes.index`                                     |
| **Tipo**          | LW                                                                        |
| **Componente**    | `ConnectModule\Livewire\ListCaseSubtypes` (auto-descubierto)              |
| **Vista**         | `connect::livewire.list-case-subtypes`                                    |
| **Módulo**        | ConnectModule                                                             |
| **Middleware**    | `auth`, permiso `case_subtypes.manage`                                    |
| **Funcionalidad** | CRUD de subtipos de caso/tramite por cola. Código, nombre, cola asociada. |
| **Formulario**    | `ConnectModule\Livewire\Forms\CaseSubtypeForm`                            |

### 7.9 APIs de Centro de Contacto

| Método | URI                                        | Nombre                                | Controlador                             | Funcionalidad                                                         |
| ------ | ------------------------------------------ | ------------------------------------- | --------------------------------------- | --------------------------------------------------------------------- |
| POST   | `/api/contact-center/calls/start`          | `contact-center.call-start`           | `CallRecordController::start`           | Inicia registro de llamada desde integración Cisco. Sin auth:sanctum. |
| PUT    | `/api/contact-center/calls/{id}/complete`  | `contact-center.call-complete`        | `CallRecordController::complete`        | Completa datos de llamada.                                            |
| PUT    | `/api/contact-center/calls/{id}/close`     | `contact-center.call-close`           | `CallRecordController::close`           | Cierra llamada. Sin auth:sanctum.                                     |
| GET    | `/api/contact-center/subtypes`             | `contact-center.subtypes.index`       | `CallRecordController::subtypes`        | Lista subtipos para dropdown.                                         |
| GET    | `/api/contact-center/cisco/agent-snapshot` | `contact-center.cisco.agent-snapshot` | `CiscoFinesseController::agentSnapshot` | Snapshot JSON de estado actual de agentes desde Cisco.                |

---

## 8. Comunicaciones

### 8.1 Inicio (Muro de Noticias) — `home`

(Véase sección 1.1 — es la misma ruta `/`)

La página de inicio funciona como muro de noticias: muestra noticias publicadas (aprobadas), encuestas activas, y reconocimientos recientes. Comentarios y reacciones habilitados.

**Componente Home** (`CommunicationsModule\Livewire\Home`): timeline de contenido social del call center.

### 8.2 Noticias (Admin) — `communications.news.*`

| URI                                      | Nombre                               | Componente                                       | Funcionalidad                                                                    |
| ---------------------------------------- | ------------------------------------ | ------------------------------------------------ | -------------------------------------------------------------------------------- |
| `/admin/communications/news`             | `communications.news.index`          | `ListNews` (reg: `communications.list-news`)     | Listado de noticias con filtros (estado, autor, fecha)                           |
| `/admin/communications/news/create`      | `communications.news.create`         | `CreateNews` (reg: `communications.create-news`) | Formulario de creación (título, slug, extracto, contenido, programación, estado) |
| `/admin/communications/news/{news}/edit` | `communications.news.edit`           | `EditNews` (reg: `communications.edit-news`)     | Edición de noticia existente                                                     |
| **Vista compartida**                     | `communications::livewire.news-form` | `NewsForm` (Form)                                | Formulario Livewire reutilizado por CreateNews y EditNews                        |

**Acciones POST via `ContentModerationController`:**
- `communications.moderation.approve` — Aprobar noticia/pendiente
- `communications.moderation.reject` — Rechazar con motivo
- `communications.moderation.archive` — Archivar
- `communications.moderation.submit-review` — Enviar a revisión

### 8.3 Encuestas (Admin) — `communications.polls.*`

| URI                                  | Nombre                        | Componente   | Funcionalidad                                                |
| ------------------------------------ | ----------------------------- | ------------ | ------------------------------------------------------------ |
| `/admin/communications/polls`        | `communications.polls.index`  | `ListPolls`  | Listado de encuestas                                         |
| `/admin/communications/polls/create` | `communications.polls.create` | `CreatePoll` | Formulario de creación (pregunta, opciones JSON, expiración) |
| **Formulario**                       |                               | `PollForm`   | Livewire Form Object                                         |

### 8.4 Reconocimientos (Admin) — `communications.shoutouts.*`

| URI                                               | Nombre                                   | Componente            | Funcionalidad                             |
| ------------------------------------------------- | ---------------------------------------- | --------------------- | ----------------------------------------- |
| `/admin/communications/shoutouts`                 | `communications.shoutouts.index`         | `ListShoutouts`       | Listado de reconocimientos con moderación |
| `/admin/communications/shoutouts/create`          | `communications.shoutouts.create`        | `CreateShoutout`      | Crear reconocimiento a un empleado        |
| `/admin/communications/shoutouts/{shoutout}/edit` | `communications.shoutouts.edit`          | `EditShoutout`        | Editar reconocimiento                     |
| **Vista compartida**                              | `communications::livewire.shoutout-form` | `ShoutoutForm` (Form) |                                           |

### 8.5 Categorías y Tags — `communications.admin.*`

| Grupo      | Rutas                                  | Controlador          |
| ---------- | -------------------------------------- | -------------------- |
| Categorías | `admin/communications/categories` CRUD | `CategoryController` |
| Tags       | `admin/communications/tags` CRUD       | `TagController`      |

### 8.6 Moderación — `communications.moderation.*`

(Ver ContentModerationController en sección 8.2)

### 8.7 Interacciones Sociales (Públicas)

| Método | URI                               | Nombre                           | Propósito                                                                   |
| ------ | --------------------------------- | -------------------------------- | --------------------------------------------------------------------------- |
| POST   | `/news/{news}/comments`           | `communications.comments.store`  | Agregar comentario a noticia (incluye respuesta a comentario via parent_id) |
| POST   | `/shoutouts/{shoutout}/reactions` | `communications.reactions.store` | Reaccionar a reconocimiento                                                 |

---

## 9. Soporte (Helpdesk)

### 9.1 Mis Tickets — `helpdesk.my-tickets`

| Propiedad         | Valor                                                                                      |
| ----------------- | ------------------------------------------------------------------------------------------ |
| **URI**           | `/helpdesk/my-tickets`                                                                     |
| **Nombre**        | `helpdesk.my-tickets`                                                                      |
| **Tipo**          | LW                                                                                         |
| **Componente**    | `HelpdeskModule\Livewire\MyTickets` (reg: `helpdesk.my-tickets`)                           |
| **Vista**         | `helpdesk::livewire.my-tickets`                                                            |
| **Módulo**        | HelpdeskModule                                                                             |
| **Middleware**    | `auth`                                                                                     |
| **Funcionalidad** | Tickets del operador autenticado. Crea nuevo ticket, lista activos, historial de cerrados. |

### 9.2 Bandeja de Soporte — `helpdesk.manage`

| Propiedad         | Valor                                                                                                 |
| ----------------- | ----------------------------------------------------------------------------------------------------- |
| **URI**           | `/helpdesk/manage`                                                                                    |
| **Nombre**        | `helpdesk.manage`                                                                                     |
| **Tipo**          | LW                                                                                                    |
| **Componente**    | `HelpdeskModule\Livewire\ManageTickets` (reg: `helpdesk.manage-tickets`)                              |
| **Vista**         | `helpdesk::livewire.manage-tickets`                                                                   |
| **Módulo**        | HelpdeskModule                                                                                        |
| **Middleware**    | `auth`, permiso `helpdesk.manage`                                                                     |
| **Funcionalidad** | Bandeja de gestión para agentes de soporte. Lista de tickets, asignación, cambio de estado/prioridad. |

### 9.3 Detalle de Ticket — `helpdesk.ticket.detail`

| Propiedad         | Valor                                                                                                                    |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------ |
| **URI**           | `/helpdesk/ticket/{ticket}`                                                                                              |
| **Nombre**        | `helpdesk.ticket.detail`                                                                                                 |
| **Tipo**          | LW                                                                                                                       |
| **Componente**    | `HelpdeskModule\Livewire\TicketDetail` (reg: `helpdesk.ticket-detail`)                                                   |
| **Vista**         | `helpdesk::livewire.ticket-detail`                                                                                       |
| **Módulo**        | HelpdeskModule                                                                                                           |
| **Funcionalidad** | Vista detallada de ticket con conversación (comentarios públicos e internos), cambios de estado, SLA. Adjuntar archivos. |

---

## 10. Base de Conocimiento

### 10.1 Vista Operador — `knowledge.index`

| Propiedad         | Valor                                                                                                             |
| ----------------- | ----------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/knowledge`                                                                                                      |
| **Nombre**        | `knowledge.index`                                                                                                 |
| **Tipo**          | LW                                                                                                                |
| **Componente**    | `KnowledgeModule\Livewire\OperatorView` (reg: `knowledge.operator-view`)                                          |
| **Vista**         | `knowledge::livewire.operator-view`                                                                               |
| **Módulo**        | KnowledgeModule                                                                                                   |
| **Middleware**    | `auth`                                                                                                            |
| **Funcionalidad** | Vista para operadores: búsqueda de artículos por palabra clave, categoría, cola asociada. Resultados en tarjetas. |

### 10.2 Detalle de Artículo — `knowledge.show`

| Propiedad         | Valor                                                                               |
| ----------------- | ----------------------------------------------------------------------------------- |
| **URI**           | `/knowledge/{slug}`                                                                 |
| **Nombre**        | `knowledge.show`                                                                    |
| **Tipo**          | LW                                                                                  |
| **Componente**    | `KnowledgeModule\Livewire\KnowledgeArticleDetail` (reg: `knowledge.article-detail`) |
| **Vista**         | `knowledge::livewire.knowledge-article-detail`                                      |
| **Módulo**        | KnowledgeModule                                                                     |
| **Middleware**    | `auth`                                                                              |
| **Funcionalidad** | Artículo completo con contenido, metadatos, versiones.                              |

### 10.3 Administrar Artículos — `knowledge.admin`, `knowledge.create`, `knowledge.edit`

| URI                          | Nombre             | Componente                                                   |
| ---------------------------- | ------------------ | ------------------------------------------------------------ |
| `/admin/knowledge`           | `knowledge.admin`  | `ManageKnowledgeArticles` (reg: `knowledge.manage-articles`) |
| `/admin/knowledge/create`    | `knowledge.create` | `UpsertKnowledgeArticle` (reg: `knowledge.upsert-article`)   |
| `/admin/knowledge/{id}/edit` | `knowledge.edit`   | `UpsertKnowledgeArticle` (reutilizado)                       |

| Propiedad         | Valor                                                                                                                              |
| ----------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| **Módulo**        | KnowledgeModule                                                                                                                    |
| **Middleware**    | `auth`, `can:knowledge.manage`                                                                                                     |
| **Funcionalidad** | CRUD completo de artículos: listado, creación/edición con versionado automático, asignación a colas y tags, control de expiración. |
| **Formulario**    | `KnowledgeArticleForm`                                                                                                             |

---

## 11. Documentación

### 11.1 Artículos (Público) — `documentation.index`, `documentation.show`

| URI            | Nombre                | Componente                                                       | Vista                                                |
| -------------- | --------------------- | ---------------------------------------------------------------- | ---------------------------------------------------- |
| `/docs`        | `documentation.index` | `WikiArticleIndex` (reg: `documentation.public.article-index`)   | `documentation::livewire.public.wiki-article-index`  |
| `/docs/{slug}` | `documentation.show`  | `WikiArticleDetail` (reg: `documentation.public.article-detail`) | `documentation::livewire.public.wiki-article-detail` |

**Funcionalidad:** Wiki/documentación del sistema. Artículos con slugs, contenido HTML, conteo de visitas.

### 11.2 Administrar Artículos — `documentation.admin.articles`

| Propiedad         | Valor                                                                                                |
| ----------------- | ---------------------------------------------------------------------------------------------------- |
| **URI**           | `/admin/documentation/articles`                                                                      |
| **Nombre**        | `documentation.admin.articles`                                                                       |
| **Tipo**          | LW                                                                                                   |
| **Componente**    | `DocumentationModule\Livewire\Admin\ManageWikiArticles` (reg: `documentation.admin.manage-articles`) |
| **Vista**         | `documentation::livewire.admin.manage-wiki-articles`                                                 |
| **Middleware**    | `can:articles.manage`                                                                                |
| **Funcionalidad** | CRUD de artículos de documentación. Listado con filtros, editor.                                     |

---

## 12. Archivos

### 12.1 Explorador de Archivos — `filesystem.index`

| Propiedad         | Valor                                                                                                                                                                                                       |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/filesystem`                                                                                                                                                                                               |
| **Nombre**        | `filesystem.index`                                                                                                                                                                                          |
| **Tipo**          | LW                                                                                                                                                                                                          |
| **Componente**    | `FilesystemModule\Livewire\FileBrowser` (reg: `filesystem.browser`)                                                                                                                                         |
| **Vista**         | `filesystem::livewire.file-browser`                                                                                                                                                                         |
| **Módulo**        | FilesystemModule                                                                                                                                                                                            |
| **Middleware**    | `auth`                                                                                                                                                                                                      |
| **Funcionalidad** | Explorador de archivos con: navegación por carpetas, subida de archivos (hasta 100MB), descarga, eliminación, compartir con otros usuarios, toggle público/privado. Vistas: "Mis Archivos" y "Compartidos". |
| **Acciones**      | `createFolder()`, `updatedUploads()` (subida batch), `download()`, `delete()`, `share()`, `processShare()`, `togglePublic()`, `navigateTo()`                                                                |

### 12.2 Centro de Descargas — `filesystem.download-center`

| Propiedad         | Valor                                                                                 |
| ----------------- | ------------------------------------------------------------------------------------- |
| **URI**           | `/descargas`                                                                          |
| **Nombre**        | `filesystem.download-center`                                                          |
| **Tipo**          | LW                                                                                    |
| **Componente**    | `FilesystemModule\Livewire\DownloadCenter` (reg: `filesystem.download-center`)        |
| **Vista**         | `filesystem::livewire.download-center`                                                |
| **Funcionalidad** | Catálogo de archivos públicos/compartidos para descarga. Sin autenticación requerida. |

### 12.3 Cuotas de Almacenamiento — `filesystem.quotas`

| Propiedad         | Valor                                                                                      |
| ----------------- | ------------------------------------------------------------------------------------------ |
| **URI**           | `/filesystem/quotas`                                                                       |
| **Nombre**        | `filesystem.quotas`                                                                        |
| **Tipo**          | LW                                                                                         |
| **Componente**    | `FilesystemModule\Livewire\QuotaManager` (reg: `filesystem.quota-manager`)                 |
| **Vista**         | `filesystem::livewire.quota-manager`                                                       |
| **Middleware**    | `auth`                                                                                     |
| **Funcionalidad** | Gestión de cuotas de almacenamiento por usuario/equipo (polimórfico via `storage_quotas`). |

---

## 13. Administración

### 13.1 Empleados — `employees.*`

| URI                              | Nombre                   | Tipo                               | Funcionalidad                                |
| -------------------------------- | ------------------------ | ---------------------------------- | -------------------------------------------- |
| `/employees`                     | `employees.index`        | CT → `EmployeeController::index`   | Listado con filtros, paginación, exportación |
| `/employees/create`              | `employees.create`       | CT → `EmployeeController::create`  | Formulario de creación (Blade tradicional)   |
| `/employees` (POST)              | `employees.store`        | CT → `EmployeeController::store`   | Guardar nuevo empleado                       |
| `/employees/import`              | `employees.import`       | CT → `EmployeeController::import`  | Importación masiva via archivo (Excel/CSV)   |
| `/employees/export`              | `employees.export`       | CT → `EmployeeExportController`    | Exportación a Excel                          |
| `/employees/{employee}`          | `employees.show`         | CT → `EmployeeController::show`    | Perfil completo del empleado                 |
| `/employees/{employee}/edit`     | `employees.edit`         | CT → `EmployeeController::edit`    | Formulario de edición                        |
| `/employees/{employee}` (PUT)    | `employees.update`       | CT → `EmployeeController::update`  | Actualizar empleado                          |
| `/employees/{employee}` (DELETE) | `employees.destroy`      | CT → `EmployeeController::destroy` | Soft-delete                                  |
| `/employees/teams/manage`        | `employees.teams.manage` | LW `ManageTeamAssignments`         | Asignación masiva de empleados a equipos     |

**Módulo:** PersonnelModule (Controladores HTTP tradicionales — no Livewire, excepto teams/manage).

### 13.2 Organigrama

#### 13.2.1 Direcciones — `organization.directorates.*`

| URI                                             | Nombre                             | Componente          |
| ----------------------------------------------- | ---------------------------------- | ------------------- |
| `/organization/directorates`                    | `organization.directorates.index`  | `ListDirectorates`  |
| `/organization/directorates/create`             | `organization.directorates.create` | `CreateDirectorate` |
| `/organization/directorates/{directorate}`      | `organization.directorates.show`   | `ShowDirectorate`   |
| `/organization/directorates/{directorate}/edit` | `organization.directorates.edit`   | `EditDirectorate`   |

#### 13.2.2 Departamentos — `organization.departments.*`

| URI                                           | Nombre                            | Componente         |
| --------------------------------------------- | --------------------------------- | ------------------ |
| `/organization/departments`                   | `organization.departments.index`  | `ListDepartments`  |
| `/organization/departments/create`            | `organization.departments.create` | `CreateDepartment` |
| `/organization/departments/{department}`      | `organization.departments.show`   | `ShowDepartment`   |
| `/organization/departments/{department}/edit` | `organization.departments.edit`   | `EditDepartment`   |

#### 13.2.3 Cargos — `organization.positions.*`

| URI                                       | Nombre                          | Componente       |
| ----------------------------------------- | ------------------------------- | ---------------- |
| `/organization/positions`                 | `organization.positions.index`  | `ListPositions`  |
| `/organization/positions/create`          | `organization.positions.create` | `CreatePosition` |
| `/organization/positions/{position}`      | `organization.positions.show`   | `ShowPosition`   |
| `/organization/positions/{position}/edit` | `organization.positions.edit`   | `EditPosition`   |

### 13.3 Equipos — `organization.teams.*`

| URI                                   | Nombre                        | Componente           | Funcionalidad                         |
| ------------------------------------- | ----------------------------- | -------------------- | ------------------------------------- |
| `/organization/teams`                 | `organization.teams.index`    | `ListTeams`          | Listado de equipos con supervisor     |
| `/organization/teams/create`          | `organization.teams.create`   | `CreateTeam`         | Crear equipo                          |
| `/organization/teams/{team}`          | `organization.teams.show`     | `ShowTeam`           | Perfil del equipo: miembros, horarios |
| `/organization/teams/{team}/edit`     | `organization.teams.edit`     | `EditTeam`           | Editar datos del equipo               |
| `/organization/teams/{team}/members`  | `organization.teams.members`  | `ManageTeamMembers`  | Gestión de miembros (agregar/quitar)  |
| `/organization/teams/{team}/transfer` | `organization.teams.transfer` | `TeamMemberTransfer` | Transferir miembros entre equipos     |

### 13.4 Ubicaciones — `location.*`

| URI                              | Nombre               | Controlador                     | Funcionalidad                    |
| -------------------------------- | -------------------- | ------------------------------- | -------------------------------- |
| `/location`                      | `location.index`     | `LocationController::index`     | Gestión geográfica               |
| `/location/provinces`            | `location.provinces` | `LocationController::provinces` | CRUD provincias                  |
| `/location/districts/{province}` | `location.districts` | `LocationController::districts` | CRUD distritos por provincia     |
| `/location/townships/{district}` | `location.townships` | `LocationController::townships` | CRUD corregimientos por distrito |

### 13.5 Usuarios — `users.*`

| URI                        | Nombre         | Componente                                   | Middleware         |
| -------------------------- | -------------- | -------------------------------------------- | ------------------ |
| `/admin/users`             | `users.index`  | `ListUsers` (reg: `core.users.list-users`)   | `can:users.view`   |
| `/admin/users/create`      | `users.create` | `CreateUser` (reg: `core.users.create-user`) | `can:users.create` |
| `/admin/users/{user}/edit` | `users.edit`   | `EditUser` (reg: `core.users.edit-user`)     | `can:users.edit`   |

**Funcionalidad:** CRUD de usuarios del sistema. Asignación de roles. Cada usuario puede tener un Employee asociado (1:1).

### 13.6 Roles y Permisos — `roles.index`

| Propiedad         | Valor                                                                                                                       |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/admin/roles`                                                                                                              |
| **Nombre**        | `roles.index`                                                                                                               |
| **Tipo**          | LW                                                                                                                          |
| **Componente**    | `CoreModule\Livewire\Roles\ListRoles` (reg: `core.roles.list-roles`)                                                        |
| **Vista**         | `core::livewire.roles.list-roles`                                                                                           |
| **Middleware**    | `can:roles.view`                                                                                                            |
| **Funcionalidad** | Gestión de roles y asignación de permisos. Vista de matriz: roles × permisos. Crear/editar roles, asignar/remover permisos. |

### 13.7 Configuración Operativa — `schedules.operational-settings`

| Propiedad         | Valor                                                                                                                                                           |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/schedules/operational-settings`                                                                                                                               |
| **Nombre**        | `schedules.operational-settings`                                                                                                                                |
| **Tipo**          | LW                                                                                                                                                              |
| **Componente**    | `WfmModule\Livewire\OperationalSettings` (no registrado explícitamente)                                                                                         |
| **Vista**         | `wfm::livewire.operational-settings`                                                                                                                            |
| **Middleware**    | `can:wfm.settings.manage`                                                                                                                                       |
| **Funcionalidad** | Configuración de parámetros operativos (key-value con categoría): metas KPI, horarios laborales, umbrales de alerta, etc. Almacenado en `operational_settings`. |

### 13.8 Auditoría — `audit.*`

| URI                   | Nombre         | Componente/Controller                          | Middleware              |
| --------------------- | -------------- | ---------------------------------------------- | ----------------------- |
| `/admin/audit`        | `audit.index`  | `ListAuditLogs` (reg: `audit.list-audit-logs`) | `can:viewAny, AuditLog` |
| `/admin/audit/export` | `audit.export` | `AuditExportController::export`                | `can:export, AuditLog`  |

**Funcionalidad:** Log de auditoría append-only de todas las operaciones CRUD. Vista de tabla con filtros (entidad, acción, usuario, fecha). Exportación a Excel/CSV.

### 13.9 Categorías y Etiquetas — `communications.admin.*`

(Véase sección 8.5 — rutas CRUD via `CategoryController` y `TagController`)

### 13.10 Moderación de Contenido — `communications.moderation.*`

(Véase sección 8.2 — `ContentModerationController`)

### 13.11 Reportes de Personal — `personnel.staffing-summary`

| Propiedad         | Valor                                                                                                                   |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/personnel/reports/staffing`                                                                                           |
| **Nombre**        | `personnel.staffing-summary`                                                                                            |
| **Tipo**          | LW                                                                                                                      |
| **Componente**    | `PersonnelModule\Livewire\StaffingSummary` (no registrado explícitamente)                                               |
| **Vista**         | `personnel::livewire.staffing-summary`                                                                                  |
| **Middleware**    | `can:reports.staffing`                                                                                                  |
| **Funcionalidad** | Reporte de personal: plantilla autorizada vs ocupada, rotación, distribución por equipo/departamento, métricas de RRHH. |

### 13.12 Mantenimiento del Sistema — `admin.system.maintenance`

| Propiedad         | Valor                                                                                                                   |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------- |
| **URI**           | `/admin/system/maintenance`                                                                                             |
| **Nombre**        | `admin.system.maintenance`                                                                                              |
| **Tipo**          | LW                                                                                                                      |
| **Componente**    | `CoreModule\Livewire\SystemMaintenance` (no registrado explícitamente)                                                  |
| **Vista**         | `core::livewire.system-maintenance`                                                                                     |
| **Middleware**    | `can:admin.system`                                                                                                      |
| **Funcionalidad** | Panel de mantenimiento: modo mantenimiento, limpieza de caché, información del sistema, estado de colas, Health checks. |

---

## 14. Configuración de Perfil

Rutas definidas en `routes/settings.php`:

| URI                    | Nombre            | Componente                   | Middleware                                        |
| ---------------------- | ----------------- | ---------------------------- | ------------------------------------------------- |
| `/settings/profile`    | `profile.edit`    | `pages::settings.profile`    | `auth`                                            |
| `/settings/appearance` | `appearance.edit` | `pages::settings.appearance` | `auth`, `verified`                                |
| `/settings/security`   | `security.edit`   | `pages::settings.security`   | `auth`, `verified`, `RequirePasswordUnlessForced` |

**Funcionalidad:**
- **Profile:** Editar nombre, email, foto de perfil.
- **Appearance:** Tema (claro/oscuro/sistema), idioma.
- **Security:** Cambio de contraseña, 2FA (TOTP via Fortify), recuperación.

---

## 15. Resumen General

| Sección            | Rutas    | Componentes Livewire | Controladores | APIs   |
| ------------------ | -------- | -------------------- | ------------- | ------ |
| Dashboard          | 2        | 1 (+6 widgets)       | 0             | 0      |
| Mi Trabajo         | 8        | 8                    | 0             | 0      |
| Mi Equipo          | 3        | 3                    | 0             | 0      |
| Planificación      | 13       | 13                   | 0             | 0      |
| Operaciones        | 10       | 9 (+6 widgets)       | 0             | 1 PDF  |
| Calidad            | 10       | 9                    | 0             | 0      |
| Centro de Contacto | 12       | 7                    | 1             | 5 API  |
| Comunicaciones     | 16       | 5                    | 5             | 2 POST |
| Soporte            | 3        | 3                    | 0             | 0      |
| Base Conocimiento  | 5        | 4                    | 0             | 0      |
| Documentación      | 3        | 3                    | 0             | 0      |
| Archivos           | 3        | 3                    | 0             | 0      |
| Administración     | 30+      | 18                   | 4             | 0      |
| Perfil             | 3        | 3                    | 0             | 0      |
| **Total**          | **~121** | **~92**              | **~10**       | **~8** |
