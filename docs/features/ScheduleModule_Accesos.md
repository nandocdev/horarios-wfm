# WfmModule — Funcionalidades y Matriz de Acceso

> Generado el 15-04-2026. Fuentes verificadas:
> `app/Modules/WfmModule/Routes/web.php`,
> `app/Modules/WfmModule/Policies/`,
> `database/seeders/RolesAndPermissionsSeeder.php`,
> `app/Helpers/MenuHelper.php`

---

## Roles del sistema (jerarquía ascendente)

| Rol           | Código | Nivel |
| ------------- | ------ | ----- |
| `operator`    | OP     | 1     |
| `supervisor`  | SUP    | 2     |
| `coordinator` | COOR   | 3     |
| `chief`       | JEF    | 4     |
| `wfm`         | WFM    | 5     |
| `director`    | DIR    | 6     |
| `admin`       | ADM    | 99    |

---

## Permisos del módulo Schedule (catálogo completo)

| Permiso                   | Descripción funcional                              |
| ------------------------- | -------------------------------------------------- |
| `schedules.view`          | Listar y ver turnos base del catálogo              |
| `schedules.manage`        | Crear, editar y desactivar turnos base             |
| `weekly_schedules.view`   | Ver planificaciones semanales                      |
| `weekly_schedules.manage` | Crear, editar, publicar y clonar semanas           |
| `overrides.manage`        | Crear y eliminar excepciones/mutaciones operativas |
| `requests.view`           | Ver solicitudes de permisos y permutas             |
| `requests.create`         | Crear solicitudes (empleado operador)              |
| `requests.manage`         | Aprobar o rechazar solicitudes                     |
| `operations.view`         | Ver asistencia e incidencias                       |
| `operations.manage`       | Registrar y editar incidencias operativas          |
| `analytics.view`          | Acceder a reportes, cobertura y capturas           |

---

## Asignación de permisos por rol

| Permiso                   | operator | supervisor | coordinator | chief |  wfm  | director | admin |
| ------------------------- | :------: | :--------: | :---------: | :---: | :---: | :------: | :---: |
| `schedules.view`          |    ✅     |     ✅      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |
| `schedules.manage`        |    —     |     —      |      —      |   —   |   ✅   |    ✅     |   ✅   |
| `weekly_schedules.view`   |    ✅     |     ✅      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |
| `weekly_schedules.manage` |    —     |     —      |      —      |   —   |   ✅   |    ✅     |   ✅   |
| `overrides.manage`        |    —     |     —      |      ✅      |   —   |   ✅   |    ✅     |   ✅   |
| `requests.create`         |    ✅     |     —      |      —      |   —   |   —   |    —     |   ✅   |
| `requests.view`           |    —     |     —      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |
| `requests.manage`         |    —     |     —      |      ✅      |   ✅   |   —   |    ✅     |   ✅   |
| `operations.view`         |    ✅     |     ✅      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |
| `operations.manage`       |    —     |     —      |      ✅      |   ✅   |   —   |    ✅     |   ✅   |
| `analytics.view`          |    —     |     —      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |

> **`admin`** recibe todos los permisos del sistema vía `syncPermissions(Permission::all())`.
> **`wfm`** tiene control total del módulo de scheduling incluyendo la gestión de equipos y empleados.

---

## Menú Horarios — Entradas y control de acceso

### 1. Mi Horario
- **Ruta:** `GET /schedules/my-schedule` (`schedules.my-schedule`)
- **Componente:** `ManageSchedules → MySchedule`
- **Visibilidad en menú:** Sin permiso — visible para cualquier usuario autenticado
- **Control de acceso en ruta:** Solo requiere `auth` (middleware)
- **Descripción:** Vista personal del agente sobre sus turnos asignados en la semana activa.
- **Roles con acceso:** Todos los roles autenticados

---

### 2. Mis Métricas
- **Ruta:** `GET /schedules/my-metrics` (`schedules.my-metrics`)
- **Componente:** `MyMetrics`
- **Visibilidad en menú:** Sin permiso — visible para cualquier usuario autenticado
- **Control de acceso en ruta:** Solo requiere `auth`
- **Descripción:** Dashboard personal de adherencia, asistencia y minutos programados vs reales.
- **Roles con acceso:** Todos los roles autenticados

---

### 3. Turnos (Catálogo base)
- **Ruta:** `GET /schedules/shifts` (`schedules.index`)
- **Componente:** `ManageSchedules`
- **Visibilidad en menú:** `gate: ['viewAny', Schedule::class]`
- **Policy aplicada:** `SchedulePolicy::viewAny`
  - Requiere: `schedules.view` **O** `schedules.manage` **O** `weekly_schedules.manage`
- **Acciones disponibles por permiso:**

  | Acción           | Permiso requerido  |
  | ---------------- | ------------------ |
  | Listar turnos    | `schedules.view`   |
  | Crear turno      | `schedules.manage` |
  | Editar turno     | `schedules.manage` |
  | Desactivar turno | `schedules.manage` |

- **Roles con acceso al menú:** operator, supervisor, coordinator, chief, wfm, director, admin
- **Roles con gestión completa:** wfm, director, admin

---

### 4. Planificación Semanal
- **Ruta principal:** `GET /schedules/weekly-planning` (`schedules.planning`)
- **Rutas derivadas:**
  - `GET /schedules/weekly-planning/{week}/assignments` → `WeeklyPlanningGrid`
  - `GET /schedules/weekly-planning/{week}/team/{team}` → `TeamWeeklyPlanningDetail`
  - `GET /schedules/weekly-planning/{week}/agent/{employee}` → `AgentWeeklyScheduleEditor`
- **Componente principal:** `ManageWeeklySchedules`
- **Visibilidad en menú:** `gate: ['viewAny', WeeklySchedule::class]`
- **Policy aplicada:** `WeeklySchedulePolicy::viewAny`
  - Requiere: `weekly_schedules.view` **O** `weekly_schedules.manage`
- **Acciones disponibles por permiso:**

  | Acción                     | Permiso requerido         |
  | -------------------------- | ------------------------- |
  | Ver semanas planificadas   | `weekly_schedules.view`   |
  | Crear nueva semana (draft) | `weekly_schedules.manage` |
  | Asignar turnos por equipo  | `weekly_schedules.manage` |
  | Asignar turno por agente   | `weekly_schedules.manage` |
  | Clonar semana anterior     | `weekly_schedules.manage` |
  | Publicar semana (bloquear) | `weekly_schedules.manage` |

- **Nota de negocio:** La publicación de una semana es **irreversible**; un validador interno bloquea la acción si la semana está vacía.
- **Roles con acceso al menú:** operator, supervisor, coordinator, chief, wfm, director, admin
- **Roles con gestión completa:** wfm, director, admin

---

### 5. Excepciones (Overrides)
- **Ruta:** `GET /schedules/overrides` (`schedules.overrides`)
- **Componente:** `ManageOverrides`
- **Visibilidad en menú:** `permission: operations.view`
- **Policy aplicada:** `ScheduleOverridePolicy`
  - Ver: rol `wfm` **O** permiso `operations.view`
  - Crear / Editar / Eliminar: rol `wfm` exclusivamente
- **Acciones disponibles por rol:**

  | Acción                     |  wfm  | coordinator¹ | director | admin |
  | -------------------------- | :---: | :----------: | :------: | :---: |
  | Ver listado de excepciones |   ✅   |      ✅       |    ✅     |   ✅   |
  | Crear excepción operativa  |   ✅   |      —       |    —     |   ✅   |
  | Editar excepción           |   ✅   |      —       |    —     |   ✅   |
  | Eliminar excepción         |   ✅   |      —       |    —     |   ✅   |

  > ¹ `coordinator` ve el menú por `operations.view` pero la Policy solo permite CRUD a `wfm`.

- **Nota de negocio:** Los overrides de tipo `leave` (ausentismo) se pueden registrar sin importar si la semana está publicada o en draft. El resto de tipos requieren semana publicada.

---

### 6. Ausencias (Leave Requests)
- **Ruta:** `GET /schedules/requests/leaves` (`schedules.requests.leaves`)
- **Componente:** `ManageLeaveRequests`
- **Visibilidad en menú:** `permission: operations.view`
- **Policy aplicada:** `LeaveRequestPolicy`
  - Ver: rol `wfm` **O** permiso `operations.view`
  - Aprobar / Rechazar: rol `wfm` exclusivamente
- **Acciones disponibles:**

  | Acción           |  wfm  | coordinator | chief | director | admin |
  | ---------------- | :---: | :---------: | :---: | :------: | :---: |
  | Ver solicitudes  |   ✅   |      ✅      |   ✅   |    ✅     |   ✅   |
  | Aprobar permiso  |   ✅   |      —      |   —   |    —     |   ✅   |
  | Rechazar permiso |   ✅   |      —      |   —   |    —     |   ✅   |

- **Nota de negocio:** Al aprobar un permiso se inyecta automáticamente un `ScheduleOverride` de tipo `leave` con prioridad 100 (máxima), que es el "ganador absoluto" en la vista efectiva del empleado.

---

### 7. Permutas (Shift Swaps)
- **Ruta:** `GET /schedules/requests/swaps` (`schedules.requests.swaps`)
- **Componente:** `ManageShiftSwaps`
- **Visibilidad en menú:** `permission: operations.view`
- **Policy aplicada:** `ShiftSwapPolicy`
  - Ver: rol `wfm` **O** permiso `operations.view`
  - Aprobar / Rechazar: rol `wfm` exclusivamente
- **Acciones disponibles:**

  | Acción          |  wfm  | coordinator | chief | director | admin |
  | --------------- | :---: | :---------: | :---: | :------: | :---: |
  | Ver permutas    |   ✅   |      ✅      |   ✅   |    ✅     |   ✅   |
  | Aprobar permuta |   ✅   |      —      |   —   |    —     |   ✅   |

---

### 8. Asistencia
- **Ruta:** `GET /schedules/operations/attendance` (`schedules.operations.attendance`)
- **Componente:** `ViewAttendance`
- **Visibilidad en menú:** `permission: operations.view`
- **Policy aplicada:** `AttendancePolicy`
  - Ver: rol `wfm` **O** permiso `operations.view`
  - Registrar: rol `wfm` exclusivamente
- **Roles con acceso al menú:** operator, coordinator, chief, wfm, director, admin

---

### 9. Incidencias
- **Ruta:** `GET /schedules/operations/incidents` (`schedules.operations.incidents`)
- **Componente:** `ManageIncidents`
- **Visibilidad en menú:** `permission: operations.view`
- **Policy aplicada:** `IncidentPolicy`
  - Ver: rol `wfm` **O** permiso `operations.view`
  - Crear incidencia: rol `wfm` exclusivamente
- **Roles con acceso al menú:** operator, coordinator, chief, wfm, director, admin

---

### 10. Cobertura
- **Ruta:** `GET /schedules/analytics/coverage` (`schedules.analytics.coverage`)
- **Componente:** `ViewCoverage`
- **Visibilidad en menú:** `permission: operations.view`
- **Control de acceso en ruta:** Middleware `auth`
- **Descripción:** Visualización de cobertura real vs planificada por equipo y día.
- **Roles con acceso:** coordinator, chief, wfm, director, admin

---

### 11. Reportes
- **Ruta:** `GET /schedules/analytics/reports` (`schedules.analytics.reports`)
- **Componente:** `ViewAnalytics`
- **Visibilidad en menú:** `permission: operations.view`
- **Descripción:** Reportes consolidados de adherencia, ausentismo y cobertura.
- **Roles con acceso:** coordinator, chief, wfm, director, admin

---

### 12. Capturas (Snapshots)
- **Ruta:** `GET /schedules/analytics/snapshots` (`schedules.analytics.snapshots`)
- **Componente:** `ManageSnapshots`
- **Visibilidad en menú:** `permission: operations.view`
- **Descripción:** Histórico de snapshots diarios compilados por el job `CompileDailyScheduleSnapshotsJob`. Permite auditar el estado de planificación de cualquier día pasado.
- **Roles con acceso:** coordinator, chief, wfm, director, admin

---

## Resumen consolidado — Acceso al menú por rol

| Ítem de menú          | operator | supervisor | coordinator | chief |  wfm  | director | admin |
| --------------------- | :------: | :--------: | :---------: | :---: | :---: | :------: | :---: |
| Mi Horario            |    ✅     |     ✅      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |
| Mis Métricas          |    ✅     |     ✅      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |
| Turnos                |    ✅     |     ✅      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |
| Planificación Semanal |    ✅     |     ✅      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |
| Excepciones           |    —     |     —      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |
| Ausencias             |    —     |     —      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |
| Permutas              |    —     |     —      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |
| Asistencia            |    ✅     |     —      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |
| Incidencias           |    —     |     —      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |
| Cobertura             |    —     |     —      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |
| Reportes              |    —     |     —      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |
| Capturas              |    —     |     —      |      ✅      |   ✅   |   ✅   |    ✅     |   ✅   |

> **Nota:** Que un rol vea el menú no implica que tenga acceso a todas las acciones dentro.
> La Policy del recurso aplica control granular sobre cada operación (crear, editar, aprobar, eliminar).

---

## Rutas adicionales (sin entrada de menú)

Estas rutas existen en el módulo pero no tienen entrada en el sidebar. Son accesibles navegando directamente desde la planificación semanal:

| Ruta                                                 | Nombre                            | Componente                  | Acceso                       |
| ---------------------------------------------------- | --------------------------------- | --------------------------- | ---------------------------- |
| `/schedules/weekly-planning/{week}/assignments`      | `schedules.planning.assignments`  | `WeeklyPlanningGrid`        | `WeeklySchedulePolicy::view` |
| `/schedules/weekly-planning/{week}/team/{team}`      | `schedules.planning.team-detail`  | `TeamWeeklyPlanningDetail`  | `WeeklySchedulePolicy::view` |
| `/schedules/weekly-planning/{week}/agent/{employee}` | `schedules.planning.agent-detail` | `AgentWeeklyScheduleEditor` | `WeeklySchedulePolicy::view` |
| `/schedules/effective`                               | `schedules.effective`             | `ViewEffectiveSchedule`     | `auth`                       |
