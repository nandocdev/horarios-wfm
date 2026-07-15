# Especificación Técnica Detallada: WfmModule (Workforce Management)

> Documento RUP Centrado en Arquitectura
> **Módulo:** WfmModule
> **Ruta:** `app/Modules/WfmModule`

## 1. Resumen Ejecutivo y Propósito del Módulo

El **WfmModule (Workforce Management)** es el motor central de planificación operativa de la plataforma. Su propósito es organizar y proyectar el tiempo de los empleados (`Schedules`) para asegurar que haya suficiente personal (Cobertura) para atender la demanda proyectada de llamadas telefónicas.

Este módulo no solo gestiona los turnos semanales estáticos, sino que desciende a la micro-gestión diaria (**Intraday Management**), definiendo a qué hora un agente debe ir al baño, comer o tomar una capacitación (`ScheduledActivityDefinition`). Además, actúa como un portal de autogestión para que los empleados soliciten permisos (`Leaves`) o intercambien turnos con sus compañeros (`Shift Swaps`).

---

## 2. Casos de Uso Detallados

Dada la profundidad del módulo, destacamos los tres pilares operativos:

### CU-WFM-01: Planificación Semanal (Scheduling)

- **Actor:** Analista WFM (Workforce Manager).
- **Descripción:** Definir las mallas horarias masivas para la próxima semana.
- **Flujo Principal:**
  1. El Analista WFM ingresa a `WeeklyPlanningTeams`.
  2. Puede importar un archivo Excel (`ImportWeeklyScheduleAction`) con las mallas horarias pre-calculadas en un software de simulación Erlang-C.
  3. El sistema crea la cabecera `WeeklySchedule` y asocia `WeeklyTeamAssignment` para el equipo entero.
  4. Genera los registros individuales de `Schedule` por día para cada agente (Ej. Lunes de 08:00 a 17:00).
  5. El Analista presiona "Publicar", invocando `PublishWeeklyScheduleAction`, lo que despacha notificaciones push/email (`SchedulePublishedNotification`) a todos los agentes afectados.

### CU-WFM-02: Solicitud e Intercambio de Turnos (Shift Swap)

- **Actor:** Agente 1 (Solicitante) y Agente 2 (Receptor).
- **Descripción:** Autogestión de horarios sin intervención inicial del supervisor.
- **Flujo Principal:**
  1. Agente 1 ingresa a `RequestShiftSwap` y ofrece su turno del Viernes a cambio del turno del Sábado del Agente 2.
  2. Se envía un `SwapRequestNotification` al Agente 2.
  3. Si el Agente 2 acepta en su bandeja (`SwapRequestHistory`), el estado pasa a "Pendiente de Aprobación WFM".
  4. El Analista WFM ingresa a `WfmSwapApprovals` y aprueba el cambio.
  5. El `ProcessShiftSwapAction` intercambia los `user_id` en la tabla `Schedule` de esos días y notifica a ambos mediante `ShiftSwapApprovedMail`.

### CU-WFM-03: Programación Intra-Día (Intraday Activities)

- **Actor:** Supervisor / Sistema.
- **Descripción:** Interrupciones planificadas del turno (Breaks, Almuerzos).
- **Flujo Principal:**
  1. Durante la jornada, el sistema (a través de reglas pre-cargadas en `ScheduledActivityDefinition`) sabe que a las 13:00 el agente tiene 1 hora de `ActivityType` "Almuerzo".
  2. Esta información es servida vía API (`GetExpectedAgentStateAction`) al `OperationsModule` para que sepa qué estado exacto debería tener el agente en el CTI en cualquier minuto dado del día, permitiendo calcular la "Adherencia Real".

---

## 3. Requerimientos Funcionales (RF)

- **RF-WFM-01 (Manejo de Excepciones de Horario):** El sistema debe permitir registrar `ScheduleException` (Tardanzas justificadas, permisos pagados, salidas tempranas) que alteren temporalmente la capacidad del turno sin destruir el horario original planificado (`Schedule`).
- **RF-WFM-02 (Colisiones de Horarios):** Al crear o importar horarios, el `SaveScheduleAction` debe validar matemáticamente que no existan traslapes (Overlaps) de tiempo para el mismo empleado en el mismo día.
- **RF-WFM-03 (Motor de Aprobaciones Multinivel):** Las ausencias (`AbsenceReasonCode`) deben seguir un flujo de estado: `Pending` -> `ManagerApproved` -> `WfmApproved`. Si un empleado pide vacaciones, su jefe directo y el departamento de WFM deben dar el Visto Bueno.

---

## 4. Requerimientos No Funcionales (RNF)

- **RNF-WFM-01 (Notificaciones Robustas):** Dado que los horarios cambian frecuentemente, el sistema confía fuertemente en el sistema de `Notifications` nativo de Laravel. Estos deben ser encolados obligatoriamente (`ShouldQueue`) para no penalizar la latencia al publicar mallas horarias para miles de agentes.
- **RNF-WFM-02 (Rendimiento de Calendarios UI):** Las vistas Livewire como `EmployeeWeeklyPlanning` y `MySchedule` deben cargar la data en una matriz JSON ligera y pintarse utilizando Alpine.js o librerías puras de Front-End (FullCalendar) para evitar pesados re-renders de Livewire al navegar de semana en semana.
- **RNF-WFM-03 (Consistencia de Datos Intra-día):** Para el cruce de Adherencia en tiempo real, las consultas a `IntradayActivityAssignment` deben estar cacheadas (Redis) ya que el `OperationsModule` podría consultarlas miles de veces por minuto.

---

## 5. Modelos de Datos Detallados

Este módulo maneja la línea temporal (Time-Series) del empleado:

| Atributo | Tipo / Cast | Descripción y Lógica de Negocio |
| :--- | :--- | :--- |
| **Entidad: `Schedule`** | | **El Turno Diario del Empleado** |
| `id` | `bigint` (PK)| |
| `user_id` | `integer` (FK)| Agente asignado (Relación a `PersonnelModule\Employee`). |
| `date` | `date` | Fecha del turno. |
| `start_time` / `end_time`| `datetime` | Rango horario maestro (ej. 08:00 a 17:00). |
| `is_published` | `boolean` | Solo los horarios publicados son visibles en `MySchedule`. |
| **Entidad: `IntradayActivityAssignment`**| | **Micro-fragmentación del Turno** |
| `schedule_id` | `bigint` (FK)| Turno padre. |
| `activity_type_id`| `integer` (FK)| (Ej. Almuerzo, Break, Capacitación, Backoffice). |
| `start_time` / `end_time`| `datetime` | Rango específico (ej. 13:00 a 14:00). |
| **Entidad: `ScheduleException`**| | **Desviaciones Aprobadas** |
| `schedule_id` | `bigint` (FK)| Turno padre. |
| `absence_reason_code_id`| `integer`| Código de justificación (Ej. Cita Médica). |
| `minutes_deducted`| `integer` | Tiempo que no será pagado (o sí, según el flag del código). |

---

## 6. Roles y Permisos (Policies)

- **`SchedulePolicy`:**
  - `view`: Los empleados solo ven el suyo (y sus excepciones).
  - `create`, `update`, `publish`: Reservado a los roles `WfmAnalyst` o `SuperAdmin`.
- **`WeeklySchedulePolicy`:**
  - Solo el rol WFM puede realizar la carga masiva y publicación de mallas (`wfm.manage`).

---

## 7. Eventos, Listeners y Notificaciones

El WfmModule es el mayor emisor de notificaciones transaccionales:

- **`SchedulePublishedNotification`:** Email masivo resumiendo los días de trabajo de la próxima semana.
- **`ScheduleModifiedNotification`:** Si el Analista cambia la hora de entrada de un agente para *mañana*, se dispara una alerta crítica (`SMS` o Push) al agente.
- **`NotifyShiftSwapApproved` (Listener):** Escucha el evento de aprobación en base de datos y despacha el `ShiftSwapApprovedMail` a los involucrados.

---

## 8. Servicios y Acciones Detallados (Actions & Services)

### `ScheduleService` (Core Lógico)

- Centraliza lógica compleja de validación temporal, cálculo de horas efectivas laborables (Restando *Unpaid Breaks*) y detección de superposiciones (Overlaps).

### `AssignTeamWeeklyScheduleAction`

- **Flujo masivo:** Itera sobre todos los `TeamMember` activos de un equipo (leyendo desde `PersonnelModule`) e inserta en Batch los turnos (`Schedule`) estándar definidos en la plantilla (`WeeklyScheduleAssignment`), optimizando las inserciones en base de datos.

### `GetExpectedAgentStateAction`

- Acción crítica tipo *Getter* (Read-Only). Recibe un `$userId` y un `$timestamp`. Examina el `Schedule` y las `IntradayActivityAssignment` vigentes en ese segundo exacto y retorna el código de estado CTI que el agente debería tener.

---

## 9. Endpoints o Rutas Detalladas (Livewire / API)

- **Portal de Autogestión (Empleados):**
  - `GET /wfm/my-schedule` (`MySchedule`): Calendario mensual personal.
  - `GET /wfm/swap/request` (`RequestShiftSwap`): Interfaz para ofrecer el turno a compañeros.
  - `GET /wfm/leaves/request` (`RequestLeave`): Interfaz para pedir vacaciones/permisos.
- **Portal WFM (Analistas):**
  - `GET /admin/wfm/weekly-planning` (`WeeklyPlanningTeams`): Vista maestra de creación de horarios por bloques.
  - `GET /admin/wfm/intraday` (`ManageIntradayActivities`): Gestor de descansos.
- **API (`api.php`):**
  - `GET /api/wfm/state/{user_id}`: Consumido internamente por Microservicios u `OperationsModule` para consultar el estado esperado.

---

## 10. Dependencias con otros Módulos

- **Dependencia Upstream (`PersonnelModule`):** WFM no puede asignar turnos si no conoce el listado activo de empleados y a qué `Team` pertenecen.
- **Proveedor Downstream hacia `OperationsModule`:** Operaciones es un cliente ciego de WFM; necesita saber los horarios exactos y excepciones para poder calcular las tardanzas y la adherencia final.

---

## 11. Estructura de Carpetas

```tree
app/Modules/WfmModule
├── Actions
│   ├── AssignIntradayActivityAction.php
│   ├── AssignTeamWeeklyScheduleAction.php
│   ├── CreateApprovedIntradayPeriodAction.php
│   ├── CreateScheduleAction.php
│   ├── ImportTeamWeeklyScheduleAction.php
│   ├── ProcessShiftSwapAction.php
│   ├── PublishWeeklyScheduleAction.php
│   ├── Realtime
│   │   └── GetExpectedAgentStateAction.php
│   ├── SaveAbsenceReasonAction.php
│   ├── SaveActivityTypeAction.php
│   ├── SaveAgentStateAction.php
│   ├── SaveScheduleAction.php
│   ├── SaveScheduledActivityAction.php
│   └── UpdateEmployeeDayAssignmentAction.php
├── DTOs
│   └── IntradayActivityDTO.php
├── Emails
├── Listeners
│   └── NotifyShiftSwapApproved.php
├── Livewire
│   ├── EmployeeWeeklyPlanning.php
│   ├── Forms
│   │   ├── AbsenceReasonForm.php
│   │   ├── ActivityTypeForm.php
│   │   ├── AgentStateForm.php
│   │   ├── ExceptionForm.php
│   │   ├── ScheduledActivityForm.php
│   │   └── ScheduleForm.php
│   ├── ImportWeeklySchedule.php
│   ├── LeaveRequestHistory.php
│   ├── ManageAbsenceReasons.php
│   ├── ManageActivityTypes.php
│   ├── ManageAgentStates.php
│   ├── ManageIntradayActivities.php
│   ├── ManagerApprovals.php
│   ├── ManageScheduledActivities.php
│   ├── ManageScheduleExceptions.php
│   ├── ManageSchedules.php
│   ├── MyDay.php
│   ├── MyMetrics.php
│   ├── MySchedule.php
│   ├── MyTeam.php
│   ├── OperationalSettings.php
│   ├── RequestLeave.php
│   ├── RequestShiftSwap.php
│   ├── RequestSummary.php
│   ├── SwapRequestHistory.php
│   ├── TeamWeeklyPlanning.php
│   ├── WeeklyPlanning.php
│   ├── WeeklyPlanningTeams.php
│   └── WfmSwapApprovals.php
├── Mail
│   └── ShiftSwapApprovedMail.php
├── Models
│   ├── AbsenceReasonCode.php
│   ├── ActivityType.php
│   ├── AgentState.php
│   ├── ApprovedIntradayPeriod.php
│   ├── IntradayActivityAssignment.php
│   ├── IntradayActivity.php
│   ├── ScheduledActivityDefinition.php
│   ├── ScheduleException.php
│   ├── Schedule.php
│   ├── WeeklyScheduleAssignment.php
│   ├── WeeklySchedule.php
│   └── WeeklyTeamAssignment.php
├── Notifications
│   ├── AttendanceIncidentNotification.php
│   ├── IntradayActivityNotification.php

│   ├── ScheduleModifiedNotification.php
│   ├── SchedulePublishedNotification.php
│   ├── ScheduleRequestStatusNotification.php
│   ├── ShiftSwapApprovedNotification.php
│   ├── SwapRequestNotification.php
│   └── SwapStatusChangedNotification.php
├── Observers
│   └── LeaveRequestObserver.php
├── Policies
│   ├── AbsenceReasonCodePolicy.php
│   ├── ActivityTypePolicy.php
│   ├── AgentStatePolicy.php
│   ├── ScheduledActivityDefinitionPolicy.php
│   ├── SchedulePolicy.php
│   ├── WeeklyScheduleAssignmentPolicy.php
│   └── WeeklySchedulePolicy.php
├── Providers
│   └── ModuleServiceProvider.php
├── Resources
│   └── Views
│       └── livewire
│           ├── employee-weekly-planning.blade.php
│           ├── import-weekly-schedule.blade.php
│           ├── leave-request-history.blade.php
│           ├── manage-absence-reasons.blade.php
│           ├── manage-activity-types.blade.php
│           ├── manage-agent-states.blade.php
│           ├── manage-intraday-activities.blade.php
│           ├── manager-approvals.blade.php
│           ├── manage-scheduled-activities.blade.php
│           ├── manage-schedule-exceptions.blade.php
│           ├── manage-schedules.blade.php
│           ├── my-day.blade.php
│           ├── my-metrics.blade.php
│           ├── my-schedule.blade.php
│           ├── my-team.blade.php
│           ├── operational-settings.blade.php
│           ├── request-leave.blade.php
│           ├── request-shift-swap.blade.php
│           ├── request-summary.blade.php
│           ├── swap-request-history.blade.php
│           ├── team-weekly-planning.blade.php
│           ├── weekly-planning.blade.php
│           ├── weekly-planning-teams.blade.php
│           └── wfm-swap-approvals.blade.php
├── Routes
│   ├── api.php
│   └── web.php
└── Services
    └── ScheduleService.php
```

---

*Documento técnico profundo generado bajo lineamientos de arquitectura iterativa RUP.*
