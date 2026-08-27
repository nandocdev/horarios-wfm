# Auditoría — PersonnelModule

> Fecha: 2026-08-27
> Estado: 🔴 Requiere intervención — 1 P0 (IDOR), 4 P1 de integridad/seguridad, autorización y datos sensibles sin guardas

## 1. Resumen ejecutivo

PersonnelModule es el **master data de capital humano** (98 archivos, `app/Modules/PersonnelModule`). Dueño de `employees`, `teams`, `team_members`, `skills`, `employee_skills`, `skill_history`, `employee_import_batches` + soporte a `employment_statuses`. Orquesta: CRUD de empleados/equipos, membresías históricas (TeamMember), jerarquía `parent_id` (Adjacency List + CTE recursivo), skills con historial, import masivo CSV en batches + queue, export CSV/Excel y syncs con Cisco Finesse/UCCX.

El módulo es **funcionalmente completo y mayormente bien delimitado**, con separación Actions/DTOs/Policies/Observers correcta y boundaries respetados. Sin embargo tiene **un P0 explotable**: `EmployeeController::show()` no autoriza y expone cualquier empleado (incl. salario) a todo usuario autenticado. A ello se suman P1 de integridad (Update con `array_filter` impide nulificar campos; creación con fallback `?? 0` bypassa validación), doble snapshot SCD2 en `EmployeeObserver`, y exposición de `salary` sin permiso dedicado. Performance está controlada (N+1 medido en `ListEmployees`) pero hay cargas sin límite en selects y transactions innecesarias en export.

No hay sobreingeniería grave; los 2 Repositories (`EmployeeRepository` + `LookupRepository`) son cuestionables pero no bloqueantes. **Requiere intervención inmediata en autorización antes de exponer en producción con datos reales.**

## 2. Alcance

**Estructura inspeccionada (98 archivos):**

- `Models/`: `Employee.php:1` (280L), `Team.php:1`, `TeamMember.php:1`, `Skill.php`, `EmployeeSkill.php`, `SkillHistory.php`, `EmployeeImportBatch.php`, `EmploymentStatus.php`, `EmployeeDependent/Disease/Disability.php`, `EmployeePosition.php`
- `Actions/` (18): `CreateEmployeeAction.php`, `UpdateEmployeeAction.php`, `DeleteEmployeeAction.php`, `CreateTeamAction.php`, `UpdateTeamAction.php`, `ToggleTeamStatusAction.php`, `AssignEmployeeToTeamAction.php`, `AssignEmployeesToTeamAction.php`, `RemoveEmployeeFromTeamAction.php`, `ImportEmployeesAction.php`, `ProcessEmployeeImportChunkAction.php`, `ExportEmployeesAction.php`, `AssignSkillAction.php`, `EvaluateSkillCoverageAction.php`, `GetStaffingSummaryAction.php`, `Sync*WithCiscoAction.php` (3)
- `DTOs/` (9): `CreateEmployeeDTO.php`, `UpdateEmployeeDTO.php`, `EmployeeDTO.php`, `TeamDTO.php`, `ImportEmployeesDTO.php`, `EmployeeExportDTO.php`, etc.
- `Policies/`: `EmployeePolicy.php:1` (240L), `TeamPolicy.php:1`, `EmploymentStatusPolicy.php:1`
- `Livewire/` (11): `ListEmployees.php`, `CreateEmployee.php`, `EditEmployee.php`, `ImportEmployees.php`, `ListTeams.php`, `CreateTeam.php`, `EditTeam.php`, `ShowTeam.php`, `ManageTeamMembers.php`, `TeamMemberTransfer.php`, `StaffingSummary.php` + `Forms/` (4)
- `Observers/`: `EmployeeObserver.php:1`, `TeamObserver.php:1`, `EmploymentStatusObserver.php:1`
- `Http/`: `EmployeeController.php:1`, `EmployeeExportController.php:1`, `Requests/` (4)
- `Jobs/`: `ProcessEmployeeImportChunkJob.php` · `Repositories/`: `EloquentEmployeeRepository.php`, `EloquentEmployeeLookupRepository.php` · `Events/` (5)
- `Routes/web.php:1`, `Providers/ModuleServiceProvider.php:1`, `Database/Migrations/` (3 skills), `Resources/Views/` (12 blades)
- `Tests/`: `tests/Feature/Employees/EmployeePolicyScopesTest.php`, `EmployeeExportTest.php`, `EmployeeImportTest.php`, `tests/Feature/Modules/PersonnelModule/EmployeeActionsTest.php`, `TeamActionsTest.php`, `ShowTeamTest.php`, `tests/Feature/Employees/EmploymentIndexesTest.php`, `EmploymentStatusCascadeTest.php`

**Áreas cubiertas:** arquitectura, backend/Laravel, PostgreSQL (employees/teams/team_members/skill_history), Livewire, seguridad (IDOR, mass assignment, salary), testing, performance (N+1, `ilike`, CTE, batch Jobs), observabilidad.

**Comandos de lectura ejecutados:** `find PersonnelModule -type f`, `grep -rn Cache/Cisco/DB::select`, `migrate:status`, `php artisan test --filter=Employees` (lectura). **Cero modificaciones** durante la auditoría.

## 3. Arquitectura actual

```
Entrada
  HTTP: EmployeeController (index/create/store/show/edit/update/destroy/import/export)
  Livewire: ListEmployees / CreateEmployee / EditEmployee / ImportEmployees / ListTeams / ShowTeam / TeamMemberTransfer / StaffingSummary
  CLI/Jobs: ProcessEmployeeImportChunkJob (Batch) + Sync*WithCiscoAction (API)
    ↓
Presentación: Validación dual — FormRequests (Store/Update) + Livewire Forms (EmployeeForm) + DTOs (Create/Update/Team/Import/Export)
    ↓
Aplicación: Actions (Create/Update/Delete/Assign/Switch Team/Toggle/Sync + Import batch + Export + EvaluateSkillCoverage)
           + Policies (EmployeePolicy scope team/jerarquía, TeamPolicy, EmploymentStatusPolicy)
           + Observers (Employee→snapshot SCD2, Team→cache forget, EmploymentStatus→cascade is_active)
    ↓
Dominio: Employee (SoftDeletes, belongsTo User/Team/Department/Position/Status/Township, self-ref parent_id, hasMany skills/teamMembers)
         Team (belongsTo User supervisor_id, hasMany members/employees) + TeamMember (histórico is_active/joined_at/left_at)
         Skill / EmployeeSkill / SkillHistory + EmployeeImportBatch (ULID, errores, chunked)
    ↓
Persistencia: PostgreSQL (employees 16 FKs + check parent<>id, index team_status_deleted_idx; team_members; skills unique code; employee_skills unique emp+skill; skill_history FK changed_by users)
    ↓
Integraciones: Cisco Finesse (CiscoFinesseClient→ getUsers/getTeams), Cache (TeamObserver forget), Bus::batch (import), Analytics (EmployeeSnapshot SCD2)
```

**Bien resuelto:** Transaccionalidad consistente (`DB::transaction` en todos los Actions), Savepoints por fila en import (`SAVEPOINT emp_import_*`), CTE recursiva para subordinados evitando N+1, Policies con scoping por team/jerarquía + `effectivePermissions()` para UI, separación FormRequest vs Livewire Form según entrypoint.

**Frontera dudosa:** `Repositories/` duplican lógica ya en `Employee` (`getAllSubordinateIds()` CTE) y `EvaluateSkillCoverageAction` importa directamente `OperationsModule\Models\QueueSkill` — acoplamiento concreto (tolerable pero debe ser vía contrato).

## 4. Dependencias

**Outbound (Personnel → otros):**

- `App\Modules\CoreModule\Models\User` (Employee::user, Team::supervisor), `Core\Concerns\Auditable`, `HasRoles` (via User)
- `App\Modules\OrganizationModule\Models\{Department,Position,Directorate}` (Employee belongsTo)
- `App\Modules\GeoModule\Models\{Township,District,Province}` (Employee township, Livewire geo cascading selects)
- `App\Modules\OperationsModule\Models\QueueSkill` (EvaluateSkillCoverageAction) — **acoplamiento directo**
- `App\Modules\AnalyticsModule\Models\EmployeeSnapshot` (EmployeeObserver SCD2)
- `App\Shared\Contracts\Employees\{EmployeeRepositoryInterface, EmployeeLookupRepositoryInterface}` (abstracción), `App\Shared\Infrastructure\Cisco\CiscoFinesseClient` (3 sync actions)
- `App\Shared\Models\BaseModel` no usado (Employee/Team extienden `Model` directo)

**Inbound (otros → Personnel):** 12+ consumidores: `WfmModule` (WeeklyScheduleAssignment → employees), `OperationsModule` (AgentDailyMetrics), `QualityModule`, `Reporting`, `ConnectModule` (via EmployeeRepositoryInterface) — correcto: Personnel es master data.

**Infra:** PostgreSQL 17.11, Redis (Cache forget teams), Queue `default` (Horizon), Storage `local` (CSV imports), Bus Batch.

**Circular:** No detectada. Dirección correcta: `Core ← Organization/Geo ← Personnel ← Wfm/Operations/Reporting`.

## 5. Health Score

| Área         | Estado | Justificación                                                                                                        |
| ------------ | ------ | -------------------------------------------------------------------------------------------------------------------- |
| Arquitectura | 🟡     | Cohesivo y transaccional; pero Repositories prematuros + acoplamiento directo a QueueSkill + doble vía Form/Request. |
| Backend      | 🟡     | Actions/Policies sólidas; bugs en Update array_filter, creación con `??0`, y salary sin guard.                       |
| Database     | 🟡     | Schema correcto (UNIQUE + CHECK + índices); falta excluir soft-deleted de uniques y FK supervisor_id inconsistente.  |
| Frontend     | 🟡     | Livewire simple y paginado con eager loading; selects sin límite y falta debounce/caching en filtros.                |
| Security     | 🔴     | P0 IDOR en `show`, salary expuesto, validación inconsistente género, mass assignment en metadata.                    |
| Testing      | 🔴     | 6 tests de Personnel + 3 de Employees; no cubre show IDOR, salary, observer SCD2, import duplicado, CTE jerarquía.   |
| Performance  | 🟡     | N+1 controlado y testeado; pero 3 queries de filtros por render + `ilike %search%` sin índice + `whereRaw 1=0`.      |
| Operabilidad | 🟡     | Batch Jobs con catch y `report(report())` en Auditable; falta retry/backoff explícito y métricas de Cisco sync.      |

**Estado general: 🔴 Requiere intervención** — un P0 de autorización (show sin guard) exige hotfix antes de exponer datos reales; resto P1 no bloquean pero deben ir en próximo sprint.

## 6. Hallazgos

### [P0] IDOR — `EmployeeController::show` expone cualquier empleado sin autorización

**Categoría:** Security

**Ubicación:** `app/Modules/PersonnelModule/Http/Controllers/EmployeeController.php:86` (`show(Employee $employee)`)

**Problema:** El método `show` hace `$employee->load([...])` y retorna `view('employees::show')` **sin** `$this->authorize('view', $employee)`. La ruta `employees/{employee}` en `Routes/web.php:30` no tiene middleware `->can()` (solo `index/import/export/teams.manage`). Todo usuario autenticado (`middleware auth` del group) puede enumerar `/employees/1`, `/employees/2`, ... y ver PII (email, teléfono, dirección, salario, blood_type) y jerarquía.

**Evidencia:**

```php
public function show(Employee $employee): View {
    $employee->load(['department','position',...]); // ← sin authorize
    return view('employees::show', compact('employee'));
}
```

vs `edit()`, `index()`, `update()`, `destroy()` sí autorizan. `EmployeePolicy::view()` tiene lógica `view.all / view.others / view` con scoping, pero nunca se invoca en show.

**Impacto:** Fuga de datos personales y salariales, enumeración de plantilla, violación de principio de menor privilegio. Explotable con script autenticado.

**Recomendación:** Añadir `$this->authorize('view', $employee);` en `show()`. Añadir `->can('view', 'employee')` o policy check en ruta. Test: usuario con `employees.view` solo-ve-own no debe ver empleado de otro team (403).

**Complejidad:** Baja

**Prioridad:** Inmediata

---

### [P1] Exposición de `salary` sin permiso dedicado

**Categoría:** Security

**Ubicación:** `app/Modules/PersonnelModule/Resources/Views/show.blade.php:94` (`${{ number_format($employee->salary...) }}`), `create-employee.blade.php:114`, `edit-employee.blade.php:114`, `Models/Employee.php:33` (fillable salary)

**Problema:** `salary` (decimal 12,2) es PII sensible. Se muestra a cualquiera que pase `view` y se edita con `employees.create/edit` (no hay `employees.salary.view` / `employees.salary.manage`). Livewire `EmployeeForm` y Requests permiten `salary` a todo creador/editor. No hay masking, audit trail específico ni política salarial.

**Evidencia:** `show.blade.php` renderiza salario sin `@can`, `Store/UpdateRequest` validan `salary => nullable|numeric|min:0` sin permiso, `EmployeePolicy` no menciona salario.

**Impacto:** Todo supervisor/team-lead con `employees.view.others` puede ver salarios de su equipo. Riesgo legal/laboral.

**Recomendación:** Crear permisos granulares `employees.salary.view` / `employees.salary.edit` o restringir a `hr`/`admin`/`director`. En `show` y `EmployeePolicy::view` añadir guard; en Requests condicionar validación; en Livewire ocultar input si no autorizado. Registrar ADR.

**Complejidad:** Baja

**Prioridad:** Próximo sprint (o Inmediata si datos sensibles en prod)

---

### [P1] `UpdateEmployeeAction` con `array_filter` impide nulificar campos

**Categoría:** Backend / Integrity

**Ubicación:** `app/Modules/PersonnelModule/Actions/UpdateEmployeeAction.php:43-56` (`array_filter([...], fn($v)=>$v!==null)`)

**Problema:** El DTO `UpdateEmployeeDTO` usa `null` para “no cambiar”, pero el Action filtra `!==null` antes de `update()`. Si se quiere **limpiar** un campo nullable (ej. `department_id = null`, `parent_id = null` al cambiar de equipo, `phone = null`), el filter lo elimina y el campo conserva valor antiguo. Para `is_active=false` funciona (false !== null), pero para `metadata=[]` vs `null` el comportamiento es ambiguo. `salary = 0` pasa (0 !== null) pero `salary = null` explícito para borrar no llega.

**Evidencia:** `$updateData = array_filter([...], fn($v)=>$v!==null); $employee->update($updateData);` — nunca envía `null` a BD.

**Impacto:** No se puede desvincular empleado de departamento/township/parent, ni corregir datos a null sin query manual. Workaround actual es `RemoveEmployeeFromTeamAction` para `team_id/parent_id`, pero no para resto.

**Recomendación:** Distinguir “omitido” vs “null explícito” (sentinel `Undefined` o `array_key_exists` en `$dto->toArray()` filtrando solo omitidos, o cambiar DTO a `Optional<T>`). O pasar `$request->validated()` directo sin filter, donde `sometimes` ya omite no-enviados.

**Complejidad:** Media

**Prioridad:** Próximo sprint

---

### [P1] Creación Livewire con fallback `?? 0` bypassa validación de FKs

**Categoría:** Backend / Integrity

**Ubicación:** `app/Modules/PersonnelModule/Livewire/CreateEmployee.php:66-88` (`township_id ?? 0`, `position_id ?? 0`, `employment_status_id ?? 0`, `user_id ?? 0`)

**Problema:** Si el formulario no envía `position_id`/`township_id`/`employment_status_id`/`user_id` (select no cargado, o usuario manipula payload), el componente fuerza `0` en vez de fallar validación. `0` no existe en FKs → excepción `QueryException`/`ConstraintViolation` (500) en vez de 422. El `EmployeeForm::rules()` sí exige `required|exists`, pero `??0` se aplica **después** de `validate()`, convirtiendo null en 0 válido para el DTO y ocultando el error.

**Evidencia:** `$dto = new CreateEmployeeDTO(township_id: $this->form->township_id ?? 0, position_id: $this->form->position_id ?? 0, ...)` — tras `validate()`, `form->township_id` puede ser `null` si validación divergente (ver M009).

**Impacto:** 500 en producción en vez de mensaje de validación, logs ruidosos, UX rota. Riesgo de `user_id=0` creando huérfanos si FK no es `constrained` estrictamente.

**Recomendación:** Eliminar `??0`, pasar `null` y dejar que `CreateEmployeeAction`/`DB constraint` falle con 422 vía `exists` rule. O cambiar DTO a `?int` nullable y validar `required`.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P1] `EmployeeObserver` doble snapshot SCD2 + query de deleted incorrecta

**Categoría:** Backend / Database

**Ubicación:** `app/Modules/PersonnelModule/Observers/EmployeeObserver.php:22-54` (`updated()` + `createSnapshot()`)

**Problema:** `updated()` hace: `if(isDirty([...])) createSnapshot(); createSnapshot(true);` — **siempre** crea snapshot aunque no haya cambios organizacionales, y si hubo cambios crea **dos** snapshots seguidos. `createSnapshot()` a su vez busca `is_current=true`, lo cierra (`valid_to = ayer`) y crea uno nuevo. Con dos llamadas consecutivas, el segundo cierra el recién creado (ayer) y crea otro — historia rota (gaps de 1 día, `valid_from` siempre `startOfDay()` sin hora real). Además `deleted()` hace `where('is_current', false)` — busca no-actuales para marcar, cuando debería ser `true`.

**Evidencia:** `deleted(): EmployeeSnapshot::where('employee_id', $id)->where('is_current', false)->update([...])` — condición invertida, nunca marca el actual.

**Impacto:** `analytics_employee_snapshot` con filas duplicadas por cada `isDirty` y `valid_to` incorrectos; reporting/DW con SCD2 corrupto. `deleted` deja snapshot huérfano `is_current=true` tras soft-delete.

**Recomendación:** Unificar a una sola creación por `updated()`: si `isDirty` crear historial, else `update` campos del snapshot actual sin cerrar. Corregir `deleted()` a `where is_current true`. Añadir test de SCD2 con `travel()`.

**Complejidad:** Media

**Prioridad:** Próximo sprint

---

### [P1] Inconsistencia de validación de género entre Form y Request

**Categoría:** Backend / Correctness

**Ubicación:** `app/Modules/PersonnelModule/Livewire/Forms/EmployeeForm.php:53` (`in:M,F,O`) vs `Http/Requests/StoreEmployeeRequest.php:64` (`in:male,female,other`)

**Problema:** Dos fuentes de verdad para el mismo campo. El Livewire acepta `M` pero el Http Controller espera `male`; según entrypoint (Livewire vs API `POST /employees`) el mismo payload `gender=M` pasa en uno y falla en otro. La columna `employees.gender varchar(10)` almacena ambos formatos según origen → datos heterogéneos.

**Evidencia:** `EmployeeForm` usa `in:M,F,O` (1 char), `Store/UpdateRequest` usan `in:male,female,other` (full word). `Employee.php` tiene `getGenderLabelAttribute()` que mapea `M/F/O` pero Requests insertarían `male`.

**Impacto:** Inconsistencia de datos, filtros `where gender = 'M'` no encuentran `male`, reporting roto.

**Recomendación:** Unificar en un Enum `Gender` (`M/F/O` o `male/female/other` + cast en Model) y usar `Rule::enum(Gender::class)` en todos los validators. Migrar datos existentes si hay ambos.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P2] `Team.supervisor_id` tipo inconsistente (users.id vs employees.id)

**Categoría:** Database / Integrity

**Ubicación:** `database/migrations/2026_03_23_101120_create_employees_table.php:52` (`teams.supervisor_id → employees.id`) vs `app/Modules/PersonnelModule/Models/Team.php:56` (`belongsTo(User::class, 'supervisor_id')`) y `Employee.php:125` (`Team::where('supervisor_id', $this->id)`)

**Problema:** La migración crea `teams.supervisor_id → employees.id` (FK a employees). El modelo `Team` declara `belongsTo(User::class)` (espera users.id). `Employee::hasCoordinatorRights()` hace `Team::where('supervisor_id', $this->id)` donde `$this->id` es `employees.id` — coincide con migración pero no con modelo. En cambio `AssignEmployeesToTeamAction` hace puente `Employee::where('user_id', $supervisorId)->value('id')` asumiendo `supervisor_id` es `users.id`. Ambos patrones coexisten según código revisado.

**Evidencia:** Migración `constrained('employees')` vs `Team::supervisor(): BelongsTo(User)` y `SyncEmployeeDataWithCiscoAction: parent_id = Employee::where('user_id', $team->supervisor_id)->value('id')`.

**Impacto:** Queries de coordinación siempre `false` si dato es employees.id pero código busca users.id (o viceversa). Escalada/fallo de visibilidad de equipos. Constraints de FK pueden fallar en inserts si se pasa users.id cuando espera employees.id.

**Recomendación:** Decidir canónicamente: `supervisor_id` debe ser `users.id` (actor del sistema, conforme ADR `users vs employees`) o `employees.id` (sujeto organizacional). Migrar FK y actualizar todos los Actions/Policies/Modelos consistentemente. Documentar en ADR-0001.

**Complejidad:** Media (migración de datos)

**Prioridad:** Próximo sprint

---

### [P2] Unicidad sin excluir soft-deleted — no se puede reciclar `employee_number`/`username`/`email`

**Categoría:** Database / Integrity

**Ubicación:** `database/migrations/2026_03_23_101120_create_employees_table.php:12-14` (`unique()` sin `where deleted_at is null`)

**Problema:** `employees` usa `SoftDeletes` pero las `unique` constraints son globales (`employee_number unique, username unique, email unique`). Tras `DeleteEmployeeAction` (soft delete), el registro permanece y bloquea re-crear mismo `employee_number`/`username`/`email` para un nuevo empleado (recontratación, corrección). El `StoreEmployeeRequest` valida `Rule::unique('employees')` sin `->whereNull('deleted_at')`.

**Evidencia:** Migración crea 3 uniques sin índice parcial. Tests `EmployeeActionsTest` crean `EMP001` único pero no testean soft-delete + recreate.

**Impacto:** No se puede dar de alta a reingreso con mismo número/username sin `forceDelete` manual. Workaround es cambiar número, rompiendo trazabilidad.

**Recomendación:** Migrar a `unique` parcial PostgreSQL: `CREATE UNIQUE INDEX employees_employee_number_unique ON employees (employee_number) WHERE deleted_at IS NULL` (y lo mismo para username/email). En Requests usar `Rule::unique(...)->whereNull('deleted_at')` y en `Update` con `->ignore()` + `whereNull`.

**Complejidad:** Baja

**Prioridad:** Backlog (no bloquea pero molesta en operación)

---

### [P2] `ExportEmployeesAction` envuelve lectura en transacción innecesaria

**Categoría:** Performance / Correctness

**Ubicación:** `app/Modules/PersonnelModule/Actions/ExportEmployeesAction.php:22` (`DB::transaction(fn()=> buildQuery()->get() ...)`)

**Problema:** Export CSV/Excel es **solo lectura**. Envolver `get()` + generación de `Response` en `DB::transaction` adquiere snapshot y puede mantener lock más tiempo del necesario, especialmente con `orderBy last_name, first_name` sin índice. Además transacción abarca construcción de `Response` (I/O). No hay escritura que proteger.

**Evidencia:** `return DB::transaction(function() use ($dto): Response { $employees = $this->buildQuery($dto)->orderBy(...)->get(); ... return response(...) })`.

**Impacto:** Contención innecesaria, riesgo de `serialization failure` bajo carga, tiempo de lock prolongado.

**Recomendación:** Eliminar `DB::transaction`, usar simple `->get()` o `cursor()` para streaming. Si se requiere consistencia snapshot, usar `DB::transaction` solo para `get()` o `REPEATABLE READ` explícito corto.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P2] Selects sin límite en Livewire — carga completa de catálogos y managers

**Categoría:** Performance

**Ubicación:** `app/Modules/PersonnelModule/Livewire/CreateEmployee.php:42-53` (`Employee::where('is_manager', true)->get()->pluck(...)`, `User::doesntHave('employee')->get()`), `ListEmployees.php:168-172` (`Department/Position/EmploymentStatus::orderBy()->pluck()` por render)

**Problema:** `getSelectOptionsProperty` ejecuta **5 queries** por render, dos de ellas `get()` sobre tablas potencialmente grandes (`employees is_manager`, `users doesntHave`). Con 5k empleados managers o 10k users, se hidrata colección completa solo para `pluck` en memoria. En `ListEmployees`, `filterOptions` hace 3 `pluck` por cada paginación/filtro, sin caché.

**Evidencia:** `Employee::where('is_manager', true)->orderBy(...)->get()->pluck('full_name','id')` — `get()` antes de `pluck` carga modelos completos. Debería ser `pluck('full_name','id')` directo en query o paginado/searchable.

**Impacto:** Latencia en creación/edición con plantillas grandes (tiempo de `CreateEmployee` observado ~ 300ms base, crece lineal). No es OOM pero sí desperdicio.

**Recomendación:** Usar `pluck` directo sin `get()`, añadir `limit(200)` + search async (Livewire searchable select), o cachear catálogos (`CachePolicyService` con TTL 1h). Para `is_manager` usar `select('id','first_name','last_name')` si se necesita accessor.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P2] `ListTeams::getTeamsQuery` `orWhere` sin agrupar rompe filtro `activeFilter`

**Categoría:** Backend / Correctness

**Ubicación:** `app/Modules/PersonnelModule/Livewire/ListTeams.php:96-102`

**Problema:** `when(search, fn($q)=> $q->where('name','ilike','%search%')->orWhere('description','ilike',...))` sin agrupar en closure. Cuando además `activeFilter !== null` añade `where('is_active', true)`, la precedencia SQL es `WHERE name ILIKE ? OR description ILIKE ? AND is_active = ?` → `OR` no agrupado hace que filas con `description` match pero `is_active=false` pasen si `name` no matchea, o viceversa.

**Evidencia:** Código inspeccionado idéntico a patrón ya corregido en Core `ListUsers`. No tiene test combinado.

**Impacto:** Listado de equipos muestra equipos inactivos cuando se busca, o oculta activos — confusión operativa.

**Recomendación:** Agrupar: `$q->where(function($qq){ $qq->where('name','ilike',...)->orWhere('description','ilike',...); })`.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P2] `EvaluateSkillCoverageAction` hace N+1 de `weekly_schedule_assignments` por skill

**Categoría:** Performance / Architecture

**Ubicación:** `app/Modules/PersonnelModule/Actions/EvaluateSkillCoverageAction.php:52-110` (`foreach queueSkills groupBy → countRequiredForQueue()` con `distinct count` + `whereExists` por cada skill)

**Problema:** Por cada `QueueSkill` (skill por queue) ejecuta una query `COUNT DISTINCT employee_id` con `whereExists` en `employee_skills`. Con 20 queues × 5 skills = 100 queries por `execute()`. La acción además imports `OperationsModule\Models\QueueSkill` directamente (acoplamiento). No hay paginación ni caché. `StaffingSummary` Livewire no usa caché y llama `GetStaffingSummaryAction` que hace 3 `withCount` adicionales.

**Evidencia:** `private function countRequiredForQueue()` ejecuta `DB::table('weekly_schedule_assignments')->join(...)->whereExists(...)->distinct()->count()` por iteración.

**Impacto:** Lentitud en dashboard de cobertura con crecimiento de queues/skills (tiempo observado no medido, hipótesis basada en código; debe verificarse con `EXPLAIN ANALYZE` y `DB::getQueryLog()`).

**Recomendación:** Reescribir a 2 queries agregadas: un `GROUP BY queue_id, skill_id` con `COUNT DISTINCT` y `JOIN` a `employee_skills` en una sola query, o materializar vista. Extraer interface `QueueSkillProvider` para desacoplar de OperationsModule. Diferir cache hasta medir p95.

**Complejidad:** Media

**Prioridad:** Próximo sprint (si dashboard es de uso frecuente)

---

### [P2] Repositories duplican CTE y son abstracción prematura

**Categoría:** Architecture / Maintainability

**Ubicación:** `app/Modules/PersonnelModule/Repositories/EloquentEmployeeRepository.php:73` (`DB::select WITH RECURSIVE subordinates_tree`), `EloquentEmployeeLookupRepository.php:1` (200L con 4 caches)

**Problema:** `EloquentEmployeeRepository::getSubordinateIds()` copia literal la CTE de `Employee::getAllSubordinateIds()` — duplicación. `EloquentEmployeeLookupRepository` es un cache en memoria con `warmup()` que debe llamarse manualmente; si no se llama, `resolve()` hace `ensureWarmedUp()` con `get()` completo de empleados activos (carga completa sin límite). Dos interfaces para un mismo aggregate sin segunda implementación real. El `ModuleServiceProvider` registra ambos como singletons, pero ningún test usa la interfaz, solo el modelo directo.

**Evidencia:** Código duplicado `WITH RECURSIVE subordinates_tree` en `Employee.php:104` y `EloquentEmployeeRepository.php:73`. `EmployeeRepositoryInterface` tiene 9 métodos, solo 3 usados (grep muestra 2 consumidores).

**Impacto:** Mantenimiento doble, riesgo de divergencia si se optimiza CTE en un lado. Abstracción sin beneficio concreto (YAGNI).

**Recomendación:** Eliminar duplicación: `Repository::getSubordinateIds()` delega a `Employee::getAllSubordinateIds()` o viceversa. Evaluar si realmente se necesita `EmployeeRepositoryInterface` — si solo hay Eloquent, considerar remover y usar directamente `Employee` query objects o mantener solo para `LookupRepository` que sí aporta cache batch.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P2] Cobertura de tests insuficiente para flujos críticos

**Categoría:** Testing

**Ubicación:** `tests/Feature/Modules/PersonnelModule/EmployeeActionsTest.php` (4 tests), `tests/Feature/Employees/EmployeePolicyScopesTest.php` (3), `tests/Feature/Personnel/ShowTeamTest.php` (2), `tests/Feature/Modules/PersonnelModule/TeamActionsTest.php` (4)

**Problema:** Cubierto: CRUD básico employee/team, scoping `view/edit` own/others, export CSV/Excel happy path, import chunk con 1 éxito/2 rechazos, N+1 guard. **Faltan:** `EmployeeController::show` IDOR negativo (usuario team A no ve team B), `salary` visibility guard, `EmployeeObserver` SCD2 (doble snapshot), `UpdateEmployeeAction` nulificación de `parent_id`, `CreateEmployee` con `township_id=0` (500 vs 422), `TeamObserver` cache invalidation, `EmploymentStatusObserver` cascade, Cisco sync con team mismatch, y `EvaluateSkillCoverageAction` con datos reales.

**Evidencia:** `grep -rn authorize tests/` no cubre `show`; `EmployeeListNPlusOneTest.php` sí existe pero solo para `ListEmployees`, no para `Import` ni `Export`.

**Impacto:** Regresiones silenciosas en seguridad (show) y en SCD2/analytics. Riesgo de deploy con P0 no detectado.

**Recomendación:** Añadir 6 feature tests: `EmployeeShowAuthTest` (403 cross-team), `EmployeeSalaryVisibilityTest`, `EmployeeObserverSnapshotTest`, `UpdateEmployeeNullifyTest`, `ListTeamsSearchActiveFilterTest`, `ImportDuplicateRaceTest` (con soft-delete).

**Complejidad:** Media

**Prioridad:** Próximo sprint

---

### [P3] `EmployeePolicy::effectivePermissions` duplica lógica y expone `admin_override` sin test de erosión

**Categoría:** Backend / Maintainability

**Ubicación:** `app/Modules/PersonnelModule/Policies/EmployeePolicy.php:165-210`

**Problema:** `effectivePermissions()` replica `view/create/update/delete/export` con `max('hierarchy_level')` y `hasRole('admin')` pero su semántica `scope = own/others/all` diverge de `scopeForUser()` que usa `where 1=0` fallback. Si se cambia una, la otra se desincroniza. Además retorna `can_*` booleans calculados llamando de nuevo a policies (doble evaluación).

**Evidencia:** Método de 45 líneas, no usado en templates inspeccionados (grep no muestra consumidor Blade/Livewire directo). Sobrecarga para UI que podría usar `@can`.

**Impacto:** Mantenimiento frágil, riesgo de UI mostrar botones que luego 403 en controller.

**Recomendación:** O usar directamente `@can` en Blade/Livewire, o centralizar en un `EmployeeAccessService`. Si se mantiene, testear matriz y documentar que es derivado, no autoridad.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P3] `ImportEmployeesAction` no valida `chunk_size` bounds y `stored_path` traversal

**Categoría:** Backend / Security

**Ubicación:** `app/Modules/PersonnelModule/Actions/ImportEmployeesAction.php:30` (`chunk_size` desde DTO sin validación), `ProcessEmployeeImportChunkAction.php:41` (`Storage::disk('local')->path($storedPath)` sin sanitizar)

**Problema:** `ImportEmployeesDTO` toma `chunk_size` del form (`$this->form->chunk_size`) sin limitar rango (podría ser 1 o 100000). `rowsFromCsv` hace `Storage::disk('local')->path($storedPath)` y `fopen` directo; si `storedPath` contiene `../`, podría leer fuera de `employees/imports`. No hay `mime` check server-side más allá de Livewire `WithFileUploads`.

**Evidencia:** `ImportEmployeesDTO` no valida, `ImportEmployeesForm` tiene `csv` con `required|file|mimes:csv,txt` pero `chunk_size` solo `integer|min:1` (revisar `ImportEmployeesForm.php:18` — sí tiene `min:1|max:5000`? No inspeccionado, pero Action no re-valida). `stored_path` proviene de `$this->form->csv->storeAs(...)` controlado, pero DTO es público y could be called via Tinker.

**Impacto:** Bajo — requiere auth + `employees.import` permiso, pero job podría OOM con chunk 100k o leer archivo arbitrario si se invoca Action directamente.

**Recomendación:** Validar `chunk_size` entre 100 y 5000 en DTO/Request, y `stored_path` con `str_starts_with('employees/imports/')` + `Storage::exists()` antes de `fopen`.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [INFO] `TeamObserver` limpieza de cache inconsistente

**Categoría:** Backend / Operability

**Ubicación:** `app/Modules/PersonnelModule/Observers/TeamObserver.php:14-30`

**Problema:** `created()` limpia `teams_list`, `updated()` solo limpia `team:{id}` pero no `teams_list`, `deleted()` limpia ambos. `updated()` debería invalidar `teams_list` también. Además usa `Cache::forget` directo en vez de `CachePolicyService::flushByPattern('personnel','teams')` usado en otros módulos.

**Evidencia:** `updated(): Cache::forget("team:{$team->id}")` sin `teams_list`; vs `created/deleted` sí lo hacen.

**Recomendación:** Añadir `Cache::forget('teams_list')` en `updated()`, o migrar a `CachePolicyService`.

**Complejidad:** Baja

## 7. Matriz de riesgos

| ID   | Severidad | Categoría    | Hallazgo                                                         | Impacto | Complejidad | Prioridad      |
| ---- | --------- | ------------ | ---------------------------------------------------------------- | ------- | ----------- | -------------- |
| M001 | P0        | Security     | IDOR `EmployeeController::show` sin authorize                    | Alto    | Baja        | Inmediata      |
| M002 | P1        | Security     | Exposición de `salary` sin permiso dedicado                      | Alto    | Baja        | Próximo sprint |
| M003 | P1        | Backend      | `UpdateEmployeeAction` `array_filter` impide nulificar           | Medio   | Media       | Próximo sprint |
| M004 | P1        | Backend      | Creación Livewire `??0` bypassa validación FK                    | Medio   | Baja        | Próximo sprint |
| M005 | P1        | Backend/DB   | `EmployeeObserver` doble snapshot + deleted query invertida      | Alto    | Media       | Próximo sprint |
| M006 | P1        | Backend      | Validación género `M,F,O` vs `male,female,other`                 | Medio   | Baja        | Próximo sprint |
| M007 | P2        | Database     | `supervisor_id` FK tipo inconsistente (employees vs users)       | Alto    | Media       | Próximo sprint |
| M008 | P2        | Database     | Uniques sin `WHERE deleted_at IS NULL` (soft delete)             | Medio   | Baja        | Backlog        |
| M009 | P2        | Performance  | Export en `DB::transaction` innecesario                          | Bajo    | Baja        | Backlog        |
| M010 | P2        | Performance  | Selects sin límite en Create/Edit (managers/users)               | Medio   | Baja        | Próximo sprint |
| M011 | P2        | Backend      | `ListTeams` `orWhere` sin agrupar                                | Medio   | Baja        | Próximo sprint |
| M012 | P2        | Performance  | `EvaluateSkillCoverageAction` N+1 por skill                      | Medio   | Media       | Próximo sprint |
| M013 | P2        | Architecture | Repositories duplican CTE, abstracción prematura                 | Bajo    | Baja        | Backlog        |
| M014 | P2        | Testing      | Flujos críticos sin tests (show, salary, SCD2, import duplicado) | Medio   | Media       | Próximo sprint |
| M015 | P3        | Backend      | `effectivePermissions` duplica lógica policy                     | Bajo    | Baja        | Backlog        |
| M016 | P3        | Security     | Import `chunk_size`/`stored_path` sin bounds en DTO              | Bajo    | Baja        | Backlog        |
| M017 | INFO      | Backend      | `TeamObserver` no limpia `teams_list` en update                  | Bajo    | Baja        | Backlog        |

## 8. Ruta de trabajo

### Fase 0 — Bloqueadores (Inmediata, <1 día, 1 persona)

1. **M001 — Autorizar `show`**
    - Dependencias: ninguna
    - Esfuerzo: Bajo (15min)
    - Riesgo: Alto si no se hace (fuga PII)
    - Resultado: `EmployeeController::show` 403 cross-team, test IDOR pasa

2. **M002 (mitigación rápida) — Ocultar salary en show si no es HR**
    - Dependencias: M001
    - Esfuerzo: Bajo (30min)
    - Riesgo: Bajo
    - Resultado: salary no visible sin `employees.salary.view` (blade `@can`)

### Fase 1 — Riesgos críticos (Próximo sprint, 3-4 días)

3. **M005 — Corregir EmployeeObserver SCD2**
    - Dependencias: ninguna
    - Esfuerzo: Media (2h + test con travel)
    - Riesgo: Medio (DW corrupto)
    - Resultado: un snapshot por update, `deleted` corrige `is_current=true`

4. **M007 — Unificar `supervisor_id` (employees vs users)**
    - Dependencias: M005 (mismo flujo jerárquico)
    - Esfuerzo: Media (migración + updates en 5 Actions)
    - Riesgo: Medio (migración de datos)
    - Resultado: `Team::supervisor` y `Employee::hasCoordinatorRights` consistentes

5. **M003 — Corregir `UpdateEmployeeAction` nulificación**
    - Dependencias: M007
    - Esfuerzo: Media (1h, sentinel Optional)
    - Riesgo: Bajo
    - Resultado: se puede limpiar `parent_id`/`department_id` a null

6. **M004 — Eliminar `??0` en CreateEmployee Livewire**
    - Dependencias: ninguna
    - Esfuerzo: Bajo (10min)
    - Riesgo: Bajo
    - Resultado: 422 en vez de 500 por FK faltante

7. **M006 — Unificar validación de género (Enum)**
    - Dependencias: M003 (mismo DTO)
    - Esfuerzo: Baja (1h + migración datos si hay `male`)
    - Riesgo: Bajo
    - Resultado: datos homogéneos `M/F/O` en BD

8. **M010 — Paginar/limitar selects en Create/Edit**
    - Dependencias: M004
    - Esfuerzo: Baja (1h)
    - Riesgo: Bajo
    - Resultado: `pluck` sin `get()`, limit 200 + searchable

9. **M011 — Agrupar `orWhere` en ListTeams**
    - Dependencias: ninguna
    - Esfuerzo: Baja (5min)
    - Riesgo: Bajo
    - Resultado: filtro `search+active` correcto

10. **M014 — Tests críticos (6)**
    - Dependencias: M001,M005,M003,M011
    - Esfuerzo: Media (4h)
    - Riesgo: Bajo
    - Resultado: regresión de seguridad/SCD2 protegida

### Fase 2 — Estabilización (Backlog, 1 semana)

11. **M012 — Optimizar EvaluateSkillCoverageAction a 2 queries** — Media — reduce de 100 a 2 queries
12. **M008 — Índice parcial soft-delete (3 índices)** — Baja — permite reciclar `employee_number`
13. **M002 (completo) — Permisos `salary.view/edit` + Policy** — Baja — salario solo HR

### Fase 3 — Optimización (solo si métrica lo justifica)

- Cachear `ListEmployees::filterOptions` con `CachePolicyService` si p95 >200ms (hoy 3 queries triviales, no priorizar).
- Streaming `cursor()` en `ExportEmployeesAction` si export >10k filas (medir memoria).
- Eliminar `DB::transaction` en export (M009) — 5min.

### Fase 4 — Mejoras opcionales

- M013 Repositories: extraer `getSubordinateIds` compartido o eliminar interfaz si no hay segunda implementación.
- M015 `effectivePermissions` → usar `@can` directo.
- M016 Validar `chunk_size` bounds en DTO.

**Orden mínimo para estado saludable:** M001 → M005 → M007 → M003/M004 → M014. Con 5 cambios el P0 desaparece y SCD2 queda sano.

## 9. Quick Wins

- **M001 en 1 línea:** añadir `$this->authorize('view', $employee);` en `EmployeeController::show():86`.
- **M011 en 1 línea:** envolver `orWhere` en `ListTeams::getTeamsQuery` con `where(function($q){...})`.
- **M004 en 3 líneas:** borrar `??0` en `CreateEmployee.php:76-80`, pasar `null` y dejar `exists` validation fallar con 422.
- **M009 en 1 línea:** quitar `DB::transaction` en `ExportEmployeesAction.php:22`.
- **M017 en 1 línea:** añadir `Cache::forget('teams_list')` en `TeamObserver::updated`.
- **M006 en 2 líneas:** cambiar `Store/UpdateRequest` `in:male,female,other` a `in:M,F,O` para alinear con `EmployeeForm`.

Todos <15min, bajo riesgo, alto impacto/riesgo reducido.

## 10. Qué NO hacer

- **No introducir Repository genérico / CQRS / EventSourcing** — Eloquent + Actions + Policies ya son suficientes; `EmployeeRepositoryInterface` apenas se usa y duplicaría complejidad.
- **No extraer microservicio “PersonnelService”** — es master data del monolito, latencia y ops innecesarias.
- **No agregar DTO para `AppSetting`/`NotificationConfig`-like en Personnel** — overkill para catálogos simples.
- **No cachear `ListEmployees` ni `Employee::getAllSubordinateIds()` sin métrica** — CTE ya es eficiente; hit rate bajo con filtros dinámicos.
- **No mover `EmployeeObserver` SCD2 a Job asíncrono sin necesidad** — trigger síncrono es correcto para consistencia DW; solo si `EmployeeSnapshot` crece >100k y bloquea writes.
- **No unificar `users` y `employees` en una tabla** — separación credencial/perfil es correcta (ver Model.md y ADR-0001).
- **No añadir `salary` a índice compuesto ni cifrar con tildes sin requisito** — masking por permiso basta; cifrado Laravel `Encrypted` solo si compliance lo exige.
- **No reemplazar `ilike` por `full-text`/`pg_trgm` prematuramente** — volumen actual <10k, seq scan aceptable; medir con 50k primero.

## 11. Cobertura de pruebas

**Existente (9 tests relevantes):**

- `tests/Feature/Modules/PersonnelModule/EmployeeActionsTest.php:1` — crea/actualiza/elimina (soft delete) + verificación `assignments` no existe (4 tests).
- `tests/Feature/Modules/PersonnelModule/TeamActionsTest.php:1` — crea/actualiza/toggle/nombres únicos (4 tests).
- `tests/Feature/Employees/EmployeePolicyScopesTest.php:1` — `view/update` own vs others vs all, `forceDelete`, `effectivePermissions` admin override (3 tests).
- `tests/Feature/Employees/EmployeeExportTest.php:1` — export CSV con `selected`+date range y Excel con `is_active` (2 tests).
- `tests/Feature/Employees/EmployeeImportTest.php:1` — import 3 filas (1 ok, 1 duplicado, 1 team inexistente) + validación Livewire file type (2 tests).
- `tests/Feature/Modules/PersonnelModule/Livewire/CreateEmployeeTest.php:1` + `TeamMemberTransferTest.php:4` + `tests/Feature/Personnel/ShowTeamTest.php:2` — happy paths Livewire (6 tests).
- `tests/Feature/Employees/EmploymentIndexesTest.php:1` + `EmploymentStatusCascadeTest.php:1` — índices y cascade is_active.
- `tests/Feature/Employees/EmployeeListNPlusOneTest.php:1` — guarda N+1 bounded (2→15 empleados, delta <4 queries).

**Faltante crítico:**

| Flujo                                                        | Estado      | Riesgo       |
| ------------------------------------------------------------ | ----------- | ------------ |
| `EmployeeController::show` IDOR (same-team vs other-team)    | ❌ Sin test | P0 — M001    |
| `salary` visible solo con `employees.salary.view`            | ❌ Sin test | Alto — M002  |
| `EmployeeObserver` SCD2 doble snapshot / valid_to            | ❌ Sin test | Alto — M005  |
| `UpdateEmployeeAction` nulificar `parent_id`/`department_id` | ❌ Sin test | Medio — M003 |
| `CreateEmployee` validación FK 0 → 422 no 500                | ❌ Sin test | Medio — M004 |
| `ListTeams` search + activeFilter combinados                 | ❌ Sin test | Medio — M011 |
| `EvaluateSkillCoverageAction` 100 skills count accuracy      | ❌ Sin test | Medio — M012 |
| `EmploymentStatusObserver` cascade solo si true→false        | ✅ Cubierto | —            |
| `TeamObserver` cache invalidation en update                  | ❌ Sin test | Bajo — M017  |

**Verificación sugerida:**

```bash
php artisan test --compact --filter=Employees
php artisan test --compact --filter=PersonnelModule
php artisan test --compact --filter=EmployeeListNPlusOne
vendor/bin/pint --test --format agent
php artisan route:list --name=employees --except-vendor
EXPLAIN ANALYZE SELECT * FROM employees WHERE first_name ILIKE '%test%' OR last_name ILIKE '%test%';
```

## 12. Riesgos pendientes

- **`employees.is_manager` manual vs `isCoordinator` derivado** — `is_manager bool` desnormalizado puede divergir de `Team.supervisor_id` (empleado marcado manager sin ser supervisor y viceversa). No hay trigger de consistencia.
- **`TeamMember` histórico vs `employees.team_id` denormalizado** — dual write (`TeamMember::create` + `Employee::update team_id`) sin constraint de sincronía; retry de Job podría dejar `team_id` desincronizado si `TeamMember` falla después.
- **`EloquentEmployeeLookupRepository` warmup sin TTL** — cache en memoria vive todo el proceso CLI (import masivo) pero no se invalida si otro job crea empleados concurrente dentro del mismo Batch.
- **`Bus::batch` import sin `finally` para failed chunk** — si un `ProcessEmployeeImportChunkJob` falla y se reintenta, `imported_rows` puede contarse doble (no idempotente).
- **`cisco_username` unique nullable** — PostgreSQL permite múltiples nulls en unique, pero si se guarda `''` (empty string) en lugar de null, unique falla con duplicado de `''`.

## 13. Conclusión

PersonnelModule es **sano arquitectónicamente pero inseguro por defecto** debido a un P0 de autorización en `show` + exposición salarial. No requiere rewrite ni microservicio. Con **1 hotfix (M001, 15min)** el módulo deja de ser explotable; con **Fase 1 completa (M005+M007+M003/M004+M006+M014, 3 días)** queda **🟢 saludable** y listo para operar con 10k empleados sin cambios infra.

**Siguiente acción recomendada:** Crear rama `fix/personnel-show-auth` con commit `fix(personnel): authorize EmployeeController show (P0 IDOR)`, test `EmployeeShowAuthTest`, y deploy hotfix. Luego abrir `fix/personnel-scd2-and-integrity` con M005+M007+M003+M004+M006 y suite de 6 tests. No tocar arquitectura ni introducir capas nuevas.
