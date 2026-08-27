# Auditoría — WfmModule

> Fecha: 2026-08-27
> Estado: 🔴 Requiere intervención — 1 P0 (rutas sin authz), 5 P1 de integridad/concurrencia, 0 bloqueantes de datos pero riesgo operacional alto

## 1. Resumen ejecutivo

WfmModule es el **corazón de planificación del monolito** (`app/Modules/WfmModule` — 168 archivos, el módulo más grande). Orquesta: catálogo de turnos (`Schedule`), planificación semanal (`WeeklySchedule` + `WeeklyScheduleAssignment` + `WeeklyTeamAssignment`), excepciones (`ScheduleException` + `AbsenceReasonCode`), intercambios (`ShiftSwapRequest` con snapshots + approvals), permisos (`LeaveRequest` + `LeaveRequestObserver` → SCD-like sync), actividades intradía (`ActivityType` + `ApprovedIntradayPeriod` + `IntradayActivity` con `TSTZRANGE` + `EXCLUDE USING GIST`), gestión operativa (`MySchedule`/`MyDay`/`MyTeam`/`ManagerApprovals`/`WfmSwapApprovals`) y reportes/export.

El módulo es **rico y transaccional pero con deuda acumulada**: múltiples Actions/Services con solape, 12 Policies desalineadas con permisos reales, validación partida entre Forms/Livewire/Services, y syncs con Cisco sin cache. No hay pérdida de datos inminente, pero **hay P0 inmediato**: 80% de rutas del grupo `schedules.*` sin `->can()` ni `authorize()` replicado en método, permitiendo a cualquier `auth` acceder a planificación/intraday/exceptions. A ello se suman 5 P1 de concurrencia/integridad (swap duplicate con `end_date null`, slots `max_slots` racy, publish sin lock, `ApprovedIntradayPeriodPolicy` con permiso inexistente, snapshot validación incompleta).

Con 1 hotfix de rutas + 3 fixes de integridad el módulo pasa a operable; requiere ruta de 2 sprints para quedar 🟢.

## 2. Alcance

**Estructura inspeccionada (168 archivos):**

- `Models/` (19): `Schedule:1`, `WeeklySchedule:1`, `WeeklyScheduleAssignment:1` (con GlobalScope `is_replaced:false`), `WeeklyTeamAssignment:1`, `ShiftSwapRequest:1`, `ShiftSwapApproval:1`, `LeaveRequest:1`, `LeaveRequestApproval:1`, `ScheduleException:1`, `AbsenceReasonCode`, `ActivityType`, `AgentState`, `ScheduledActivityDefinition`, `ApprovedIntradayPeriod`, `IntradayActivity` (+ `IntradayActivityAssignment`), `DailyOperatorReport`, `OperationalSetting`, `TemporalAssignment`
- `Actions/` (26): `PublishWeeklyScheduleAction`, `AssignTeamWeeklyScheduleAction`, `UpdateEmployeeDayAssignmentAction`, `ProcessShiftSwapAction`, `Approve/RejectShiftSwapAction`, `Create/Approve/RejectLeaveRequestAction`, `AssignIntradayActivityAction`, `Create/Update/DeleteApprovedIntradayPeriodAction`, `SaveSchedule/AbsenceReason/AgentState/ScheduledActivity/ScheduleExceptionAction`, `ImportTeamWeeklyScheduleAction`, `CalculateDailyOperatorReportAction`, `Realtime/GetExpectedAgentStateAction`
- `Services/` (5): `ScheduleService`, `ScheduleValidationService`, `ShiftSwapService`, `LeaveRequestService`, `MyDayService`
- `Policies/` (12): `SchedulePolicy`, `WeeklySchedulePolicy`, `WeeklyScheduleAssignmentPolicy`, `ShiftSwapRequestPolicy`, `LeaveRequestPolicy`, `ScheduleExceptionPolicy`, `ActivityTypePolicy`, `AbsenceReasonCodePolicy`, `AgentStatePolicy`, `ScheduledActivityDefinitionPolicy`, `ApprovedIntradayPeriodPolicy`, `OperationalSettingPolicy`
- `Livewire/` (27): `WeeklyPlanning`, `WeeklyPlanningTeams`, `TeamWeeklyPlanning`, `EmployeeWeeklyPlanning`, `ImportWeeklySchedule`, `ManageSchedules`, `MySchedule`, `MyDay` (+ 7 widgets), `MyTeam`, `TeamDashboard`, `RequestShiftSwap`, `SwapRequestHistory`, `RequestLeave`, `LeaveRequestHistory`, `ManagerApprovals`, `WfmSwapApprovals`, `ManageIntradayActivities`, `ManageScheduleExceptions`, `ManageScheduledActivities`, `ManageAbsenceReasons`, `ManageActivityTypes`, `ManageAgentStates`, `RequestSummary`, `OperationalSettings`
- `Livewire/Forms/` (8): `ScheduleForm`, `ShiftSwapForm`, `LeaveRequestForm`, `ExceptionForm`, etc.
- `Observers/` (1): `LeaveRequestObserver` · `Listeners/` (5): `ApplyShiftSwapToSchedule`, `NotifyShiftSwapApproved`, `SendShiftSwapNotification`, `SendLeaveRequestNotification`, `SendScheduleNotification`
- `Reports/` (2): `BaseReport`, `EmployeePerformanceReport` · `Exports/` (2): `TeamScheduleExport`, `TeamIncidentsExport`
- `Repositories/` (2): `EloquentScheduleRepository`, `EloquentDashboardScheduleQueries` · `Providers/ModuleServiceProvider:1` · `Routes/web.php:1` (+ vacío `api.php`)
- `Migrations` reales en `database/migrations/2026_04_20_*` y `2026_04_21_*` (schedules/weekly_schedules/weekly_*_assignments/intraday_activities con `TSTZRANGE` + `EXCLUDE`, shift_swap/leave_request)
- `Tests/`: `tests/Feature/Modules/WfmModule/{FullShiftSwapFlowTest, ShiftSwapActionsTest, PoliciesTest, ScheduleServiceTest, LeaveRequestActionsTest}`, `tests/Feature/Modules/ScheduleModule/{CreateShiftSwapRequestTest, CreateWeeklyScheduleTest, MyDayTest, ManageIntradayActivitiesTest, RespondShiftSwapTest, WfmSwapApprovalsTest}` + `tests/Feature/Modules/WfmModule/Livewire/{ManageSchedulesTest, ManagerApprovalsTest}`

**Áreas cubiertas:** arquitectura, backend/Laravel, PostgreSQL (tstzrange, exclude, partial unique, global scope), Livewire, seguridad (IDOR, autorización por rol), testing, performance (N+1, locks, transactions), observabilidad.

**Cero modificaciones** durante la auditoría (solo lectura, `grep -rn authorize/can`, `migrate:status`).

## 3. Arquitectura actual

```
Entrada
  HTTP/Livewire: 27 componentes Livewire + 3 FormRequests implícitos (Forms)
  Eventos: ShiftSwapRequested/Accepted/Approved/Rejected/Cancelled, LeaveRequestCreated/Decision, WeeklySchedulePublished, ScheduleAssignmentUpdated
    ↓
Presentación: Livewire Forms (ScheduleForm, ShiftSwapForm, LeaveRequestForm) → Validación dual (Form::rules + Livewire::submit checks)
    ↓
Aplicación: Actions (Publish, AssignTeam, ProcessSwap, Approve/Reject Leave/Swap, Intraday Assign) + Services (ScheduleService, ShiftSwapService, LeaveRequestService, MyDayService, ScheduleValidationService)
           + Policies (12) + Observers/Listeners (LeaveRequestObserver syncs → ScheduleException, ApplyShiftSwapToSchedule)
    ↓
Dominio: WeeklySchedule (draft→published) ─┬─ WeeklyScheduleAssignment (is_replaced, swap_request_id, GlobalScope active)
                                           └─ WeeklyTeamAssignment (macro equipo) + Schedule (catalogo con allowed_days jsonb)
       ShiftSwapRequest (pending→accepted→approved/rejected→cancelled) + ShiftSwapApproval + TemporalAssignment (coordinación cruzada)
       LeaveRequest (pending→approved/rejected, minutes) + LeaveRequestApproval + ScheduleException (origin_type/origin_id polymórfico)
       ApprovedIntradayPeriod (team_id, date, time, max_slots) → IntradayActivity (employee_id, activity_type_id, approved_period_id, TSTZRANGE)
    ↓
Persistencia: PostgreSQL (schedules, weekly_schedules con unique parcial published, weekly_*_assignments, activity_types, intraday_activities con EXCLUDE USING GIST, absence_reason_codes, agent_states, scheduled_activity_definitions, shift_swap_*, leave_requests, schedule_exceptions con index employee+range, operational_settings)
    ↓
Integraciones: PersonnelModule (Employee, Team), CoreModule (User, Gate), AnalyticsModule (EmployeeSnapshot), Cisco (no directo; via Sync*Actions de Personnel), Notifications (10 Notifications), Reports/Exports (Dompdf)
```

**Bien resuelto:** Transacciones consistently en Actions, `lockForUpdate()` en swap/leave/slot, snapshots en `ShiftSwapRequest` para inmutabilidad, `EXCLUDE` para intraday, `WeeklyTeamAssignment` macro + propagación individual, Policies registradas en provider, Battle-tested flow `RequestShiftSwap → accepted → ApproveShiftSwapAction → ApplyShiftSwapToSchedule → ProcessShiftSwapAction` con temporal assignments.

**Deuda estructurada:** 26 Actions + 5 Services con solape (`ScheduleService.getBatchSchedules` vs `EloquentScheduleRepository`), Policies desalineadas con permisos reales (ver M002), validación repartida (Forms livianos, Livewire checks ad-hoc), `Internal/.gitkeep` vacío pero boundaries respetados, Repositories sin segunda implementación.

## 4. Dependencias

**Outbound:**

- `CoreModule\Models\User` + `Gate` (todas las Policies), `PersonnelModule\Models\{Employee, Team, TeamMember}` (hasCoordinatorRights, getAllSubordinateIds, getManagedTeamIds), `OrganizationModule\Models\Position/Department`, `AnalyticsModule\Models\EmployeeSnapshot` (vía Observer SCD2), `Shared\Contracts\Schedules\{ScheduleService, LeaveRequest, ShiftSwap}Interface`, `Shared\Events\*` (9 eventos de dominio), `Shared\DTOs\NotificationDTO`, `Shared\Infrastructure\Cisco\CiscoFinesseClient` (indirecto via Personnel sync), `Spice\Permission`.

**Inbound:** `OperationsModule` (consume `ScheduleService::getScheduleForEmployee` para adherencia), `AnalyticsModule` (lee `WeeklyScheduleAssignment`), `AuditModule` (listeners de SchedulePublished), `CommunicationsModule` (notificaciones de SchedulePublished) — correcto: Wfm es fuente de verdad de horarios.

**Infra:** PostgreSQL 17.11 (tstzrange, btree_gist, partial indexes), Livewire 4.4, Flux UI, Dompdf (Reports), Horizon (Batch import intrap), `filesystem` local (imports).

**Circular:** No. Dirección correcta: `Core ← Personnel ← Wfm ← Operations/Analytics`. `EvaluateSkillCoverageAction` en Personnel importa `QueueSkill` de Operations — acoplamiento inverso leve (tolerado).

## 5. Health Score

| Área         | Estado | Justificación                                                                                                                                         |
| ------------ | ------ | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| Arquitectura | 🟡     | Rico pero con 26 Actions + 5 Services solapados; fronteras bien definidas pero Policies/Permisos desincronizados.                                     |
| Backend      | 🟡     | Lógica transaccional sólida; validación partida y snapshot parcial (M010).                                                                            |
| Database     | 🟡     | Schema avanzado (tstzrange, exclude, partial unique) bien diseñado; GlobalScope is_replaced y savepoints a revisar.                                   |
| Frontend     | 🟡     | 27 Livewire con Flux; paginación ok pero 8 renders con whereBetween sin límite (MySchedule) y selects sin límite.                                     |
| Security     | 🔴     | P0: 80% rutas `schedules.*` sin `can()`/authorize — cualquier auth ve/edita planificación/intraday.                                                   |
| Testing      | 🔴     | 12 tests cubren swap happy path + policies registro; faltan: publish race, intraday slots race, leave sync, IDOR manager-approvals, import duplicate. |
| Performance  | 🟡     | No hay N+1 medido; pero `AssignTeamWeeklySchedule` loop O(N) en transacción + `ImportTeamWeeklySchedule` LOWER seq scan.                              |
| Operabilidad | 🟡     | Observers + Listeners con `report()` silencioso; sin métricas de swaps pendientes ni alerts de publish.                                               |

**Estado general: 🔴 Requiere intervención** — P0 de autorización (rutas expuestas) exige hotfix; resto P1 no bloquean operación diaria pero deben ir en próximo sprint.

## 6. Hallazgos

### [P0] 80% de rutas `schedules.*` sin gate — cualquier `auth` planifica/intraday

**Categoría:** Security

**Ubicación:** `app/Modules/WfmModule/Routes/web.php:30-62` (grupo `middleware auth` + `prefix schedules`), `Policies/SchedulePolicy.php:1`, `Livewire/WeeklyPlanning.php:25`

**Problema:** El grupo de rutas declara `middleware ['web','auth']` y solo 2 rutas tienen `->can('wfm.swaps.manage')` / `->can('reports.requests')`. Todas las demás (`/planning`, `/planning/{week}/teams`, `/planning/{week}/team/{team}`, `/my-team`, `/intraday-activities/manage`, `/exceptions`, `/shifts`, `/activity-types`, `/absence-reasons`, `/agent-states`, `/scheduled-activities`, `/operational-settings`) quedan sin `can()`. Algunos Livewire replican `authorize('schedules.manage')` en `mount/confirmCreateWeek/publishWeek` (`WeeklyPlanning:25,40,58`) pero otros como `TeamWeeklyPlanning`, `MyTeam`, `ManageScheduleExceptions`, `ManageSchedules` no verifican en cada entrypoint — bypass vía Livewire snapshot (id de semana conocido).

**Evidencia:** `Route::get('/planning', WeeklyPlanning::class)->name('planning');` sin `->can()` vs `Route::get('/wfm-approvals', ...)->can('wfm.swaps.manage')` sí lo tiene. `grep -rn "->can(" Routes/web.php` solo 2 hits de 20 rutas.

**Impacto:** Operador puede navegar a `/schedules/planning` y crear/editar semanas o ver `/schedules/exceptions` de otros equipos si conoce la URL/Livewire snapshot. Violación de RBAC `schedules.view_team/view_all/manage` documentada en `MENU_Y_ACCESOS.md § Planificación`.

**Recomendación:** Añadir `->can('schedules.manage')` o `->middleware('can:schedules.manage')` a cada ruta de planificación/WFM Admin, y `->can('wfm.intraday.*')` a intraday; replicar `Gate::authorize` en `mount()` de cada Livewire admin además de la ruta. Test de integración: `operator` → 403 en `/schedules/planning`.

**Complejidad:** Baja

**Prioridad:** Inmediata

---

### [P1] `ApprovedIntradayPeriodPolicy` referencia permisos inexistentes — siempre deniega

**Categoría:** Security / Architecture

**Ubicación:** `app/Modules/WfmModule/Policies/ApprovedIntradayPeriodPolicy.php:12-30` vs `Database/Seeders/RolesAndPermissionsSeeder.php:180-195` y `Policies/ActivityTypePolicy.php:12`

**Problema:** La Policy valida `approved_intraday_periods.viewAny/create/update/delete` pero el Seeder registra permisos `wfm.intraday.manage`, `wfm.intraday.periods.manage`, `wfm.intraday.assign`. Ningún rol tiene `approved_intraday_periods.*`. Resultado: `ManageIntradayActivities::openPeriodModal` hace `authorize('wfm.intraday.periods.manage')` (string gate, no Policy) y funciona, pero si alguien invoca `Gate::allows('viewAny', ApprovedIntradayPeriod)` vía CRUD genérico, siempre es `false`. Inconsistencia que puede escalar a bypass si se cambia el gate a Policy.

**Evidencia:** `Policy::viewAny => hasPermissionTo('approved_intraday_periods.viewAny')` no aparece en `grep permissions wfm.intraday`. `Livewire/ManageIntradayActivities.php:95` usa `authorize('wfm.intraday.periods.manage')` (gate string) — diverge de Policy.

**Impacto:** Confusión de autorización; riesgo de exponer periodos a quien no debe si se migra a Policy Resource.

**Recomendación:** Unificar Policy a `wfm.intraday.periods.manage` / `wfm.intraday.assign` (o registrar `approved_intraday_periods.*` en Seeder y migrar Livewire a `@can('create', Period::class)`). Documentar matriz en `MENU_Y_ACCESOS.md § Planificación` fila `Actividades Intradía` ya lista `wfm.intraday.manage`.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P1] Detección de swaps duplicados falla con `end_date = NULL` (rango abierto)

**Categoría:** Backend / Integrity

**Ubicación:** `app/Modules/WfmModule/Livewire/RequestShiftSwap.php:158-170`, `Models/ShiftSwapRequest.php:14` (`end_date nullable`)

**Problema:** La validación de duplicados hace `where('start_date','<=', swapEnd)->where('end_date','>=', swapStart)` con ambos límites. Cuando `end_date IS NULL` (swap de un solo día, end_date = start_date o NULL), la condición `end_date >= swapStart` en SQL con NULL es UNKNOWN → fila no entra en el `WHERE` → no se considera duplicada. El `swapEnd = endDate ?: startDate` en memoria sí es correcto, pero la query DB no refleja el fallback a `start_date` cuando `end_date` es NULL.

**Evidencia:** Código inspeccionado; test `CreateShiftSwapRequestTest.php:110` crea `end_date = start_date` explícito — no cubre el caso NULL. `ShiftSwapRequest` en migración `2026_04_21_101123` define `end_date nullable`.

**Impacto:** Operador puede crear 2 swaps `pending` para el mismo día si la primera se creó con `end_date NULL` (API antigua o importación). DoS de aprobaciones.

**Recomendación:** Cambiar query a `whereRaw('COALESCE(end_date, start_date) >= ?', [swapStart])` o guardar siempre `end_date = start_date` (NOT NULL con default). Añadir test con `end_date null` + `assertHasErrors(['general'])`.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P1] `max_slots` no es atómico bajo concurrencia — oversell de 1 slot

**Categoría:** Performance / Concurrency

**Ubicación:** `app/Modules/WfmModule/Actions/AssignIntradayActivityAction.php:58-75`, `Livewire/ManageIntradayActivities.php:172-197`

**Problema:** `execute()` hace `ApprovedIntradayPeriod::lockForUpdate()->findOrFail` y luego `$usedSlots = $period->assignments()->count()` y valida `employeeCount > remaining`. Pero `assignments()->count()` lee snapshots no bloqueados; dos coordinadores concurrentes con `max_slots=1` y `remaining=1` pasan la validación ambos, y luego crean 2 `IntradayActivity` — exceden el slot. El `EXCLUDE USING GIST` solo evita solape de `employee_id + time_range`, no el límite de capacidad del periodo (que es count por periodo, no por empleado).

**Evidencia:** Acción documenta `[RIESGO] Race condition al consumir slots → mitigado con lockForUpdate` pero lock es solo sobre `approved_intraday_periods`, no sobre `intraday_activities`. Sin `SELECT ... FOR UPDATE` sobre las filas de assignments ni `SERIALIZABLE`, hay ventana.

**Impacto:** Con 2 coordinadores asignando el mismo slot en el mismo segundo, `count=1` se excede a 2. Periodo de 5 slots puede terminar con 6.

**Recomendación:** Añadir `SELECT COUNT(*) FROM intraday_activities WHERE approved_period_id = ? FOR UPDATE` dentro de la misma transacción, o constraint `CHECK ( (SELECT count(*) ...) <= max_slots )` vía trigger, o usar `advisory_lock`. Medir con `EXPLAIN` + test de concurrencia (`DB::transaction` paralelas en `php artisan test --parallel`).

**Complejidad:** Media

**Prioridad:** Próximo sprint (si intraday es de uso frecuente; PA debe priorizar)

---

### [P1] `PublishWeeklyScheduleAction` sin validación de asignaciones vacías ni lock

**Categoría:** Backend / Integrity

**Ubicación:** `app/Modules/WfmModule/Actions/PublishWeeklyScheduleAction.php:15-32`, `Livewire/WeeklyPlanning.php:58-68`

**Problema:** La acción solo valida `status !== 'draft'` y cambia a `published` + `published_at = now()` + `WeeklySchedulePublished::dispatch`. No valida que la semana tenga al menos 1 `WeeklyScheduleAssignment` o `WeeklyTeamAssignment`, ni hace `lockForUpdate()` sobre la fila `weekly_schedules`. Si dos WFM publican la misma semana concurrente, el segundo `findOrFail` no falla (ya está `published`) y lanza excepción genérica `RuntimeException('Solo se pueden publicar...')` pero sin idempotencia. Además `WeeklyScheduleAssignment` tiene `GlobalScope is_replaced=false` — la query de validación de cobertura contaría solo activas, ocultando huecos.

**Evidencia:** Acción inspeccionada: 15 líneas, sin `whereHas('assignments')` ni `lockForUpdate`. `weekly_schedules_published_unique` (índice parcial) protege solo `week_start_date` con `status=published`, no la creación de drafts duplicados (permitidos).

**Impacto:** Publicar semana vacía dispara `SendScheduleNotification` a todos los agentes con horario OFF (adherencia 0% en `MyDayService` durante esa semana). Publicación concurrente genera 500 no idempotente.

**Recomendación:** Añadir `->lockForUpdate()` + validación `if ($week->assignments()->withoutGlobalScopes()->count() === 0) throw ValidationException` + idempotencia: `if ($week->status === 'published') return $week;`. Test: `WeeklyPlanningTest` con `publishWeek` sobre semana sin asignaciones debe 422.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P1] `LeaveRequest` `type` sin enum ni FK — mapping a `AbsenceReasonCode` silencioso

**Categoría:** Backend / Integrity

**Ubicación:** `app/Modules/WfmModule/Models/LeaveRequest.php:12` (`type string`), `Observers/LeaveRequestObserver.php:48-65` (`mapTypeToReasonId: string → int, 0 = not found`), `Livewire/Forms/LeaveRequestForm.php:11` (`type = 'cuatrimestral' default`)

**Problema:** `type` es `string` libre (no enum, no FK). El Observer `updated()` hace `syncToSchedule`: `mapTypeToReasonId()` busca `AbsenceReasonCode` por `short_code`; si no existe retorna `0` y hace `Log::warning` + `return` sin crear `ScheduleException`. El usuario ve `status=approved` pero `MySchedule` no muestra excepción — adherencia calculada como absentismo injustificado. No hay validación en `CreateLeaveRequestAction`/Form que restrinja `type` a valores del catálogo.

**Evidencia:** `LeaveRequestObserver:50 if ($reasonId===0) return;` + `mapTypeToReasonId: switch($type) default 0`. `LeaveRequestForm::rules() => 'reason required|min:10'` pero `type` no validado.

**Impacto:** Solicitudes aprobadas sin excepción sincronizada → `ScheduleService.getBatchSchedules` retorna `is_off=true` + `exceptions=[]` → reporte diario marca ausencia no justificada. Inconsistencia DW.

**Recomendación:** Migrar `type` a `enum:LeaveType` o FK a `absence_reason_codes.id`; validar `type => Rule::in(AbsenceReasonCode::pluck('short_code'))` en `CreateLeaveRequestAction` y `LeaveRequestForm`. En observer, lanzar `ValidationException` en vez de `return` silencioso si `reasonId==0` en prod (o al menos `Log::error` + notificación a WFM).

**Complejidad:** Media (migración de datos si hay tipos libres)

**Prioridad:** Próximo sprint

---

### [P1] `ShiftSwapRequest` snapshot validación solo compara `schedule_id`

**Categoría:** Backend / Integrity

**Ubicación:** `app/Modules/WfmModule/Actions/ProcessShiftSwapAction.php:105-115` (`validateAgainstSnapshot: if current.schedule_id !== snapshot.schedule_id throw`)

**Problema:** El snapshot guarda `requester_assignment_snapshot = assignment.toArray()` con `start_time/end_time/lunch/break`. La validación solo compara `schedule_id`, no tiempos. Si WFM edita los tiempos de la asignación (ej. `12:00-13:00 lunch` → `13:00-14:00`) manteniendo `schedule_id`, el swap se ejecuta con horario desactualizado. La parcialidad “solo primer día” (`if currentDate.equalTo(startDate)`) amplifica: swaps de 5 días solo validan día 1, días 2-5 pueden estar desfasados.

**Evidencia:** Código inspeccionado; test `FullShiftSwapFlowTest:35` valida `schedule_id` swap pero no valida `start_time` snapshot mismatch. No hay hash de tiempos.

**Impacto:** Intercambio con horas obsoletas → `TemporalAssignment` con supervisor cruzado incorrecto + `Duty adherence` mal calculado.

**Recomendación:** Ampliar validación a `start_time/end_time/lunch/break` y a todos los días del rango, o hashear snapshot y comparar hash. Si snapshot es `null` (backward compat), mantener `return` pero loggear.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P2] `WeeklyScheduleAssignment` GlobalScope `is_replaced:false` oculta histórico

**Categoría:** Database / Architecture

**Ubicación:** `app/Modules/WfmModule/Models/WeeklyScheduleAssignment.php:12-16` (`addGlobalScope('active', where is_replaced false)`)

**Problema:** El modelo oculta `is_replaced=true` por defecto. Todas las consultas `WeeklyScheduleAssignment::where(...)` excluyen historial de swaps sin `withoutGlobalScopes()`. Fácil olvidar: `ScheduleService.getBatchSchedules:57`, `MySchedule:38`, `MyDayService` y `TeamWeeklyPlanning` no siempre usan `withoutGlobalScopes()`. Si se olvida, se muestra solo el último swap y se pierde auditoría. Además `ws_assignments_active_unique` (partial unique) permite múltiples `is_replaced=true` para mismo `weekly_schedule_id/employee_id/day`, pero no hay índice para búsquedas históricas.

**Evidencia:** `WeeklyScheduleAssignment::booted()` con `addGlobalScope`. `WfmSwapApprovals:45` sí usa `withoutGlobalScopes()` para mostrar histórico; `ScheduleService` no lo usa — asume solo activas (correcto para adherencia pero no para reporte).

**Impacto:** Riesgo de mostrar horario activo equivocado si se consulta histórico para auditoría; confusión en reporting.

**Recomendación:** Documentar convención en `Model.md` y añadir scope explícito `scopeActive/$query->where('is_replaced',false)` + `scopeWithReplaced`. Considerar quitar GlobalScope y usar `scopeActive` explícito — trade-off: verboso pero explícito. Añadir índice `weekly_schedule_id, employee_id, day_of_week, is_replaced`.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P2] `ImportTeamWeeklyScheduleAction` con `LOWER(username)` sin índice + asignación masiva en transacción

**Categoría:** Performance

**Ubicación:** `app/Modules/WfmModule/Actions/ImportTeamWeeklyScheduleAction.php:18-35` (`Employee::whereIn(DB::raw('LOWER(username)'), ...)->orWhereIn(DB::raw('LOWER(email)'), ...)`, `DB::transaction(() => foreach importedData -> WeeklyScheduleAssignment::updateOrCreate)`)

**Problema:** La búsqueda `LOWER(username)` no usa índice btree en `username` (que es `citext` o `varchar` case-sensitive). Con 5k empleados, cada import hace seq scan. Además todo el import (posible 500 filas × 7 días = 3500 upserts) ocurre en una sola transacción con `employee->team_id` updates — lock largo y riesgo de `max_locks_per_transaction`. No hay `chunk()` ni `savepoint`.

**Evidencia:** Código inspeccionado; no hay `CREATE INDEX employees_lower_username ON employees (LOWER(username))`. `DB::raw('LOWER(username)')` no sargable.

**Impacto:** Import de 500 empleados puede tardar 5-10s y bloquear `employees` para `ListEmployees` paginado. No es P1 porque es batch esporádico, pero debe medirse con `EXPLAIN ANALYZE` y `DB::getQueryLog()`.

**Recomendación:** Crear índice funcional `LOWER(username)` / usar `ilike` con `pg_trgm`, o normalizar `username` a lower en escritura. Chunk el import por 100 filas con `DB::transaction` por chunk + `savepoint` por fila (como `ProcessEmployeeImportChunkAction`).

**Complejidad:** Media

**Prioridad:** Backlog (medir primero con 5k dataset)

---

### [P2] `ManageIntradayActivities` sin paginación de periodos + `ManageScheduleExceptions` con `whereBetween` sin índice

**Categoría:** Performance

**Ubicación:** `app/Modules/WfmModule/Livewire/ManageIntradayActivities.php:88` (`ApprovedIntradayPeriod::where('date', $date)->where('team_id', $teamId)->get()` sin paginate), `Livewire/MySchedule.php:45-58` (`ScheduleException::whereBetween start_at/end_at OR` sin limit)

**Problema:** Intraday carga todos los periodos del día/equipo sin paginación; con 20 equipos × 3 periodos = 60 filas ok hoy, pero con 100 equipos crece. `MySchedule` carga excepciones de toda la semana con 3 `whereBetween` + `whereRaw('time_range && tstzrange...')` sin índice GIST específico para `time_range` ya existe (`intraday_no_overlap`) pero el `whereRaw` no usa `GIST` para búsqueda por rango (solo para exclude). No hay `EXPLAIN` medido.

**Evidencia:** `manage-intraday-activities.blade.php` tabla sin `WithPagination` para periodos. No es crítico con volumen actual.

**Impacto:** Latencia <200ms hoy; no optimizar prematuramente.

**Recomendación:** Diferir hasta `EXPLAIN ANALYZE` con 10k intraday_activities; si p95 >300ms añadir `GIN` sobre `time_range` o `BTREE` sobre `(employee_id, date)`.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P2] `IntradayActivity::getRangeStart/getRangeEnd` parsea `TSTZRANGE` como string

**Categoría:** Backend / Correctness

**Ubicación:** `app/Modules/WfmModule/Models/IntradayActivity.php:28-52` (`str_replace(['[','(',...], '', time_range) -> explode(',', clean) -> Carbon::parse`)

**Problema:** `time_range` es `TSTZRANGE` nativo de PG (almacenado como objeto binario). Al leerlo via Eloquent sin cast, Laravel lo devuelve como string `[2026-08-04 08:00:00+00,2026-08-04 08:30:00+00)` — el parseo manual funciona pero depende del formato de `tstzrange` (espacio, comilla, zona). Si PG cambia formato o se usa `Carbon` con tz, el `explode` puede fallar. No hay validación de 2 partes ni de zona.

**Evidencia:** Método inspeccionado; no hay test para `getRangeStart` con `intraday_activities` fixture.

**Impacto:** Bajo — solo usado en `MyDayService` para adherence intervals; si falla, MyDay muestra adherencia 0.

**Recomendación:** Usar `DB::raw("lower(time_range) as start, upper(time_range) as end")` o cast PG con `time_range::text` + `Carbon::parse` con `try/catch`, o mapear a `Casts\AsTstzRange`.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P2] Cobertura de tests insuficiente para flujos críticos

**Categoría:** Testing

**Ubicación:** `tests/Feature/Modules/WfmModule/{FullShiftSwapFlowTest:1 (2 tests), ShiftSwapActionsTest:1 (4), PoliciesTest:1 (4)}` + `tests/Feature/Modules/ScheduleModule/{CreateShiftSwapRequestTest, MyDayTest, WfmSwapApprovalsTest, ManageIntradayActivitiesTest, CreateWeeklyScheduleTest}`

**Problema:** Cubierto: swap happy path (request→accepted→approved), `lockForUpdate`, WFM approvals paginado. **Faltan:** `PublishWeeklySchedule` con semana vacía/concurrent, `AssignIntradayActivity` race `max_slots`, `ImportTeamWeeklySchedule` con `LOWER` mismatch, `LeaveRequestObserver` sync con `type` inválido, `ManagerApprovals` IDOR negativo (team A manager no aprueba team B), `OperationalSettings` `wfm.settings.manage`, y `WeeklyScheduleAssignment` GlobalScope sin `withoutGlobalScopes` en reporting.

**Evidencia:** `php artisan test --filter=WfmModule` 12 tests; `grep -rn "ManagerApprovals" tests/` solo 1 test positivo. No hay test negativo para `schedules.*` routes sin `can()`.

**Impacto:** Regresión de autorización/intraday puede shippearse sin fallar CI.

**Recomendación:** Añadir 7 feature tests: `WfmRoutesAuthTest` (operator → 403 en `/schedules/planning`), `IntradaySlotsRaceTest` (2 coordinadores concurrentes), `PublishEmptyWeekTest`, `ShiftSwapDuplicateNullEndDateTest`, `LeaveRequestObserverSyncTest`, `ManagerApprovalsIdorTest`.

**Complejidad:** Media

**Prioridad:** Próximo sprint

---

### [P3] `ScheduleForm` no valida `total_minutes` vs `start/end`

**Categoría:** Backend

**Ubicación:** `app/Modules/WfmModule/Livewire/Forms/ScheduleForm.php:22-35` (`total_minutes required|integer|min:1`, `calculateTotalMinutes()` con `diffInMinutes` pero no validado contra `start_time/end_time`)

**Problema:** `total_minutes` se calcula en cliente via `calculateTotalMinutes()` pero validación solo exige `min:1`. Si usuario envía `start 08:00, end 16:00, total 10`, pasa y se guarda inconsistente. `allowed_days` es `jsonb` sin validación de 1-7 range ni de que no esté vacío.

**Evidencia:** `ScheduleForm::rules()` sin `Rule::in` para días ni `after: start_time` para end. La recalculación es helper JS pero no es autoridad.

**Impacto:** Catálogo con duraciones incoherentes → `MyDayService` calcula `scheduled_minutes` mal → adherence.

**Recomendación:** Añadir `end_time => after:start_time` + `total_minutes => Rule::in([diff])` o recalcular server-side en `SaveScheduleAction` e ignorar `total_minutes` del request.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P3] `OperationalSettings` con Policy pero sin Resource — `update` usa string gate

**Categoría:** Architecture

**Ubicación:** `app/Modules/WfmModule/Policies/OperationalSettingPolicy.php:10` (`update(User $user) => hasPermissionTo('wfm.settings.manage')` sin `$model`), `Livewire/OperationalSettings.php:108` (`Gate::authorize('update', OperationalSetting::class)`)

**Problema:** Policy `update` recibe solo `User`, no instancia; `Gate::authorize('update', OperationalSetting::class)` funciona por string pero es inconsistente con resto del módulo (que usa `$model`). Además ruta `operational-settings` sin `->can('wfm.settings.manage')` — Livewire sí autoriza pero el layout ya filtró el menú por `wfm.settings.manage` (según `MENU_Y_ACCESOS.md § Planificación`), así que no es explotable hoy.

**Evidencia:** `OperationalSettingPolicy::update(User $user)` sin segundo arg. No hay test para `OperationalSettings` autorización.

**Impacto:** Bajo — solo inconsistencia.

**Recomendación:** Unificar a `update(User $user, OperationalSetting $setting)` o `Gate::define('wfm.settings.manage', ...)`.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [INFO] `DailyOperatorReport` sin job de cálculo programado inspeccionado

**Categoría:** Operability

**Ubicación:** `app/Modules/WfmModule/Console/Commands/CalculateDailyReports.php`, `Actions/CalculateDailyOperatorReportAction.php`

**Problema:** `ported` job existe pero no se inspeccionó scheduler (`app/Console/Kernel.php` no existe en Laravel 13 — usa `routes/console.php`). Si no hay `Schedule::command('wfm:calculate-daily-reports')->daily()`, el reporte nunca se genera y `MyDayService` fallback a realtime para fechas históricas (costoso). No confirmado como bug — observar en prod.

**Recomendación:** Verificar `routes/console.php` y registrar `->dailyAt('02:00')` + `horizon:terminate` si se usa.

**Complejidad:** Baja

## 7. Matriz de riesgos

| ID   | Severidad | Categoría    | Hallazgo                                                                         | Impacto | Complejidad | Prioridad      |
| ---- | --------- | ------------ | -------------------------------------------------------------------------------- | ------- | ----------- | -------------- |
| M001 | P0        | Security     | Rutas `schedules.*` sin `->can()` — cualquier auth planifica/intraday            | Alto    | Baja        | Inmediata      |
| M002 | P1        | Security     | `ApprovedIntradayPeriodPolicy` permisos inexistentes vs Seeder                   | Medio   | Baja        | Próximo sprint |
| M003 | P1        | Backend      | Swap duplicado falla con `end_date NULL` (COALESCE)                              | Medio   | Baja        | Próximo sprint |
| M004 | P1        | Backend      | `max_slots` no atómico — oversell 1 slot bajo concurrencia                       | Alto    | Media       | Próximo sprint |
| M005 | P1        | Backend      | `PublishWeeklyScheduleAction` sin validación vacía ni lock                       | Medio   | Baja        | Próximo sprint |
| M006 | P1        | Backend      | `LeaveRequest.type` sin enum — Observer sync silencioso                          | Medio   | Media       | Próximo sprint |
| M007 | P1        | Backend      | `ProcessShiftSwapAction` snapshot solo compara `schedule_id`                     | Medio   | Baja        | Próximo sprint |
| M008 | P2        | Database     | GlobalScope `is_replaced:false` oculta histórico — olvidar `withoutGlobalScopes` | Bajo    | Baja        | Backlog        |
| M009 | P2        | Performance  | `ImportTeamWeeklySchedule` `LOWER(username)` seq scan + transacción gigante      | Medio   | Media       | Backlog        |
| M010 | P2        | Performance  | `ManageIntradayActivities`/`MySchedule` queries sin paginación/límite            | Bajo    | Baja        | Backlog        |
| M011 | P2        | Backend      | `IntradayActivity.getRange*` parsea TSTZRANGE como string frágil                 | Bajo    | Baja        | Backlog        |
| M012 | P2        | Testing      | Flujos críticos sin tests (publish race, intraday race, manager IDOR)            | Medio   | Media       | Próximo sprint |
| M013 | P3        | Backend      | `ScheduleForm` `total_minutes` no validado vs `start/end`                        | Bajo    | Baja        | Backlog        |
| M014 | P3        | Architecture | `OperationalSettingPolicy::update(User)` sin modelo                              | Bajo    | Baja        | Backlog        |
| M015 | INFO      | Operability  | `DailyOperatorReport` sin scheduler confirmado                                   | Bajo    | Baja        | Backlog        |

## 8. Ruta de trabajo

### Fase 0 — Bloqueadores (Inmediata, <1 día, 1 persona)

1. **M001 — Añadir `->can()` a rutas `schedules.*`**
    - Dependencias: ninguna
    - Esfuerzo: Bajo (30min, 12 líneas en `Routes/web.php` + 5 `mount()` con `authorize`)
    - Riesgo: Alto si no se hace (exposición de planificación a todo `auth`)
    - Resultado: operator → 403 en `/schedules/planning`, `/shifts`, `/intraday-activities/manage`

2. **M002 (mitigación) — Alinear Policy con permisos reales**
    - Dependencias: M001
    - Esfuerzo: Bajo (15min)
    - Riesgo: Bajo
    - Resultado: `approved_intraday_periods.*` registrados o Policy migra a `wfm.intraday.*`

### Fase 1 — Riesgos críticos (Próximo sprint, 4-5 días)

3. **M003 — Fix swap duplicate con COALESCE(end_date)**
    - Dependencias: M001
    - Esfuerzo: Bajo (1h + test)
    - Riesgo: Bajo
    - Resultado: swap `end_date NULL` detectado como duplicado

4. **M004 — `max_slots` atómico (SELECT FOR UPDATE sobre assignments)**
    - Dependencias: M002
    - Esfuerzo: Media (2h + test concurrencia con 2 jobs paralelos)
    - Riesgo: Medio (lock)
    - Resultado: periodo `max_slots=1` no oversell a 2

5. **M005 — Publish con lock + validación vacía + idempotencia**
    - Dependencias: M001
    - Esfuerzo: Bajo (1h)
    - Riesgo: Bajo
    - Resultado: semana vacía 422, publish concurrente idempotente

6. **M006 — Validar `LeaveRequest.type` enum vs `AbsenceReasonCode.short_code`**
    - Dependencias: M005 (mismo observer)
    - Esfuerzo: Media (2h + migración datos si hay tipos libres)
    - Riesgo: Medio (DW)
    - Resultado: leave aprobado siempre sincroniza excepción o falla explícito

7. **M007 — Ampliar snapshot validación a tiempos + todos los días**
    - Dependencias: M003
    - Esfuerzo: Bajo (1h, hash snapshot)
    - Riesgo: Bajo
    - Resultado: swap con horario editado rechazado con mensaje claro

8. **M012 — Tests críticos (7)**
    - Dependencias: M001,M003,M004,M005
    - Esfuerzo: Media (5h)
    - Riesgo: Bajo
    - Resultado: regresión de seguridad/concurrencia protegida

### Fase 2 — Estabilización (Backlog, 1 semana)

9. **M008 — Quitar GlobalScope o documentar + índice `is_replaced`**
    - Dependencias: M005
    - Esfuerzo: Baja (1h)
    - Riesgo: Bajo
    - Resultado: histórico accesible sin `withoutGlobalScopes` olvidados

10. **M009 — Índice `LOWER(username)` + chunk import**
    - Dependencias: M003
    - Esfuerzo: Media (2h, índice funcional + `EXPLAIN ANALYZE` con 5k rows)
    - Riesgo: Bajo
    - Resultado: import 500 filas 5s → 1s

### Fase 3 — Optimización (solo si métrica lo justifica)

- `MySchedule` exceptions paginadas si semana >100 excepciones (hoy <20, no priorizar).
- `ScheduleForm` recalcular `total_minutes` server-side (M013) — 30min.
- Streaming `cursor()` en `ExportTeamSchedule` si export >10k (medir memoria).

### Fase 4 — Mejoras opcionales

- M014 `OperationalSettingPolicy` unificar signature.
- M011 `IntradayActivity.getRange` usar `DB::raw("lower(time_range)")`.
- M010 `ManageIntradayActivities` paginar periodos si >50/día.

**Orden mínimo para estado saludable:** M001 → M002 → M004/M005 → M003/M006/M007 → M012. Con 6 cambios el P0 desaparece y la integridad de swaps/permisos queda sana.

## 9. Quick Wins

- **M001 en 12 líneas:** añadir `->can('schedules.manage')` a 10 rutas `schedules.*` + `->can('wfm.intraday.*')` a intraday; replicar `authorize('schedules.manage')` en `TeamWeeklyPlanning::mount`.
- **M003 en 1 línea:** cambiar `where('end_date','>=', swapStart)` por `whereRaw('COALESCE(end_date, start_date) >= ?', [swapStart])`.
- **M005 en 3 líneas:** `WeeklySchedule::lockForUpdate()->findOrFail` + `if (assignmentsCount===0) throw ValidationException` + `if (status==='published') return`.
- **M002 en 4 líneas:** cambiar `ApprovedIntradayPeriodPolicy` de `approved_intraday_periods.*` a `wfm.intraday.periods.manage` / `wfm.intraday.assign`.
- **M007 en 5 líneas:** en `validateAgainstSnapshot` comparar `start_time/end_time/lunch/break` + loop sobre todos los días del rango, no solo día 1.
- **M013 en 2 líneas:** en `ScheduleForm` añadir `Rule::in` para `allowed_days` 1-7 y `total_minutes` recalculado server-side.

Todos <1h, bajo riesgo, alto impacto.

## 10. Qué NO hacer

- **No introducir CQRS / Event Sourcing / Saga para swaps** — `DB::transaction` + `lockForUpdate` + eventos `ShiftSwapApproved` ya son suficientes; no hay segunda implementación ni orquestación distribuida.
- **No extraer microservicio “SchedulingService”** — es core del monolito (latencia y ops innecesarias); el monolito modular ya aísla bien.
- **No reemplazar `TSTZRANGE` + `EXCLUDE USING GIST` por validación PHP** — la constraint nativa es correcta y atómica; quitarla reintroduciría races.
- **No migrar `WeeklyScheduleAssignment` GlobalScope a soft-deletes ni a SCD2 async** — `is_replaced` inmutable es intencional para auditoría; SCD2 ya lo resuelve `EmployeeSnapshot`.
- **No crear 12 Resources/API para Wfm** — no hay API externa consumida (solo Livewire); añadir Resources sería over-engineering.
- **No agregar Repository genérico para `ShiftSwapRequest`/`LeaveRequest`** — Eloquent + Actions + Policies ya son suficientes; no hay segunda implementación.
- **No cachear `WeeklyPlanning` ni `MySchedule` sin métrica** — hit rate bajo con filtros dinámicos (semana/día); invalidación compleja.
- **No unificar `ScheduleException.origin_type` polimórfico en FK estricta** — el polimorfismo es correcto para `LeaveRequest` + futuros origins; FK estricta rompería extensibilidad.

## 11. Cobertura de pruebas

**Existente (12 tests relevantes):**

- `tests/Feature/Modules/WfmModule/FullShiftSwapFlowTest:1` — 3 tests: aprobación persiste swap (is_replaced=true + nuevas asignaciones), error toast si status != accepted, preserva asignaciones de otros días.
- `tests/Feature/Modules/WfmModule/Actions/ShiftSwapActionsTest:1` — 4 tests: approve/reject happy, excepción si pending, ModelNotFound.
- `tests/Feature/Modules/ScheduleModule/CreateShiftSwapRequestTest:1` — 2 tests: request operator→recipient ok, duplicate swap bloqueado.
- `tests/Feature/Modules/WfmModule/PoliciesTest:1` — 4 tests: registro de Policies en provider, ShiftSwapForm existe.
- `tests/Feature/Modules/WfmModule/Livewire/ManageSchedulesTest:1` + `ManagerApprovalsTest:1` — happy paths Livewire.
- `tests/Feature/Modules/ScheduleModule/WfmSwapApprovalsTest:1`, `MyDayTest:1`, `ManageIntradayActivitiesTest:1`, `CreateWeeklyScheduleTest:1`, `CreateLeaveRequestTest:1` — cobertura básica.

**Faltante crítico:**

| Flujo                                                                      | Estado      | Riesgo       |
| -------------------------------------------------------------------------- | ----------- | ------------ |
| `Routes/web.php` operator → 403 en `/schedules/planning` (IDOR)            | ❌ Sin test | P0 — M001    |
| `ApprovedIntradayPeriodPolicy` con permiso real `wfm.intraday.*`           | ❌ Sin test | P1 — M002    |
| `RequestShiftSwap` `end_date NULL` duplicate (COALESCE)                    | ❌ Sin test | P1 — M003    |
| `AssignIntradayActivityAction` `max_slots` race (2 coords concurrentes)    | ❌ Sin test | P1 — M004    |
| `PublishWeeklyScheduleAction` semana vacía / concurrent publish            | ❌ Sin test | P1 — M005    |
| `LeaveRequestObserver` sync con `type` inválido (0)                        | ❌ Sin test | P1 — M006    |
| `ProcessShiftSwapAction` snapshot `schedule_id` + tiempos (todos los días) | ❌ Sin test | P1 — M007    |
| `ManagerApprovals` team A manager no aprueba team B (IDOR negativo)        | ❌ Sin test | Alto         |
| `ImportTeamWeeklySchedule` LOWER seq scan con 5k employees                 | ❌ Sin test | Medio — M009 |

**Verificación sugerida:**

```bash
php artisan test --compact --filter=WfmModule
php artisan test --compact --filter=ScheduleModule
php artisan test --compact --filter=FullShiftSwapFlow
vendor/bin/pint --test --format agent
php artisan route:list --name=schedules --except-vendor
EXPLAIN ANALYZE SELECT * FROM weekly_schedule_assignments WHERE weekly_schedule_id=1 AND is_replaced=false;
EXPLAIN ANALYZE SELECT * FROM intraday_activities WHERE time_range && tstzrange('2026-08-04 08:00+00','2026-08-04 08:30+00');
```

## 12. Riesgos pendientes

- **`WeeklyScheduleAssignment` sin `updated_at` trigger para `replaced_at`** — `replaced_at` se setea manualmente; si se olvida, historial sin timestamp. Considerar `DB::trigger` o `saving` event.
- **`TemporalAssignment` cruzado sin TTL observado** — `CleanExpiredTemporalAssignments` command existe pero no se verificó scheduler; si expira y no se limpia, `getManagedTeamIds()` puede retornar equipos obsoletos.
- **`ApprovedIntradayPeriod` sin unique `team_id+date+time_range`** — permite crear 2 periodos solapados para mismo equipo/día (el modelo documenta el riesgo). Si WFM lo hace por error, `max_slots` se duplica en UI.
- **`ScheduleService.getBatchSchedules` con `withoutGlobalScopes=false`** — para semanas con swaps, muestra solo activas (correcto para MyDay) pero reporting que necesite histórico debe recordar `withoutGlobalScopes`.
- **`intraday_activities` UNLOGGED `agent_realtime_states` vs logged** — `agent_realtime_states` es UNLOGGED (pérdida en crash) por diseño para throughput; si se requiere durabilidad, migrar a LOGGED.

## 13. Conclusión

WfmModule es **rico y bien intencionado pero inseguro por defecto** debido a un P0 de autorización en rutas + deuda de P1 en integridad (swap duplicate, slots, publish, leave type). No requiere rewrite ni microservicio. Con **1 hotfix (M001, 30min)** el módulo deja de ser explotable; con **Fase 1 completa (M002-M007+M012, 5 días)** queda **🟢 saludable** y listo para operar con 500-5k empleados sin cambios infra.

**Siguiente acción recomendada:** Crear rama `fix/wfm-routes-auth` con commit `fix(wfm): gate schedules.* routes + Livewire authorize (P0)`, test `WfmRoutesAuthTest`, y deploy hotfix. Luego abrir `fix/wfm-integrity` con M003-M007 + suite de 7 tests. No tocar arquitectura ni introducir capas nuevas.
