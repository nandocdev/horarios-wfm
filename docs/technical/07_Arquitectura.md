# Arquitectura del Sistema — WFM Call Center CSS

**Proyecto:** Sistema de Gestión de Horarios (WFM) — Call Center CSS
**Versión:** v1.0
**Fecha:** Febrero 2026
**Stack:** PHP 8.3 · Laravel 12 · PostgreSQL 16
**Fase RUP:** Elaboración

---

## 1. Visión General

El sistema adopta una **arquitectura modular por dominio** sobre Laravel 12. Cada módulo es una unidad cohesiva que encapsula su propia lógica, modelos, rutas, vistas y proveedores. Los módulos no se comunican directamente entre sí a través de sus internos; lo hacen mediante interfaces bien definidas (Actions, Events o servicios compartidos en `app/Shared`).

El objetivo es lograr alta cohesión dentro del módulo y bajo acoplamiento entre módulos.

---

## 2. Estructura de Directorios

```
app/
├── Modules/
│   └── {NombreModulo}/
│       ├── Actions/
│       ├── DTOs/
│       ├── Events/
│       ├── Listeners/
│       ├── Models/
│       ├── Observers/
│       ├── Policies/
│       ├── Livewire/
│       ├── Http/
│       │   ├── Controllers/
│       │   └── Requests/
│       ├── Providers/
│       │   └── ModuleServiceProvider.php
│       ├── Resources/
│       │   └── Views/
│       └── Routes/
│           └── web.php
│
├── Shared/
│   ├── Contracts/
│   ├── DTOs/
│   ├── Exceptions/
│   ├── Traits/
│   └── Services/
│
config/
│   └── modules.php
│
resources/views/        ← layouts globales únicamente
```

---

## 3. Dominios y Módulos

```
app/Modules/

# Identity & Access
├── Auth/
├── User/
├── Role/

# Organization
├── Organization/
├── Geography/
├── Catalog/

# Workforce
├── Employee/
├── Team/
├── EmployeeWellness/

# Planning
├── Schedule/
├── WeeklyPlanning/
├── IntradayPlanning/

# Workflow
├── LeaveRequest/
├── ShiftSwap/
├── Attendance/

# Reporting
├── Reports/
└── Audit/
```

Los dominios son agrupaciones conceptuales, no directorios en disco. Un módulo pertenece a un único dominio.

---

## 4. Anatomía de un Módulo

### 4.1 `Actions/`
Contiene las acciones de negocio del módulo. Cada acción representa **una operación atómica** del sistema.

**Reglas:**
- Una clase por acción. Nombre en imperativo: `CreateLeaveRequest`, `ApproveShiftSwap`.
- Método público único: `handle(DTO $dto): Result`.
- No contiene lógica HTTP (sin `Request`, sin `Response`).
- No llama directamente a Actions de otros módulos. Si necesita lógica de otro dominio, lo hace a través de un contrato en `app/Shared/Contracts/`.

```php
// app/Modules/LeaveRequest/Actions/CreateLeaveRequestAction.php
final class CreateLeaveRequestAction
{
    public function handle(CreateLeaveRequestDTO $dto): LeaveRequest
    {
        // lógica pura de negocio
    }
}
```

---

### 4.2 `DTOs/`
Objetos de transferencia de datos entre capas (HTTP → Action, Action → Event).

**Reglas:**
- Clases `readonly` de PHP 8.2+.
- Sin lógica de negocio. Solo transporte de datos validados.
- Nombre descriptivo del contexto: `CreateLeaveRequestDTO`, `ApproveLeaveRequestDTO`.
- Se construyen desde el `FormRequest` correspondiente.

```php
// app/Modules/LeaveRequest/DTOs/CreateLeaveRequestDTO.php
final readonly class CreateLeaveRequestDTO
{
    public function __construct(
        public int    $employeeId,
        public string $type,
        public Carbon $startDatetime,
        public Carbon $endDatetime,
        public string $justification,
    ) {}
}
```

---

### 4.3 `Models/`
Modelos Eloquent del módulo. Cada modelo representa una entidad persistida en la base de datos.

**Reglas:**
- Un modelo por tabla de dominio. Un módulo es el **propietario** de sus modelos.
- Los modelos de otros módulos se referencian por su FQCN completo, nunca se reimportan ni se duplican.
- Sin lógica de negocio compleja. Los modelos exponen: relaciones, scopes, casts y accessors.
- Lógica de ciclo de vida (eventos de modelo) delegada al `Observer` correspondiente.
- Usar `SoftDeletes` en todas las entidades críticas según `RD-02`.

```php
// app/Modules/LeaveRequest/Models/LeaveRequest.php
class LeaveRequest extends Model
{
    use SoftDeletes;

    protected $casts = [
        'status'         => LeaveRequestStatus::class,
        'type'           => LeaveRequestType::class,
        'start_datetime' => 'datetime',
        'end_datetime'   => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Employee\Models\Employee::class);
    }
}
```

---

### 4.4 `Events/` y `Listeners/`
Comunicación desacoplada entre módulos mediante el sistema de eventos de Laravel.

**Reglas:**
- Los eventos se disparan desde Actions, nunca desde Controllers ni Observers.
- Un evento representa un hecho del dominio ocurrido: `LeaveRequestCreated`, `ShiftSwapApproved`.
- Los Listeners de un módulo solo escuchan eventos de su propio dominio o eventos del dominio `Shared`.
- Los Listeners de otros módulos que reaccionan a eventos de este módulo se registran en el `ModuleServiceProvider` del módulo **escucha**, no del módulo **emisor**.
- Los Listeners son responsables de efectos secundarios: notificaciones, auditoría, sincronización.

```php
// app/Modules/LeaveRequest/Events/LeaveRequestCreated.php
final class LeaveRequestCreated
{
    public function __construct(
        public readonly LeaveRequest $leaveRequest
    ) {}
}
```

---

### 4.5 `Observers/`
Observan el ciclo de vida del modelo Eloquent (`created`, `updated`, `deleted`).

**Reglas:**
- Responsabilidad única: registrar `audit_logs` automáticamente en entidades críticas.
- No ejecutan lógica de negocio. Para eso existen las Actions.
- No disparan Events. Esa responsabilidad es de las Actions.
- Se registran en el `ModuleServiceProvider` del módulo propietario del modelo.

```php
// app/Modules/LeaveRequest/Observers/LeaveRequestObserver.php
class LeaveRequestObserver
{
    public function updated(LeaveRequest $model): void
    {
        // registrar cambio en audit_logs
    }
}
```

---

### 4.6 `Policies/`
Controlan la autorización a nivel de entidad siguiendo el modelo de Laravel Policies.

**Reglas:**
- Una Policy por modelo: `LeaveRequestPolicy`, `ShiftSwapPolicy`.
- Validan dos dimensiones: **rol del usuario** y **alcance jerárquico** (`team_id` / `parent_id`).
- El método `before()` intercepta roles de sistema (Admin, WFM) con acceso transversal.
- Nunca retornan lógica de negocio, solo `true` / `false` / `Response::deny()`.
- Las Policies se registran en el `ModuleServiceProvider` del módulo propietario.

```php
// app/Modules/LeaveRequest/Policies/LeaveRequestPolicy.php
class LeaveRequestPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('admin')) return true;
        return null;
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->employee->team_id === $leaveRequest->employee->team_id
            && $user->hasRole('coordinator');
    }
}
```

---

### 4.7 `Http/Controllers/`
Punto de entrada HTTP del módulo. Orquestan el flujo: validan entrada → construyen DTO → llaman Action → retornan respuesta.

**Reglas:**
- Los Controllers no contienen lógica de negocio.
- Cada método del controller sigue el flujo: `FormRequest → DTO → Action → Response`.
- Thin controllers: máximo 10 líneas por método.
- Retornan vistas Blade o respuestas JSON según el contexto.

```php
// app/Modules/LeaveRequest/Http/Controllers/LeaveRequestController.php
class LeaveRequestController extends  App\Http\Controllers\Controller
{
    public function store(StoreLeaveRequestRequest $request, CreateLeaveRequestAction $action)
    {
        $dto    = CreateLeaveRequestDTO::fromRequest($request);
        $result = $action->handle($dto);

        return redirect()->route('leave-requests.show', $result);
    }
}
```

---

### 4.8 `Http/Requests/`
FormRequests de Laravel que validan y autorizan la entrada HTTP.

**Reglas:**
- Un FormRequest por operación: `StoreLeaveRequestRequest`, `ApproveLeaveRequestRequest`.
- El método `authorize()` delega a la Policy correspondiente.
- El método `rules()` valida estructura y tipos. La lógica de negocio (duplicados, solapamientos) se valida en la Action.
- Exponen un método `toDTO()` para construir el DTO sin lógica en el Controller.

---

### 4.9 `Providers/ModuleServiceProvider.php`
Punto de registro de todo lo que el módulo aporta al contenedor de Laravel.

**Reglas:**
- Cada módulo tiene exactamente un `ModuleServiceProvider`.
- Registra: rutas, vistas, Observers, Policies, Listeners y bindings del módulo.
- No registra servicios globales. Lo global va en `AppServiceProvider`.
- Todos los `ModuleServiceProvider` se cargan desde `config/modules.php`.

```php
// app/Modules/LeaveRequest/Providers/ModuleServiceProvider.php
class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'leave-request');

        Gate::policy(LeaveRequest::class, LeaveRequestPolicy::class);
        LeaveRequest::observe(LeaveRequestObserver::class);
    }
}
```

---

### 4.10 `Routes/web.php`
Rutas del módulo, protegidas por middleware de autenticación y rol.

**Reglas:**
- Todas las rutas del módulo llevan el middleware `auth` y `verified`.
- Los grupos de rutas usan prefijo de URL y nombre consistentes con el módulo.
- Las rutas de solo lectura no llevan middleware de rol. Las de escritura sí.
- No se definen rutas globales desde aquí. Las rutas de autenticación van en `Auth/Routes/web.php`.

```php
// app/Modules/LeaveRequest/Routes/web.php
Route::middleware(['auth'])->prefix('leave-requests')->name('leave-requests.')->group(function () {
    Route::get('/',        [LeaveRequestController::class, 'index'])->name('index');
    Route::post('/',       [LeaveRequestController::class, 'store'])->name('store');
    Route::get('/{id}',    [LeaveRequestController::class, 'show'])->name('show');
    Route::post('/{id}/approve', [LeaveRequestController::class, 'approve'])
        ->middleware('role:coordinator')
        ->name('approve');
});
```

---

### 4.11 `Resources/Views/`
Vistas Blade del módulo.

**Reglas:**
- Las vistas heredan del layout global en `resources/views/layouts/`.
- El namespace de vistas del módulo es el nombre del módulo en kebab-case: `@include('leave-request::partials.status')`.
- Sin lógica de negocio en Blade. Solo presentación y llamadas a helpers de formato.

---

## 5. Capa Compartida — `app/Shared/`

Para lógica que necesitan múltiples módulos sin crear dependencia directa entre ellos.

| Directorio    | Contenido                                                                                                      |
| ------------- | -------------------------------------------------------------------------------------------------------------- |
| `Contracts/`  | Interfaces que los módulos implementan o consumen. Ej: `HierarchyResolverContract`, `ScheduleResolverContract` |
| `DTOs/`       | DTOs de uso transversal. Ej: `PaginationDTO`, `DateRangeDTO`                                                   |
| `Exceptions/` | Excepciones base del sistema. Ej: `UnauthorizedHierarchyException`, `ConflictException`                        |
| `Traits/`     | Traits reutilizables. Ej: `Auditable`, `HasTeamScope`                                                          |
| `Services/`   | Servicios de dominio transversal. Ej: `HierarchyService`, `ScheduleResolverService`                            |

**Reglas:**
- Un módulo puede depender de `app/Shared/`. Nunca al revés.
- Dos módulos no se importan directamente entre sí. Toda comunicación pasa por `Shared/Contracts/` o por Events.
- `HierarchyService` y `ScheduleResolverService` viven en `Shared/Services/` por ser consumidos por múltiples módulos.

---

## 6. Reglas de Comunicación entre Módulos

```
┌─────────────┐        Event         ┌─────────────┐
│  LeaveReq   │ ──────────────────► │   Audit     │
└─────────────┘                      └─────────────┘

┌─────────────┐   Shared\Contract    ┌─────────────┐
│  Attendance │ ──────────────────► │  Employee   │
└─────────────┘                      └─────────────┘

       ✅ Permitido                        ❌ Prohibido
  Módulo → Shared\*                   Módulo → Módulo (directo)
  Módulo → Events                     Módulo → Models de otro módulo (instancia directa)
  Módulo → FQCN de modelo ajeno       Módulo → Actions de otro módulo
```

| Mecanismo                       | Cuándo usarlo                                                         |
| ------------------------------- | --------------------------------------------------------------------- |
| **Events / Listeners**          | Efectos secundarios tras una acción (notificar, auditar, sincronizar) |
| **Shared\Contracts**            | Cuando un módulo necesita datos de otro dominio de forma síncrona     |
| **FQCN en relaciones Eloquent** | Referenciar modelos de otro módulo solo en definición de relaciones   |

---

## 7. Registro de Módulos

Todos los módulos se declaran en `config/modules.php`. El `AppServiceProvider` itera este archivo para registrar cada `ModuleServiceProvider` automáticamente.

```php
// config/modules.php
return [
    // Identity & Access
    \App\Modules\Auth\Providers\ModuleServiceProvider::class,
    \App\Modules\User\Providers\ModuleServiceProvider::class,
    \App\Modules\Role\Providers\ModuleServiceProvider::class,

    // Organization
    \App\Modules\Organization\Providers\ModuleServiceProvider::class,
    \App\Modules\Geography\Providers\ModuleServiceProvider::class,
    \App\Modules\Catalog\Providers\ModuleServiceProvider::class,

    // Workforce
    \App\Modules\Employee\Providers\ModuleServiceProvider::class,
    \App\Modules\Team\Providers\ModuleServiceProvider::class,
    \App\Modules\EmployeeWellness\Providers\ModuleServiceProvider::class,

    // Planning
    \App\Modules\Schedule\Providers\ModuleServiceProvider::class,
    \App\Modules\WeeklyPlanning\Providers\ModuleServiceProvider::class,
    \App\Modules\IntradayPlanning\Providers\ModuleServiceProvider::class,

    // Workflow
    \App\Modules\LeaveRequest\Providers\ModuleServiceProvider::class,
    \App\Modules\ShiftSwap\Providers\ModuleServiceProvider::class,
    \App\Modules\Attendance\Providers\ModuleServiceProvider::class,

    // Reporting
    \App\Modules\Reports\Providers\ModuleServiceProvider::class,
    \App\Modules\Audit\Providers\ModuleServiceProvider::class,
];
```

---

## 8. Convenciones de Nomenclatura

| Artefacto         | Convención                  | Ejemplo                        |
| ----------------- | --------------------------- | ------------------------------ |
| Módulo            | PascalCase singular         | `LeaveRequest`, `ShiftSwap`    |
| Action            | Verbo + Entidad + `Action`  | `CreateLeaveRequestAction`     |
| DTO               | Verbo + Entidad + `DTO`     | `CreateLeaveRequestDTO`        |
| Event             | Entidad + Participio pasado | `LeaveRequestCreated`          |
| Listener          | Verbo + propósito           | `SendLeaveRequestNotification` |
| Observer          | Entidad + `Observer`        | `LeaveRequestObserver`         |
| Policy            | Entidad + `Policy`          | `LeaveRequestPolicy`           |
| Controller        | Entidad + `Controller`      | `LeaveRequestController`       |
| FormRequest       | Verbo + Entidad + `Request` | `StoreLeaveRequestRequest`     |
| Vista (namespace) | kebab-case del módulo       | `leave-request::index`         |

---

## 9. Restricciones Absolutas

1. **Ningún módulo importa clases internas de otro módulo** salvo la referencia FQCN en relaciones Eloquent.
2. **Las Actions no dependen del ciclo HTTP.** No reciben `Request` ni retornan `Response`.
3. **Los modelos no tienen lógica de negocio.** Relaciones, scopes, casts y accessors únicamente.
4. **Los Controllers son thin.** Orquestan, no deciden.
5. **Los registros de `audit_logs` son inmutables.** El módulo `Audit` no expone métodos `update` ni `delete`.
6. **`SoftDeletes` es obligatorio** en todas las entidades críticas definidas en `RD-02`.
7. **Las rutas de escritura siempre llevan middleware de rol.** Ninguna acción sensible es accesible sin verificación explícita.
8. **Un módulo tiene un único `ModuleServiceProvider`.** No se crean proveedores adicionales por módulo.

---

*Documento de arquitectura técnica — WFM CSS v1.0. Sujeto a revisión ante cambios estructurales del sistema.*
