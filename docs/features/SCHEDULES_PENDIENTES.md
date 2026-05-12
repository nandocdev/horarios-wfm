## Gaps: Documentación vs. Implementación actual

### ✅ Lo que existe y funciona (scope WFM/operaciones)

Todos los flujos de gestión **para el rol WFM** tienen componente Livewire + vista Blade + Action + Policy:

| Funcionalidad                                           | Estado                                         |
| ------------------------------------------------------- | ---------------------------------------------- |
| CRUD de turnos base (`ManageSchedules`)                 | ✅ Completo                                     |
| Planificación semanal + grid + editor por agente        | ✅ Completo                                     |
| Excepciones/Overrides (`ManageOverrides`)               | ⚠️ Bug conocido: `stdClass::delete()`           |
| Cola de aprobación de ausencias (`ManageLeaveRequests`) | ✅ Completo (solo rol wfm)                      |
| Cola de aprobación de permutas (`ManageShiftSwaps`)     | ✅ Completo (solo rol wfm)                      |
| Asistencia (`ViewAttendance`)                           | ⚠️ Solo listado global, sin filtros avanzados   |
| Incidencias (`ManageIncidents`)                         | ✅ Básico                                       |
| Cobertura (`ViewCoverage`)                              | ✅ Básico (usa agregados pre-calculados)        |
| Reportes (`ViewAnalytics`)                              | ✅ Soporta exportación CSV filtrada (snapshots) |
| Capturas (`ManageSnapshots`)                            | ✅ Completo                                     |
| Mi Horario (`MySchedule`)                               | ✅ Completo                                     |
| Mis Métricas (`MyMetrics`)                              | ✅ Completo (integra ContactCenter)             |

---

### ❌ Lo documentado que NO está construido

#### 1. Flujo de auto-servicio del Operador (UC-OP-05 a UC-OP-09)
El permiso `requests.create` existe para el rol `operator` en el seeder pero **no hay ningún componente UI que lo use**. Los UCs documentados sin implementación:

| Caso de uso | Descripción                                | Falta                                                                                                                                           |
| ----------- | ------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| UC-OP-06    | Solicitar permiso parcial (rango horario)  | ✅ Implementado: soporta `type='partial'` + validación de solapamientos                                                                          |
| UC-OP-05    | Solicitar permiso total (día completo)     | ✅ Implementado: `CreateLeaveRequest` Livewire + `CreateLeaveRequestAction` + `CreateLeaveRequestDTO`                                            |
| UC-OP-07    | Consultar estado de mis solicitudes        | ✅ Implementado: vista personal del operador con filtros por estado                                                                              |
| UC-OP-08    | Iniciar solicitud de cambio de turno       | ✅ Implementado: `CreateShiftSwapRequest` Livewire + `CreateShiftSwapAction` + `CreateShiftSwapDTO` — el empleado selecciona contraparte y fecha |
| UC-OP-09    | Responder (aceptar/rechazar) swap recibido | Notificación + acción de respuesta desde el punto de vista del `employee_id_to`                                                                 |

**Impacto:** El permiso `requests.create` del `operator` es actualmente un permiso huérfano — no tiene ninguna surface en la UI.

---

#### 2. Planificación Intradía — cero implementación (UC-INP-*)
El roadmap lo marca como Sprint 5 "En Progreso" pero no existe ningún artefacto:

- ❌ No hay modelo `IntradayActivity` ni `IntradayActivityAssignment`
- ❌ No hay `AssignIntradayActivityAction`
- ❌ No hay componente "Mi Día" (timeline del agente)
- ❌ No hay entrada en el menú

Antes de iniciar el desarrollo, valida modelos que coincidan con lo requerido en esta tarea, y si es necesario, ajusta el diseño de la base de datos para soportar las funcionalidades intradía (ej. asignaciones múltiples por día, tipos de actividad, etc.). Sino coincide, implementar el modelo y migración faltante para `IntradayActivity` y `IntradayActivityAssignment` antes de avanzar con la lógica de asignación o la UI.

Nota rápida: se añadió una implementación mínima (modelos, migración, Action transaccional, Livewire `MyDay` placeholder y tests) en la rama `feature/UC-INP-01-intraday-planning` — pendiente revisión/PR. UI aún usa inputs HTML (TODO: refactor a FluxUI). Verificar permisos (`intraday.assign`) y ajustes de menú.

---

#### 3. BreakTemplate — documentado en DDL, no implementado
schedule.md especifica la tabla `break_templates` con sus constraints y FK a `teams`. El modelo `WeeklyScheduleAssignment` referencia `break_template_id` pero:

- ❌ No existe modelo `BreakTemplate`
- ❌ No hay CRUD (Actions, Livewire, vista)
- ❌ No hay migración para `break_templates`

---

#### 4. Cobertura requerida vs. cobertura real
schedule.md define `coverage_requirements` (demanda objetivo por equipo/hora) y `coverage_snapshots`. Lo que existe (`DailyCoverageAggregate`) solo captura cobertura calculada, pero **no hay tabla ni modelo de requerimientos objetivo** para comparar.

- ❌ Modelo `CoverageRequirement`
- ❌ `SimulateWeeklyCoverageAction` (scoring preventivo antes de publicar — también listado en scheduling-full-flow-implementation.md)
- La UI `ViewCoverage` existe pero no puede mostrar déficit real sin los requerimientos objetivo.

---

#### 5. Eventos y Listeners del dominio
La arquitectura del proyecto (07_Arquitectura.md) exige comunicación entre módulos vía Events. El módulo **no tiene ninguno definido**:

- ❌ `WeeklySchedulePublished`
- ❌ `LeaveRequestCreated`
- ❌ `LeaveRequestApproved` / `LeaveRequestRejected`
- ❌ `ShiftSwapApproved`

Sin eventos, no hay forma de que otros módulos (como Notifications o Audit) reaccionen a cambios del Schedule sin acoplamiento directo.

---

#### 6. Notificaciones (no hay ninguna)
El roadmap Sprint 4 menciona "integración con Jobs/Notifications" como criterio de aceptación. Estado actual:

- ❌ Sin clases `Notification` para aprobación/rechazo de permiso
- ❌ Sin notificación al operador cuando le llega una solicitud de permuta
- ❌ Sin notificación al supervisor cuando un operador solicita permiso

---

#### 7. Artisan Command / Scheduler para Snapshots
`CompileDailyScheduleSnapshotsJob` existe como Job pero:

- ❌ No hay entrada en console.php que lo programe
- ❌ No hay Artisan Command envolvente
- Los snapshots actuales de `ManageSnapshots` solo pueden compilarse manualmente desde la UI

---

#### 8. Bug / deuda técnica (bloquean features):
| Elemento                       | Problema                                                                                            |
| ------------------------------ | --------------------------------------------------------------------------------------------------- |
| `ManageOverrides` L73          | `stdClass::delete()` — error en eliminación de override                                             |
| `ValidateWeeklyScheduleAction` | `$agentsWithNoTime` calculado pero nunca se agrega a `$errors` — la validación no funciona completa |
| `AttendanceIncidentPolicy`     | Existe en `Policies/` pero **no está registrada** en `ModuleServiceProvider`                        |
| LeaveRequest.php               | Modelo fantasma en Scheduling fuera de la arquitectura modular                                      |

---

### Resumen de prioridad

| Prioridad |                          Gap                           |                   Bloqueante                    |
| --------- | :----------------------------------------------------: | :---------------------------------------------: |
| 🔴 Alta    | Flujo operador: crear permiso / permuta (UC-OP-05/08)  | El permiso `requests.create` existe sin surface |
| 🔴 Alta    | Bugs en ManageOverrides y ValidateWeeklyScheduleAction |          Operaciones cotidianas rotas           |
| 🔴 Alta    |         AttendanceIncidentPolicy no registrada         |               Acceso sin control                |
| 🟠 Media   |             Events + Listeners del dominio             |        Rompe la arquitectura al escalar         |
| 🟠 Media   |              Notificaciones de aprobación              |          UX bloqueada para el operador          |
| 🟠 Media   |     Console schedule para CompileDailySnapshotsJob     |           Snapshots siempre manuales            |
| 🟡 Baja    |          Intraday Planning completo (UC-INP)           |                    Sprint 5+                    |
| 🟡 Baja    |                   BreakTemplate CRUD                   |                  Sprint futuro                  |
| 🟡 Baja    |        CoverageRequirements (demanda objetivo)         |                  WFM avanzado                   |
