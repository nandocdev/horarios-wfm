# Guía de Subsanación — Arquitectura del Módulo WFM

Problemas identificados y tareas concretas para resolverlos, ordenadas de menor a mayor esfuerzo.

---

## 1. Eliminar modelos duplicados

### 1.1 `AuditLog` — unificar las tres copias

Aparece en: `CoreModule\Models\AuditLog`, `AuditModule\Models\AuditLog`, `SupportModule\Models\AuditLog`.

- [x] 1.1.1 Elegir un solo modelo canónico (el de `AuditModule` es el que tiene sentido por nombre de módulo).
- [x] 1.1.2 En los otros dos módulos, reemplazar el `use` de la clase duplicada por el canónico. Eliminar los archivos duplicados.
- [x] 1.1.3 Migrar los datos a una sola tabla si existen tablas separadas. Si solo hay una tabla, es seguro borrar los modelos extra.
- [x] 1.1.4 Eliminar `SupportModule` si su único propósito era tener ese modelo.

### 1.2 `Article` — unificar DocumentationModule y KnowledgeModule

- [x] 1.2.1 Decidir si DocumentationModule y KnowledgeModule deben convivir o fusionarse. Si son conceptos distintos (manuales internos vs base de conocimiento operativa), renombrar uno para evitar ambigüedad.
- [x] 1.2.2 Si se fusionan, migrar datos y eliminar el módulo redundante.

### 1.3 `Category` — unificar CommunicationsModule y KnowledgeModule

- [x] 1.3.1 Decidir si el concepto de categoría es compartido. Si ambas usan `categorizables` polimórfico, unificar en un solo modelo en `Shared`.

---

## 2. Mover modelos y lógica fuera de módulos incorrectos

### 2.1 Repatriar lógica de WorkflowsModule a WfmModule (o viceversa)

WorkflowsModule tiene los modelos `LeaveRequest`, `LeaveRequestApproval`, `ShiftSwapRequest`, `ShiftSwapApproval` y las Actions de aprobación, pero NO tiene Livewire, rutas, ni vistas. Toda la UI está en WfmModule.

Hay dos caminos. Elegir uno:

**Opción A — WorkflowsModule es el dueño del dominio:**

- [ ] 2.1.A.1 Mover los Livewire de WfmModule que gestionan swaps y permisos a WorkflowsModule (`RequestShiftSwap`, `RequestLeave`, `ManagerApprovals`, `WfmSwapApprovals`, `LeaveRequestHistory`, `SwapRequestHistory`, `RequestSummary`).
- [ ] 2.1.A.2 Crear rutas en `WorkflowsModule/Routes/web.php`.
- [ ] 2.1.A.3 Registrar los componentes en `WorkflowsModule/Providers/ModuleServiceProvider.php`.

**Opción B — fusionar WorkflowsModule dentro de WfmModule:**

- [x] 2.1.B.1 Mover modelos y acciones de WorkflowsModule a `WfmModule/Models/` y `WfmModule/Actions/`.
- [x] 2.1.B.2 Eliminar WorkflowsModule del manifiesto `config/modules.php`.
- [x] 2.1.B.3 Mover migraciones a `database/migrations/` renombrándolas para orden cronológico.

### 2.2 Mover `app/Scheduling/LeaveRequest.php` — modelo huérfano

- [x] 2.2.1 Verificar si es usado en alguna parte (`grep -r "Scheduling\\\\LeaveRequest" app/`). Si no, eliminarlo.
- [x] 2.2.2 Si es usado, moverlo al módulo correspondiente (WorkflowsModule o WfmModule).

### 2.3 Mover `app/Livewire/Actions/Logout.php` a CoreModule

- [x] 2.3.1 Mover a `app/Modules/CoreModule/Livewire/Actions/Logout.php`.
- [x] 2.3.2 Actualizar el registro en el provider de CoreModule.
- [x] 2.3.3 Buscar referencias a la ruta/flux original y actualizar.

### 2.4 Mover `app/Services/MenuDataService.php` a un módulo o a Shared

- [x] 2.4.1 Si es usado por varios módulos, mover a `app/Shared/Services/MenuDataService.php`.
- [x] 2.4.2 Si solo lo usa un módulo, mover a ese módulo.

---

## 3. Romper dependencias directas entre módulos

### 3.1 Quitar observer de WfmModule sobre modelo de WorkflowsModule

Actualmente WfmModule tiene un `LeaveRequestObserver` que observa `WorkflowsModule\Models\LeaveRequest`.

- [x] 3.1.1 Mover el observer a WorkflowsModule. *(Auto-completado al aplicar la Opción B de la fusión: el observer es ahora puramente interno a WfmModule, eliminando el acoplamiento inter-modular)*
- [x] 3.1.2 En WfmModule, escuchar el evento compartido `LeaveRequestCreated`/`LeaveRequestDecision` en vez de observar el modelo directamente. *(Auto-completado; al estar ambos en el mismo dominio Wfm, se mantiene como observer local)*

### 3.2 Reemplazar imports directos de otros módulos por interfaces

Revisar qué módulos importan modelos de otros módulos directamente:

```bash
grep -r "use App\\\\Modules" app/Modules/ --include="*.php" | grep -v "ModuleServiceProvider"
```

- [x] 3.2.1 Evaluar cada dependencia directa y determinar el contrato adecuado.
- [x] 3.2.2 Refactorizar consumidores para programar contra interfaces:
  - OperationsModule: `CalculateAdvancedProductivityAction`, `CalculateRealAdherenceAction`, `GetEmployeePerformanceAction`, `GetStandardizedPerformanceAction`, `ReconcileEmployeeAttendanceAction`, `AgentPerformanceService`, `PerformanceService`
  - ConnectModule: `ImportUccxInboundAction`, `ImportUccxChatAction`, `ImportUccxPerformanceAction`, `ImportUccxTransitionsAction`, `AutoImportUccxCommand`, `CuicSyncCommand`
  - WfmModule: `AssignIntradayActivityAction`
- [x] 3.2.3 Crear y expandir interfaces: `EmployeeInterface` (+getTeamId, +getUserId), `EmployeeRepositoryInterface` (+findActive, +findByTeam, +findActiveByTeams, +findActiveByPositions)

---

## 4. Consolidar módulos de granularidad inconsistente

### 4.1 Evaluar SupportModule

- [x] 4.1.1 Si solo contiene `AuditLog` y un provider vacío, eliminarlo del manifiesto y borrar el directorio. *(Completado en la Fase 1 al unificar AuditLog y remover SupportModule por completo)*

### 4.2 Consolidar HelpdeskModule

- [x] 4.2.1 Evaluar si HelpdeskModule debe fusionarse con CommunicationsModule o mantenerse independiente.

**Decisión: Mantener independiente.** HelpdeskModule y CommunicationsModule pertenecen a dominios distintos:

| Aspecto          | CommunicationsModule                                      | HelpdeskModule                                                          |
| ---------------- | --------------------------------------------------------- | ----------------------------------------------------------------------- |
| Dominio          | Publicación de contenido (noticias, encuestas, shoutouts) | Ticketing y soporte (solicitud-respuesta)                               |
| Modelo de datos  | author_id → User, contenido versionado, polimórfico       | creator_id → Employee, assigned_agent_id → Employee, máquina de estados |
| Flujo de trabajo | Publicado/Archivado                                       | new → open → in_progress → resolved → closed                            |
| Permisos         | news.view/create/edit/delete, polls.view, etc.            | helpdesk.view, helpdesk.manage                                          |

**Problemas identificados en HelpdeskModule (corregir como tareas separadas):**
- No tiene capa Actions — la lógica de negocio está incrustada en Livewire
- Sin Policies — la autorización se hace inline con `can('operations.view')`
- Sin Enums — status y priority son strings mágicos
- Sin cobertura de tests
- `sla_hours` en categorías está almacenado pero nunca se usa

```
- [x] Extraer Actions: SubmitTicketAction, AssignTicketAction, ChangeTicketStatusAction, AddCommentAction
- [x] Crear HelpdeskTicketPolicy
- [x] Crear Enums: TicketStatus, TicketPriority
- [x] Migrar autorización inline a Policies via Gate
- [x] Escribir tests (16 tests, 30 assertions)
- [ ] Implementar monitoreo de SLA basado en sla_hours de categoría
```

### 4.3 Separar módulos demasiado grandes

PersonnelModule (15 modelos → 6, 25 Livewire → 7, 23 Actions → 14) y WfmModule (12 modelos, 25 Livewire, 17 Actions) son candidatos a dividir.

- [x] 4.3.1 Extraer organigrama (Directorate, Department, Position) a `OrganizationModule`.
  - 3 modelos, 12 Livewire, 9 Actions, 3 Policies, 3 Observers, 6 Events, 3 DTOs, 3 Controllers movidos
  - Employee model actualizado con imports a OrganizationModule
- [x] 4.3.2 Extraer geografía (Province, District, Township) a `GeoModule`.
  - 3 modelos, LocationController y vista movidos
  - PersonnelModule reducido a 6 modelos (Employee, Team, etc.)
- [ ] 4.3.3 Evaluar si los componentes de planificación semanal de WfmModule pueden separarse de los componentes de turnos base.

---

## 5. Unificar estilos arquitectónicos

### 5.1 Migrar controladores HTTP a Livewire (o viceversa)

Hay módulos que mezclan Livewire con controladores tradicionales.

- [x] 5.1.1 Evaluar cada controlador HTTP y eliminar código muerto.
  - **Eliminados (4)**:
    - `OrganizationModule\Http\Controllers\DepartmentController` — 0 rutas, TODO stubs
    - `OrganizationModule\Http\Controllers\DirectorateController` — 0 rutas, TODO stubs
    - `OrganizationModule\Http\Controllers\PositionController` — 0 rutas, TODO stubs
    - `PersonnelModule\Http\Controllers\TeamController` — 0 rutas, TODO stubs
    - La funcionalidad ya estaba cubierta por Livewire components.
  - **Mantenidos (11 activos)**: No se convierten a Livewire porque:
    - `CallRecordController`, `CiscoFinesseController` — son APIs JSON para integración Cisco
    - `AuditExportController` — descarga de archivos (respuesta binaria)
    - `ReactionController`, `CommentController` — endpoints AJAX livianos
    - `CategoryController`, `TagController`, `ContentModerationController` — CRUD tradicional con Form Requests + Actions (patrón correcto)
    - `EmployeeController` — renderiza vistas Livewire en READ, usa Actions en WRITE (híbrido válido)
    - `EmployeeExportController` — descarga CSV (invokable, single-action)
    - `LocationController` — JSON lookup (provincias/distritos), read-only
- [x] 5.1.2 Mover lógica restante a Actions.
  - `EmployeeController::destroy()` → `DeleteEmployeeAction` creado
  - El resto ya seguía el patrón `FormRequest → DTO → Action → Response`

### 5.2 Homogeneizar validación en Livewire Forms

- [x] 5.2.1 y 5.2.2 Migrar 5 componentes de `$rules` → `#[Rule]` attributes:

| Componente           | Módulo              | Campos                                                       |
| -------------------- | ------------------- | ------------------------------------------------------------ |
| `ListRoles`          | CoreModule          | name, code, hierarchy_level                                  |
| `ManageWikiArticles` | DocumentationModule | title, content, is_published, selectedCategories, sort_order |
| `TicketDetail`       | HelpdeskModule      | newComment                                                   |
| `MyTickets`          | HelpdeskModule      | subject, description, categoryId, priority                   |
| `RequestShiftSwap`   | WfmModule           | requestedDate, endDate, recipientId, reason                  |

Los 18 archivos en `Livewire/Forms/` ya usaban `#[Rule]` — ahora hay consistencia total.

### 5.3 Eliminar `DB::table()` en crudo

- [x] 5.3.1 Reemplazar `DB::table('operational_settings')` por modelo `OperationalSetting` (WfmModule).
  - `OperationalSettings.php` — 6 llamadas migradas a Eloquent (loadSettings, addGoal, removeGoal, save)
  - `GetStandardizedPerformanceAction.php` — 1 llamada migrada
  - `RealtimeMonitoring.php` — 1 llamada migrada
  - `ImportWeeklySchedule.php` — 2 llamadas migradas
- [x] 5.3.2 Migrar otros `DB::table()` a modelos Eloquent existentes:

| Tabla                     | Modelo                 | Archivos migrados                                                                    |
| ------------------------- | ---------------------- | ------------------------------------------------------------------------------------ |
| `agent_state_transitions` | `AgentStateTransition` | AgentTimeline, StateDistributionWidget, MyDay, PerformanceService                    |
| `call_records`            | `CallRecord`           | QueuePerformanceReport, QueueStatsWidget, VolumeComparisonWidget, PerformanceService |
| `agent_call_performance`  | `AgentCallPerformance` | AgentPerformanceService                                                              |
| `csq_realtime_stats`      | `CsqRealtimeStat`      | IntradayAvailability, PerformanceService                                             |
| `agent_realtime_states`   | `AgentRealtimeState`   | IntradayAvailability, AgentRealtimeCard                                              |
| `users`                   | `User`                 | SendShiftSwapApprovedNotification, SendShiftSwapReceivedNotification                 |

Total: **~25 llamadas `DB::table()` migradas** a Eloquent. Se conservaron solo aquellas con JOINs complejos, subqueries o agregaciones raw donde Eloquent no aporta claridad (ej: `CiscoSync`, `CriticalAlertsWidget`, `ImportTeamWeeklyScheduleAction`).

---

## 6. Crear capa de abstracción de datos

Actualmente solo hay 1 repositorio y 2 interfaces.

- [x] 6.1 Expandir `EmployeeRepositoryInterface` con `findByUser`, `getSubordinateIds` (CTE recursiva), `search` (ILIKE). Implementados en `EloquentEmployeeRepository`.
- [x] 6.2 Crear `ScheduleRepositoryInterface` con `getForEmployee`, `getForEmployees`, `getForDateRange`. Implementado `EloquentScheduleRepository` en WfmModule.
- [x] 6.3 Crear `AgentPerformanceRepositoryInterface` con `getCallRecords`, `getStateTransitions`, `getDailyMetric`, etc. Implementado `EloquentAgentPerformanceRepository` en OperationsModule.
- [x] 6.4 Inyectar repositorios en Actions:
  - `GetEmployeePerformanceAction` — inyecta `AgentPerformanceRepositoryInterface`
  - `CalculateAdvancedProductivityAction` — inyecta `AgentPerformanceRepositoryInterface`
  - 2 repositorios registrados como singletons en sus respectivos ModuleServiceProviders
  - Las Actions de operaciones ya programaban contra interfaces desde 3.2

---

## 7. Aumentar cobertura de pruebas

### 7.1 Prioridad alta — Acciones con lógica crítica

- [ ] 7.1.1 Probar `ApproveLeaveRequestAction`, `RejectLeaveRequestAction`, `ApproveShiftSwapAction` (WorkflowsModule).
- [ ] 7.1.2 Probar `ProcessShiftSwapAction`, todas las del módulo de operaciones (`CalculateAdvancedProductivityAction`, `ReconcileEmployeeAttendanceAction`).
- [ ] 7.1.3 Probar `ScheduleService` y `ScheduleValidationService`.
- [ ] 7.1.4 Probar integraciones CISCO (`CiscoFinesseService`, `CuicReportService`).

### 7.2 Prioridad media — Políticas

- [ ] 7.2.1 Escribir tests para cada Policy (son plantillas repetitivas, fáciles de cubrir con un test parametrizado).
- [ ] 7.2.2 Verificar que `hasPermissionTo` vs `can` vs `hasRole` se usan consistentemente.

### 7.3 Prioridad media — Livewire components

- [ ] 7.3.1 Los componentes de approvals y swaps deben tener tests de integración (ya existen algunos en `tests/Feature/Modules/ScheduleModule/`).
- [ ] 7.3.2 Extender cobertura a los componentes de creación/edición de empleados, turnos, y dashboards.

---

## 8. Políticas y autorización inconsistentes

### 8.1 Migrar autorización inline a Policies

- [ ] 8.1.1 En `OperationalSettings::save()`, reemplazar `abort(403)` por `Gate::authorize()` con una Policy existente o crear una nueva (`OperationalSettingPolicy`).

### 8.2 Unificar sistema de permisos

Algunas Policies mezclan `$user->hasPermissionTo()` con `$user->can()` y `$user->hasRole('admin')`.

- [ ] 8.2.1 Decidir una estrategia única: usar `$user->can('permission.name')` que delega en `Gate::policy()`.
- [ ] 8.2.2 Refactorizar todas las Policies para que usen el mismo patrón.
- [ ] 8.2.3 El `Gate::before` en AppServiceProvider que da todos los permisos a admin ya existe — las verificaciones de `hasRole('admin')` inline son redundantes.

---

## 9. Migración de datos y limpieza

- [ ] 9.1 Si hay tablas duplicadas para modelos unificados, crear migraciones que consoliden los datos y dropeen las tablas sobrantes.
- [ ] 9.2 Renombrar migraciones que estén fuera de orden cronológico.
- [ ] 9.3 Documentar en `config/modules.php` el propósito de cada módulo.

---

## Resumen de ejecución sugerida

| Fase         | Tareas                            | Impacto                             |
| ------------ | --------------------------------- | ----------------------------------- |
| 1 (rápido)   | 1.1, 1.2, 1.3, 2.2, 2.3, 4.1, 8.1 | Bajo — solo mover/eliminar archivos |
| 2 (semanal)  | 2.1, 2.4, 3.1, 5.2, 5.3, 8.2      | Medio — refactor lógica             |
| 3 (mensual)  | 3.2, 4.3, 5.1, 6                  | Alto — cambios arquitectónicos      |
| 4 (continuo) | 7                                 | Cobertura de pruebas                |
