# 🗺️ ROADMAP DE DESARROLLO — WFM Call Center CSS (v2.0)

**Proyecto:** HorariosWFM (Monolito Modular)
**Estado:** Actualizado a Marzo 2026
**Arquitectura:** Laravel 12 + Livewire 3 + FluxUI + PostgreSQL
**Uso para IA:** Copiar la referencia de la tarea (ej. `UC-LRQ-01`) y pasarla al agente junto con el prompt de `new-task`.

---

## 📊 MÉTRICAS Y ESTADO ACTUAL (Marzo 2026)

* **Progreso General:** ~80% del sistema funcional (Núcleo base operativo).
* **Testing:** Suite base completa (31 tests pasando, 72 assertions). CI/CD configurado.
* **UI/UX:** Integración global de FluxUI y Heroicons completada. Formularios reactivos.
* **Arquitectura:** Patrón `ModuleServiceProvider` estandarizado, Actions, DTOs, transacciones DB.

### ⚠️ Deuda Técnica Global (Obligatorio para nuevas tareas IA)
- [ ] **GLO-01 (ULIDs):** Todo nuevo modelo debe heredar de un `BaseModel` que use **ULIDs** como Primary Key, no IDs autoincrementales.
- [ ] **GLO-02 (Performance):** Mantener `preventLazyLoading` activo. Toda consulta nueva debe usar `with()`.
- [ ] **GLO-03 (Testing):** Todo nuevo `UC-XXX` debe incluir su respectivo Feature Test en Pest/PHPUnit.

---

## 🟢 FASE 1 / SPRINTS 0-1: Foundation & Organization (Completado ✅)

> *Base sólida, permisos, auditoría y estructura organizativa. Integración de FluxUI.*

### Módulo: Core & Organization & Geography
- [x] UC-COR-01: Autenticación Fortify/Sanctum, Login y Rate Limiting.
- [x] UC-COR-02: CRUD de Usuarios y SoftDeletes.
- [x] UC-COR-03: RBAC (Spatie Permissions) jerárquico + Caché de permisos.
- [x] UC-ORG-01: Estructura organizacional completa (Directorates → Teams).
- [x] UC-GEO-01: Catálogo geográfico (LocationModule).
- [ ] NA: Forzar cambio de contraseña en primer login (Implementar en Fase 2).

### Módulo: Audit & Cache
- [x] UC-AUD-01: Modelo `AuditLog` y Trait `Auditable` implementado.
- [x] UC-AUD-02: Componente UI Livewire (FluxUI) para visor/búsqueda de logs operativos.
  - [x] UC-AUD-02.1: Listado paginado de `audit_logs` con filtros (usuario, entidad, acción, rango de fechas).
  - [x] UC-AUD-02.2: Acción de exportar CSV/JSON para resultados filtrados.
  - [x] UC-AUD-02.3: Políticas de acceso `audit.view` y `audit.export` (Spatie + Gate).
  - [x] UC-AUD-02.4: Test de integración Pest para UI y API de logs.
- [x] UC-AUD-03: Command de retención/limpieza de logs (`audit:prune --days=...`).
- [x] UC-CAC-01: Caché inteligente habilitado en config e invalidación en Observers.

---

## 🟢 FASE 2 / SPRINT 2: Employees (Completado ✅)

> *Núcleo del negocio. Gestión de personal y jerarquías operativas.*

### Módulo: Employees
- [x] UC-EMP-01: Modelos `Employee`, `EmploymentStatus` con jerarquía (`parent_id`).
  - [x] UC-EMP-01.1: Relaciones completas con `Team`, `Department`, `Position` y cascada de desactivación por status.
  - [x] UC-EMP-01.2: Índices de DB `employee(team_id, status_id, deleted_at)` y `status(parent_id)`.
- [x] UC-EMP-02: Interfaz Livewire reactiva con filtros avanzados (búsqueda, dpto, cargo).
  - [x] UC-EMP-02.1: Paginación server-side con `with('team','position','status')` + anti N+1 test.
  - [x] UC-EMP-02.2: Export CSV/Excel desde UI (selected/all) y parámetros de rango de fechas.
- [x] UC-EMP-03: Policies con scope por `team_id` y permisos granulares.
  - [x] UC-EMP-03.1: Distintos scopes `own` vs `others` y `force_delete` solo para roles de alto privilegio.
  - [x] UC-EMP-03.2: Policy `effectivePermissions` con role hierarchy y administrator override.
- [x] UC-EMP-04: Importador masivo CSV. **[DEUDA TÉCNICA: Refactorizado a Chunked/Queueable para evitar memory leaks]**.
  - [x] UC-EMP-04.1: Acción `ImportEmployeesAction` con `LazyCollection::chunk(1000)` + `DB::transaction` por chunk.
  - [x] UC-EMP-04.2: Job/Batch en queue y reporte de filas rechazadas / inválidas.
  - [x] UC-EMP-04.3: Manejo de duplicados, relaciones inexistentes (`position`, `team`) y rollback selectivo.

---

## 🔵 FASE 3 / SPRINT 3: Scheduling (Core Técnico) (En Progreso 🟡)

> *Módulo más complejo. Riesgo alto de consultas N+1 y solapamientos.*

### Objetivo general

Diseñar e implementar el módulo de Scheduling que reemplaza el proceso Excel, garantizando validaciones en tiempo real (no solapamientos), auditoría completa y publicación semanal automatizada.

### Entregables clave (Sprint 3)

- [x] UC-SCH-01: Modelos base `Schedule`, `BreakTemplate` y `Shift` con ULIDs y casts.
- [x] UC-SCH-02: CRUD y Actions atómicos para `BreakTemplate` (Livewire + FluxUI).
- [x] UC-SCH-03: `ScheduleValidationService` (validaciones: start < end, contigüidad, no-solapamiento por empleado/role/puesto).

### Weekly Planning (Sprint 3 → Sprint 4)

- [x] **UC-PRE-01:** Validación de preparación pre-planificación (PASO 1)
  - `ValidatePreparationAction`: Sincronizar empleados activos, validar estructura de equipos y catálogo
  - `PreparationValidationDTO`: Resultados de validación con problemas bloqueantes y warnings
  - `PreparationValidator` Livewire: UI para mostrar estado de preparación con métricas visuales
  - Tests feature completos y permisos `schedules.validate_preparation`
- [ ] UC-WPL-01: Modelos `WeeklySchedule`, `WeeklyScheduleAssignment` (estructura para publicaciones semanales).
- [x] UC-WPL-01: Modelos `WeeklySchedule`, `WeeklyScheduleAssignment` (estructura para publicaciones semanales).
- [ ] UC-WPL-02: Constraints y migraciones: índices compuestos y constraints DB (`unique` y `exclude overlaps` donde aplique).
- [x] UC-WPL-02: Constraints y migraciones: índices compuestos y constraints DB (`unique` y `exclude overlaps` donde aplique).
- [x] UC-WPL-03: `AssignEmployeeScheduleAction` (bulk inserts, validación por lote con `ScheduleValidationService`).
- [x] UC-WPL-04: Componente Livewire para asignación en grid con edición masiva y preflight validation.

### Timeline y hitos (alineado con `docs/features/schedule.md`)

- Semana 9: Implementar modelos base y DDL (UC-SCH-01). Entregable: migraciones + modelos + observers.
- Semana 10: Implementar CRUD de plantillas y Actions (UC-SCH-02). Entregable: Livewire Forms + tests feature.
- Semana 11: Publicación & "Mi Horario" (primer MVP de publicación semanal y vista "Mi Horario").
- Semana 12: Pruebas de performance y validación masiva (solapamientos, bulk assign). Ajustes antes de integrar WeeklyPlanning.

### Criterios de aceptación (DoD)

- Toda inserción/actualización en Scheduling debe ejecutarse dentro de `DB::transaction()`.
- No permitir lazy-loading en endpoints/Livewire; usar `with()` y tests que verifiquen ausencia de N+1.
- `ScheduleValidationService` cubre: start < end, solapamiento por empleado y colisión con `BreakTemplate` y `IntradayActivity`.
- Tests: Feature tests en Pest para Actions, y pruebas unitarias para `ScheduleValidationService` (casos borde: end == existing.start permitido).
- Migraciones incluyen constraints explícitos de PostgreSQL cuando corresponda (exclusion constraints si procede).

### Riesgos y mitigaciones

- Riesgo: Validación de solapamientos costosa en consultas grandes. Mitigación: indexar por employee_id + date, usar queries por rango y bulk checks en memoria por batch.
- Riesgo: Publicación semanal puede bloquear operaciones si hay jobs largos. Mitigación: publicar usando Jobs/Batch con feedback (progress) y estrategias de retriable slices.

### Tareas futuras (Sprint 4+)

- [ ] Integrar `LeaveRequest` y `ShiftSwap` con el motor de validación para evitar inconsistencias.
- [ ] Enlace con CISCO para registrar asistencia real-time y reconciliar incidencias con `AttendanceIncident`.
- [ ] Dashboard de cumplimiento de horario y reportes automatizados (UC-REP-02).


---

## � FASE 4 / SPRINT 4: Workflows (Flujo Operativo Real) (Completado ✅)

> *Desacoplado en módulos independientes por principios SOLID.*

### Objetivo

Construir el conjunto de Workflows operativos que permitan a empleados y coordinadores gestionar permisos (vacaciones, ausencias), intercambios de turno y aprobaciones con trazabilidad y validación automática contra el motor de Scheduling.

### Entregables (Sprint 4)

- [x] UC-LRQ-01: Modelos `LeaveRequest` y `LeaveRequestApproval` con estados y trazabilidad (aprobado/pendiente/rechazado).
- [x] UC-LRQ-02: `CreateLeaveRequestAction` que valida contra `ScheduleValidationService` y `WeeklySchedule` para asegurar que no se rompa cobertura.
- [x] UC-LRQ-03: `ApproveLeaveRequestAction` / `RejectLeaveRequestAction` con comentarios y auditoría (observer/event → AuditLog).
- [x] UC-LRQ-04: Bandeja Livewire para Coordinadores con filtros por `team_id`, fecha, estado y acciones en lote.

- [x] UC-SWP-01: Modelo `ShiftSwapRequest` con estado y referencias a `WeeklyScheduleAssignment` originales.
- [x] UC-SWP-02: `CreateShiftSwapRequestAction` + `RespondToShiftSwapAction` para manejo de aceptación/rechazo por la contraparte.
- [x] UC-SWP-03: `ApproveShiftSwapAction` que valida compatibilidad horaria usando `ScheduleValidationService` y chequea cobertura mínima.

### Timeline y criterios de aceptación

- Semana 13: Modelos y migraciones (LRQ, SWP) con constraints y tests unitarios.
- Semana 14: Implementación Actions y reglas de negocio (validaciones contra solapamientos, cobertura mínima).
- Semana 15: Livewire UI para coordinadores y usuarios (solicitudes, respuesta, historial).
- Semana 16: Integración con Jobs/Notifications y pruebas end-to-end.

Aceptación (DoD):

- Todas las solicitudes pasan por `FormRequest` y Policies; los Actions se ejecutan dentro de `DB::transaction()`.
- Validaciones automáticas contra solapamientos a través de `ScheduleValidationService`.
- Auditoría completa: quien solicitó, quien aprobó, motivo y timestamps en `AuditLog`.
- Tests: Feature tests para flujo completo (crear → responder → aprobar/rechazar) y tests unitarios para Actions.

### Riesgos y mitigaciones

- Riesgo: Gran volumen de solicitudes concurrentes que bloquean la publicación semanal. Mitigación: procesar aprobaciones en Jobs en background y aplicar locks por `weekly_schedule_id` para evitar race conditions.
- Riesgo: Usuarios forzando cambios que violan cobertura. Mitigación: bloquear aprobaciones si coverage < threshold y requerir escalado manual.

---

## � FASE 5 / SPRINT 5: Operations (Intradía e Incidencias) (En Progreso 🟡)

> *Control en tiempo real del piso de operaciones.*

### Objetivo

Implementar las capacidades de operación en tiempo real: planificación intradía, actividades puntuales, registro y reconciliación de incidencias de asistencia (tardanzas, ausencias) integradas con el motor de Scheduling y con fuentes externas (CISCO).

### Entregables (Sprint 5)

- [ ] UC-INP-01: Modelos `IntradayActivity` y `IntradayActivityAssignment` que permitan actividades temporales sin romper el turno principal.
- [ ] UC-INP-02: `AssignIntradayActivityAction` con validaciones de capacidad y bloqueo de conflictos con `ScheduleValidationService`.
- [ ] UC-INP-03: UI FluxUI (MyDay) con timeline y posibilidad de reasignar actividades intra-día.

- [x] UC-ATT-01: Modelos `IncidentType` y `AttendanceIncident` con integración de fuentes (CISCO events).
- [x] UC-ATT-02: `RecordAttendanceIncidentAction` que crea incidentes, notifica al empleado y sugiere acciones (justificar, solicitar permiso).
- [x] UC-ATT-03: Componentes UI para gestionar incidencias y reconciliación automática con `LeaveRequest` y `WeeklySchedule`.

### Timeline y criterios de aceptación

- Semana 17: Modelos y APIs para Intraday + Attendance; migraciones y tests unitarios.
- Semana 18: Implementar MyDay timeline (Livewire) y asignación de actividades.
- Semana 19: Integración con CISCO (punto de partida: ingest de eventos) y pruebas de reconciliación.
- Semana 20: Robustez y performance (pruebas de carga para ingest masivo de eventos y conciliación).

Aceptación (DoD):

- Integración con CISCO debe ser desacoplada (Jobs/Queue) y logueada en `AuditLog`.
- Incidencias creadas por fuentes externas deben poder mapearse automáticamente a `LeaveRequest` o generar alertas para intervención manual.
- No lazy-loading en endpoints que repueblen UI; usar `with()` y paginación eficiente.
- Tests: cobertura de integración (ingest → reconciliación → notificaciones) y pruebas de performance.

### Riesgos y mitigaciones

- Riesgo: Volumen alto de eventos CISCO que generan spikes. Mitigación: ingest por batches y backpressure en queues; escalar workers.
- Riesgo: Desajuste entre eventos y asignaciones semanales. Mitigación: implementar reconciliación heurística y fallback manual con trazabilidad.

### Dependencias críticas

- `ScheduleValidationService` — usado por Workflows, WeeklyPlanning e Intraday.
- Jobs/Queue — para ingest de eventos, publicación y reconciliación.
- Observers/Events — para mantener `AuditLog` y cache coherente.


---

## 📊 FASE 6 / SPRINT 6: Reporting & Analytics

### Módulo: Reports & Dashboard
- [ ] UC-REP-01: `AttendanceReportService` y exportación a Excel/CSV.
- [ ] UC-REP-02: `ScheduleComplianceReportService` (Adherencia al horario).
- [ ] UC-DBR-01: Dashboard Livewire con KPIs (Ausentismo, Cobertura) integrando Recharts/Chart.js.

---

## 🌐 FASE 7 / SPRINT 7: Engagement (Ampliación v2.0)

> *Funcionalidades de valor agregado para retención y servicio externo.*

- [x] UC-COM-01: Comunicaciones internas (`NewsArticle`, `NewsCategory`) - **Implementado parcialmente**.
- [ ] UC-SUR-01: Encuestas internas y clima laboral (`Surveys`).
- [ ] UC-CIT-01: Gestión de Ciudadanos y registro de llamadas entrantes (`Citizens`).

---

## 🛠️ INSTRUCCIONES DE USO PARA EL AGENTE DE IA

1. **Lee el contexto:** Antes de codificar, analiza este roadmap y el prompt principal.
2. **Usa el Código UC:** El usuario te dará un código (Ej. `UC-WPL-03`).
3. **Aplica Deuda Técnica (GLO):** Automáticamente asegura que tu código cumpla con las reglas GLO-01, GLO-02 y GLO-03 (ULIDs, no N+1, y Tests).
4. **Respeta Git Flow:** Genera los comandos de rama, los commits atómicos y el código listo para producción.
