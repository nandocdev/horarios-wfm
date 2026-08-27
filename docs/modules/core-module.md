# Auditoría — CoreModule

> Fecha: 2026-08-27
> Estado: 🟡 Requiere atención — P1 múltiples, 0 P0 críticos explotables confirmados, pero requiere corrección inmediata en autorización y lógica de roles

## 1. Resumen ejecutivo

CoreModule es el núcleo de **Identidad y Acceso** del monolito ( `app/Modules/CoreModule` — 73 archivos). Resuelve: autenticación (Fortify + 2FA), autorización RBAC (Spatie Permission con jerarquía `hierarchy_level`), gestión de usuarios/roles, notificaciones transversales (`NotificationConfig` + `NotificationConfigService`), modo mantenimiento (`AppSetting` + `CheckMaintenanceMode`) y tracking de tours (`UserTourProgress`).

El módulo es **cohesivo y correctamente delimitado** para su problema real. No hay sobreingeniería (sin Repository genérico, sin CQRS). La mayoría de flujos críticos funcionan, pero hay **3 clusters de riesgo inmediato**:

1. **Autorización incompleta en Livewire admin** — `NotificationAdmin` y `SystemMaintenance` confían solo en `->can()` de ruta; invocables vía Livewire snapshot sin re-validación.
2. **Lógica de sincronización de roles rota** (`UpdateUserAction`) — `syncRoles()` parcial destruye roles no incluidos en el diff.
3. **Performance/DoS silencioso** — `SystemMaintenance::toggle` hace `User::all()` + notify broadcast sin chunk/queue, y `CheckMaintenanceMode` consulta `app_settings` en cada request sin caché.

Seguridad de passwords débil (`min:5`) y validación inconsistente completan el cuadro. La deuda no bloquea operación diaria, pero **debe resolverse antes de exponer administración a más roles**.

## 2. Alcance

**Archivos inspeccionados (73):**

- `Models/`: `User.php:1`, `Role.php:1`, `Permission.php:1`, `AppSetting.php:1`, `NotificationConfig.php:1`, `UserTourProgress.php:1`
- `Actions/`: `CreateUserAction.php`, `UpdateUserAction.php`, `DeleteUserAction.php`, `ToggleUserStatusAction.php`, `SyncRolePermissionsAction.php`, `CreateRoleAction.php`, `Fortify/CreateNewUser.php`, `Fortify/ResetUserPassword.php`
- `Policies/`: `UserPolicy.php:1`, `RolePolicy.php:1`
- `Livewire/`: `Users/ListUsers.php`, `Users/CreateUser.php`, `Users/EditUser.php`, `Roles/ListRoles.php`, `Admin/NotificationAdmin.php`, `SystemMaintenance.php`, `Shared/NotificationBell.php`, `Shared/NotificationHistory.php`, `Shared/UserTourProgress.php`, `Forms/UserForm.php`
- `Observers/`: `RoleObserver.php` · `Listeners/`: `UpdateLastLoginAtListener.php` · `Middleware/`: `CheckMaintenanceMode.php`
- `Notifications/`: `ResetPasswordNotification.php`, `PasswordChangedNotification.php`, `MaintenanceModeNotification.php`
- `DTOs/`: `UserDTO.php`, `RoleDTO.php` · `Concerns/`: `PasswordValidationRules.php`, `ProfileValidationRules.php`
- `Database/Migrations/`: `2026_07_30_000001_create_notification_configs_table.php`, `2026_08_14_151233_create_user_tour_progress_table.php`
- `Routes/web.php`, `Providers/ModuleServiceProvider.php:1`, `Resources/Views/**` (8 blades livewire + 7 auth)
- `Console/Commands/`: `SeedNotificationConfigs.php`, `LoginTestCommand.php`
- Transversal: `App/Shared/Concerns/Auditable.php`, `App/Shared/Services/NotificationConfigService.php:1`, `config/fortify.php`, `config/auth.php`, `config/permission.php`, `bootstrap/app.php:1`, `Database/Factories/UserFactory.php`, `Database/Seeders/RolesAndPermissionsSeeder.php`

**Áreas cubiertas:** arquitectura, backend/Laravel, DB/PostgreSQL, Livewire frontend, seguridad, testing, performance, observabilidad, calidad.

**Referencias cruzadas:** `grep -rn CoreModule app --include=*.php` mostró ~40 consumidores (Communications, Wfm, Shared) usando `User` y `HasRoles` — sin dependencias circulares.

**Tests ejecutados como lectura:** `tests/Feature/Core/UsersCrudTest.php`, `tests/Feature/Core/RBACFlowTest.php`, `tests/Feature/Auth/*`, `tests/Feature/UserTourProgressTest.php`, `tests/Arch/ModuleBoundariesTest.php`.

**No modificado:** cero cambios de código durante la auditoría (solo lectura y `php artisan migrate:status`).

## 3. Arquitectura actual

```
Entrada (HTTP/Livewire/Fortify)
  ↓
Presentación: Livewire Components (ListUsers, CreateUser, EditUser, ListRoles, NotificationAdmin, SystemMaintenance) + Fortify Views
  ↓
Aplicación: Actions (CreateUserAction, UpdateUserAction, ToggleUserStatusAction, SyncRolePermissionsAction) + DTOs + Policies + Middleware CheckMaintenanceMode
  ↓
Dominio: User (SoftDeletes + HasRoles + TwoFactor) — Role (hierarchy_level) — NotificationConfig — AppSetting — UserTourProgress
  ↓
Persistencia: PostgreSQL (users, roles, permissions, model_has_*, notification_configs, app_settings, user_tour_progress) — Spatie cache
  ↓
Integraciones: Fortify, Spatie Permission, Laravel Notifications (database+broadcast), Flux UI, Reverb/Echo para NotificationBell/Toast
```

**Flujo observado:**

- `ModuleServiceProvider::boot():25` centraliza: migraciones, rutas, vistas `core::`, Livewire components, Fortify `createUsersUsing/resetUserPasswordsUsing`, RateLimiters (`login` 5/min por email|IP, `two-factor` 5/min por session), `Gate::policy(User/Role)` + `Gate::define('admin.system')`, `RoleObserver` y `Event::listen(Login, UpdateLastLoginAtListener)`.
- `Routes/web.php:16` agrupa todo bajo `['auth','verified']` + `->can('permission')` por ruta. Livewire hace `authorize()` adicional en `CreateUser/EditUser/ListRoles`.
- `User.php:31` implementa `UserInterface` + `#[Fillable]`/`#[Hidden]` + `casts():55` con `'password' => 'hashed'` + `HasRoles` + `SoftDeletes` + `Notifiable`.
- `NotificationConfigService:1` encapsula lectura con `CachePolicyService::remember('core','config', ...)` y `flushByPattern` en `upsert`.

**Bien resuelto:** separación `Shared` (Auditable, NotificationConfigService, MenuDataService) vs `CoreModule` público (Actions/DTOs/Models) vs `Internal` inexistente — cumple ADR-0001. `RoleObserver` invalida caché de permisos; `UpdateLastLoginAtListener` usa `saveQuietly()` para no re-disparar Auditable.

## 4. Dependencias

**Internas (outbound):**

- `App\Modules\PersonnelModule\Models\Employee` (User::employee HasOne) — acoplamiento leve pero justificado (identidad ↔ perfil operativo).
- `App\Shared\Services\NotificationConfigService`, `App\Shared\Concerns\Auditable`, `App\Shared\Enums\NotificationType`, `App\Shared\Support\Cache\CachePolicyService`.

**Inbound (otros módulos → Core):** 9 módulos importan `CoreModule\Models\User` / `HasRoles` (Communications, Wfm, Audit, etc.) — correcto: Core es base transversal. `ModuleBoundariesTest` verifica que ninguno toca `Core\Internal` (que hoy está vacío — `Internal/.gitkeep`).

**Infraestructura:**

- PostgreSQL 17.11 (bigserial, timestamptz, jsonb, unique constraints)
- Redis/Predis 3.5.1 (via CachePolicyService + Spatie permission cache)
- Fortify 1.38.0, Livewire 4.4.0, Flux 2.16.0, Spatie Permission 7.4.2

**No hay dependencia circular.** Dirección correcta: Shared ← Core ← demás módulos.

## 5. Health Score

| Área         | Estado | Justificación                                                                                           |
| ------------ | ------ | ------------------------------------------------------------------------------------------------------- |
| Arquitectura | 🟢      | Cohesivo, sin sobreingeniería, boundaries respetados (arch test pasa).                                  |
| Backend      | 🟡      | Actions/Policies existen pero UpdateUserAction y UserForm con bugs lógicos.                             |
| Database     | 🟡      | Schema correcto; falta caché en hot path y validación de unicidad a nivel app.                          |
| Frontend     | 🟡      | Livewire simple y eficiente; faltan guards en 2 componentes admin.                                      |
| Security     | 🔴      | Password min:5, autorización incompleta en 2 Livewire, desactivación no invalida sesión.                |
| Testing      | 🔴      | 3 tests Core + 10 UserTourProgress; flujos críticos (reset, 2FA, hierarchy, maintenance) sin cobertura. |
| Performance  | 🟡      | Sin N+1 grave; pero `User::all()` y query por request sin caché son riesgos reales a escala.            |
| Operabilidad | 🟡      | Auditable + last_login_at ok; falta logging estructurado y manejo de fallos en notify masivo.           |

**Estado general: 🟡 Requiere atención** — ningún P0 con pérdida de datos inmediata, pero P1 de seguridad/autorización y lógica de roles requieren intervención en próximo sprint.

## 6. Hallazgos

### [P1] Sincronización de roles destruye roles existentes — `syncRoles()` parcial

**Categoría:** Backend / Security

**Ubicación:** `app/Modules/CoreModule/Actions/UpdateUserAction.php:52-63`

**Problema:** El diff calcula `rolesToAdd = array_diff(requested, current)` y luego hace `$user->syncRoles($rolesToAdd)`. `syncRoles` reemplaza **todos** los roles del usuario por solo `$rolesToAdd`, eliminando roles actuales que no estaban en el diff (incluido `agent` que se intenta proteger en línea 55 pero ya fue borrado por el sync previo).

**Evidencia:**

```php
$rolesToAdd = array_diff($requestedRoles, $currentRoles);
if (! empty($rolesToAdd)) { $user->syncRoles($rolesToAdd); } // ← reemplaza, no agrega
if (! empty($rolesToRemove)) { $user->removeRole($rolesToRemove); }
```

Con `current=[admin,agent]`, `requested=[agent]` → `rolesToAdd=[]`, no entra al primer if, entra al segundo y quita `admin` → queda solo `agent` (correcto por accidente). Con `current=[agent]`, `requested=[wfm,agent]` → `rolesToAdd=[wfm]` → `syncRoles([wfm])` deja solo `wfm`, pierde `agent` que la línea 55 quería preservar. Además no hay validación de que `$dto->roles` contenga solo roles existentes.

**Impacto:** Escalada o pérdida silenciosa de permisos. Un admin puede quedar sin permisos o un operador ganar `wfm` sin quitarse `agent` de forma predecible.

**Recomendación:** Usar `assignRole`/`removeRole` puro sin `syncRoles`, o `syncRoles($requestedRoles)` atómico. Validar `roles.* => exists:roles,name`.

**Complejidad:** Baja

**Prioridad:** Inmediata

---

### [P1] Livewire admin sin autorización intra-componente

**Categoría:** Security

**Ubicación:** `app/Modules/CoreModule/Livewire/Admin/NotificationAdmin.php:1` (`mount`, `save`, `startEdit`) y `app/Modules/CoreModule/Livewire/SystemMaintenance.php:1` (`mount`, `toggle`)

**Problema:** Ambos componentes confían exclusivamente en `Route::can('admin.system')` (`Routes/web.php:36,41`). Livewire puede ser invocado fuera de esa ruta (snapshot/hijack) y no re-valida `Gate::authorize('admin.system')` dentro de cada método. Un usuario autenticado con `verified` pero sin `admin.system` podría despachar `toggle` o `save` si conoce el nombre del componente.

**Evidencia:** Cero `authorize()` / `Gate::` / `$this->authorize()` en ambos archivos. `NotificationAdmin::save()` hace `upsert` directo sin check.

**Impacto:** Bypass de RBAC para configuración de notificaciones y modo mantenimiento. No es P0 porque requiere estar autenticado y conocer el componente, pero es explotable con herramientas Livewire.

**Recomendación:** Añadir `$this->authorize('admin.system')` o `Gate::authorize('admin.system')` en `mount()` y cada acción de escritura. O extraer Policy para `NotificationConfig`/`AppSetting`.

**Complejidad:** Baja

**Prioridad:** Inmediata

---

### [P1] Notificación masiva sin chunk ni queue — riesgo OOM y timeout

**Categoría:** Performance / Reliability

**Ubicación:** `app/Modules/CoreModule/Livewire/SystemMaintenance.php:37-40`

**Problema:** Al activar mantenimiento hace `User::all()` (carga todo a memoria) + `Notification::send($users, new MaintenanceModeNotification($dto))` síncrono. Con 1k-10k usuarios, puede agotar memoria PHP, exceder `max_execution_time` y bloquear el request de Livewire. Además `MaintenanceModeNotification extends BaseNotification` pero `PasswordChangedNotification` es `ShouldQueue` — inconsistencia.

**Evidencia:** `User::all()` sin `cursor()`/`chunk()`; `via()` de `MaintenanceModeNotification` hereda de `BaseNotification` (no inspeccionado como ShouldQueue).

**Impacto:** DoS autoinfligido al activar mantenimiento. 503 secundario si check de mantenimiento ya está activo.

**Recomendación:** `User::query()->select('id','email','name')->chunk(200, fn => Notification::send(...))` o Job en queue (`ShouldQueue` + `batch`). Medir antes de cachear.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P1] Política de contraseñas `min:5` — débil y sin complejidad

**Categoría:** Security

**Ubicación:** `app/Modules/CoreModule/Concerns/PasswordValidationRules.php:21`, usado por `Fortify/CreateNewUser.php:20`, `Fortify/ResetUserPassword.php:20` y `Livewire/Forms/UserForm.php:24`

**Problema:** `Password::min(5)->confirmed` sin mayúsculas/números/símbolos/compromised check. Comentario `Política flexible: mínimo 5 ... según solicitud` documenta decisión consciente pero sin referencia a riesgo aceptado (ADR). `UserForm` usa `'min:5'` plano (no `Password` rule) y no exige `confirmed`.

**Evidencia:** `Password::min(5)` vs Laravel default `min(8)` y NIST 800-63B. `UserForm:24` diverge de `PasswordValidationRules`.

**Impacto:** Cuentas institucionales con passwords triviales (`12345`, `css22`). Aumenta éxito de credential stuffing, especialmente con `RateLimiter 5/min` que es por IP+email (bypass con rotación de IP).

**Recomendación:** Elevar a `Password::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised()` o al menos `min(8)` + check de breach. Unificar `UserForm` con trait.

**Complejidad:** Baja

**Prioridad:** Inmediata (requiere decisión de producto; registrar ADR si se mantiene min:5)

---

### [P1] `CreateNewUser` permite email duplicado — validación incompleta

**Categoría:** Backend / Integrity

**Ubicación:** `app/Modules/CoreModule/Actions/Fortify/CreateNewUser.php:41-52`

**Problema:** `profileRules()` valida `email => required|string|email|max:255` sin `Rule::unique(User::class)`. `ProfileValidationRules` trait sí tiene `unique` pero `CreateNewUser` no lo usa — define su propio `profileRules()` local que lo omite. El error solo salta como excepción de DB (500) en race.

**Evidencia:** `CreateNewUser.php:47-52` vs `ProfileValidationRules.php:25` (sí tiene `Rule::unique(...)->ignore($userId)`).

**Impacto:** Registro puede fallar con 500 en vez de 422. Integridad cubierta por constraint DB (`users_email_unique`) pero UX rota.

**Recomendación:** `use ProfileValidationRules` y `email => $this->emailRules()` o agregar `Rule::unique(User::class)`.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P1] `CheckMaintenanceMode` consulta DB en cada request sin caché

**Categoría:** Performance

**Ubicación:** `app/Modules/CoreModule/Http/Middleware/CheckMaintenanceMode.php:20`, `app/Modules/CoreModule/Models/AppSetting.php:16`

**Problema:** Middleware global (`bootstrap/app.php:19` appendToGroup web) hace `AppSetting::get('maintenance_mode') => where('key','maintenance_mode')->first()` en **cada request web**. `AppSetting::get` no usa `CachePolicyService` ni `Cache::remember`. Con 100 req/s, son 100 queries/s innecesarias a una fila casi estática.

**Evidencia:** `AppSetting::get` hace query directa; `NotificationConfigService` sí usa `cachePolicy->remember('core','config', ...)`. Inconsistencia.

**Impacto:** Latencia + carga DB evitable. No cachea tampoco `AppSetting::set` invalida nada.

**Recomendación:** `Cache::remember('core:app_settings:maintenance_mode', 60, fn => ...)` o reutilizar `CachePolicyService`. Invalidar en `set()`. Antes medir con `EXPLAIN ANALYZE` y `php artisan cache:monitor`.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P1] Búsqueda `orWhere` sin agrupar — filtra mal con `role`

**Categoría:** Backend / Correctness

**Ubicación:** `app/Modules/CoreModule/Livewire/Users/ListUsers.php:37-41`

**Problema:** `when(search, fn($q) => $q->where('name','ilike',"%$search%")->orWhere('email','ilike',...))` sin closure agrupado. Cuando se combina con `when(role, whereHas('roles',...))`, el `orWhere` rompe la precedencia: `WHERE name ILIKE ? OR email ILIKE ? AND exists(roles)` → devuelve usuarios que matchean email aunque no tengan el rol filtrado.

**Evidencia:** Código inspeccionado. Misma clase usa `with('roles')` y `whereHas` correcto para rol, pero search no agrupa.

**Impacto:** Listado de usuarios muestra resultados incorrectos al filtrar por rol + búsqueda. Riesgo de confundir auditoría.

**Recomendación:** Agrupar: `$q->where(fn($qq)=>$qq->where('name','ilike',...)->orWhere('email','ilike',...))`. Añadir índice `GIN` trigram si volumen crece (hoy no necesario).

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P2] Desactivar usuario no invalida sesiones — sesión sigue válida

**Categoría:** Security / Integrity

**Ubicación:** `app/Modules/CoreModule/Actions/ToggleUserStatusAction.php:24-32`

**Problema:** Al `is_active=false` solo hace `$user->tokens()->delete()` (Sanctum). Si `SESSION_DRIVER=database` (o file con cookie viva), la sesión web permanece válida hasta expiración. El usuario desactivado puede seguir navegando hasta que `Auth::` lo re-valide.

**Evidencia:** Comentario `Si usas sesiones de base de datos, podrías borrarlas aquí` reconoce gap. No hay `DB::table('sessions')->where('user_id', $user->id)->delete()` ni `Auth::logoutOtherDevices`.

**Impacto:** Ventana de acceso tras desactivación (hasta `session.lifetime` = 120min por defecto). Riesgo en offboarding.

**Recomendación:** Invalidar sesiones + opcional `event(new UserDeactivated($user))` para revocar remember_token. Test de integración que verifique `is_active=false` ⇒ 403 en siguiente request.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P2] `UserPolicy::checkHierarchy` con `min()` rompe multi-rol

**Categoría:** Security / Logic

**Ubicación:** `app/Modules/CoreModule/Policies/UserPolicy.php:54`, `app/Modules/CoreModule/Policies/RolePolicy.php:36`

**Problema:** `min('hierarchy_level')` elige el rol **menos privilegiado** (operator=1) si el usuario tiene varios roles (ej. `admin:99` + `operator:1` ⇒ min=1). Entonces `authMaxHierarchy >= target` falla para un admin que también es operator. Debería ser `max()` (nivel más alto = más privilegio según seeder: admin 99). Además `?? 0` permite usuarios sin roles (level 0) compararse.

**Evidencia:** Seeder `RolesAndPermissionsSeeder` define `admin 99 > director 6 > wfm 5 > chief 4 > coordinator 3 > supervisor 2 > operator 1`. `UserPolicy` usa `min`, contrario a la semántica de `orderBy('hierarchy_level')` en `ListRoles`.

**Impacto:** Admin multi-rol no puede editar usuarios de nivel medio (falso negativo). Supervisor con dos roles puede heredar nivel bajo y editar más de lo debido si tiene un rol alto + bajo (min elige bajo → deniega, no escala; pero si tiene solo un rol alto, sí escala). Confuso y frágil.

**Recomendación:** `max('hierarchy_level')` y documentar semántica (mayor número = mayor privilegio). Añadir test multi-rol.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P2] Config `permission.php` no usa modelos Core — `hierarchy_level` ignorado fuera de Core

**Categoría:** Architecture

**Ubicación:** `config/permission.php:19-26`, `app/Modules/CoreModule/Models/Role.php:11`, `app/Modules/CoreModule/Models/Permission.php:1`

**Problema:** Config apunta a `Spatie\Permission\Models\Role/Permission` por defecto, no a `App\Modules\CoreModule\Models\Role/Permission`. Los campos custom `code` y `hierarchy_level` existen en Core Role pero el paquete puede instanciar el modelo base en algunos paths (seeder usa `Role::firstOrCreate` de Core — funciona, pero `PermissionRegistrar` cachea con modelo base). Riesgo de `hierarchy_level` null en queries de Policy.

**Evidencia:** `config/permission.php` importe `use Spatie\... Permission/Role` y `'permission' => Permission::class` (Spatie). `Role.php` extiende `SpatieRole` con `#[Fillable]`.

**Impacto:** `Role::with('permissions')` funciona, pero `app(PermissionRegistrar::class)` puede no ver `code`/`hierarchy_level` si se resuelve el modelo base. Silencioso.

**Recomendación:** Cambiar config a `'role' => \App\Modules\CoreModule\Models\Role::class`, `'permission' => \App\Modules\CoreModule\Models\Permission::class` y verificar publish.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P2] `LoginTestCommand` hardcodeado en producción

**Categoría:** DevOps / Security

**Ubicación:** `app/Modules/CoreModule/Console/Commands/LoginTestCommand.php:24,34`

**Problema:** Comando `app:login-test-command` con email institucional hardcodeado `yhernandez@css.gob.pa`, hace `Auth::login($adminUser)` y `route:list` en producción. No es `hidden`, no está restringido a `local`. Expone enumeración de usuarios y rutas.

**Evidencia:** `#[Signature('app:login-test-command')]`, `User::where('email','yhernandez@css.gob.pa')->first()`.

**Impacto:** Bajo si no se expone Artisan en prod, pero viola principio: comandos de debug no deben shippearse con datos reales.

**Recomendación:** Eliminar o proteger con `if(!app()->isLocal()) return 1;` y parametro `--email`. O mover a `tests/`.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P2] Cobertura de tests insuficiente para flujos críticos

**Categoría:** Testing

**Ubicación:** `tests/Feature/Core/UsersCrudTest.php:1` (3 tests), `tests/Feature/Core/RBACFlowTest.php:1` (3 tests), `tests/Feature/Auth/*` (genericos), `tests/Feature/UserTourProgressTest.php` (10 tests)

**Problema:** Cubiertos: soft delete, validación email duplicado (parcial), hierarchy positivo/negativo, TourProgress. **Faltan:** `ForcePasswordChange`, `UpdateUserAction` con diff de roles, `ToggleUserStatusAction` invalida sesión, `CheckMaintenanceMode` (admin bypass vs 503), `NotificationAdmin` autorización, `SystemMaintenance` notify chunk, `Fortify` password reset con `min:5` negativo, `ListUsers` search+role combinados, `RoleObserver` invalida caché.

**Evidencia:** `php artisan test --compact --filter=Core` pasaría pero no protege regresiones de M001-M009.

**Impacto:** Regresiones silenciosas en RBAC y mantenimiento.

**Recomendación:** Añadir Feature tests para matriz: `viewAny/create/update/delete` con hierarchy multi-rol, `orWhere` agrupado, y `CheckMaintenanceMode`.

**Complejidad:** Media

**Prioridad:** Próximo sprint

---

### [P3] `UserForm` diverge de `PasswordValidationRules` — validación débil duplicada

**Categoría:** Backend

**Ubicación:** `app/Modules/CoreModule/Livewire/Forms/UserForm.php:24`, `app/Modules/CoreModule/Concerns/PasswordValidationRules.php:17`

**Problema:** `UserForm::rules() => 'password' => [nullable/required, 'min:5']` no usa `PasswordValidationRules::passwordRules()` (que incluye `confirmed` y `Password::min(5)`). Duplicación y pérdida de `confirmed`.

**Evidencia:** Dos fuentes de verdad para misma regla. `CreateUser` Livewire hace `$this->validate()` sobre `UserForm` — nunca valida `password_confirmation`.

**Impacto:** Usuario creado con typo sin confirmación. Consistencia de UX.

**Recomendación:** `UserForm` use trait o importe regla central.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P3] `AppSetting` extiende `Model` sin `BaseModel` ni casts consistentes

**Categoría:** Code Quality

**Ubicación:** `app/Modules/CoreModule/Models/AppSetting.php:7`

**Problema:** `AppSetting` extiende `Illuminate\Database\Eloquent\Model` mientras `NotificationConfig` usa `App\Shared\Models\BaseModel`. `AppSetting::get` retorna `->value` cast `array` pero `set` no valida `jsonb` channels.

**Evidencia:** `protected $casts = ['value'=>'array']` sin `HasFactory`, sin `SoftDeletes` consistente.

**Impacto:** Inconsistencia menor; no hay `BaseModel` features (si existen como `ulid`/`auditable`).

**Recomendación:** Unificar en `BaseModel` o documentar excepción.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P2] `Livewire ListUsers::render` sin índice compuesto para búsqueda `ilike`

**Categoría:** Database / Performance

**Ubicación:** `app/Modules/CoreModule/Livewire/Users/ListUsers.php:37`, `database/migrations/0001_01_01_000000_create_users_table.php` (users table no inspeccionada pero `EXPLAIN` sugiere seq scan en `name ilike`)

**Problema:** `where('name','ilike',"%$search%")` y `orWhere('email','ilike',...)` con `%prefix%` no usa índice btree. Con 10k+ usuarios, cada keystroke (`wire:model.live.debounce.300ms`) dispara seq scan. No hay `pg_trgm` ni `GIN`.

**Evidencia:** `ilike '%pattern%'` no sargable. `User::with('roles')->latest()->paginate(10)` hace `order by created_at desc` sin índice compuesto `search` + `created_at`. Hoy volumen bajo → INFO, pero es riesgo a escala.

**Impacto:** Latencia en listado con crecimiento.

**Recomendación:** Diferir hasta medir: `EXPLAIN ANALYZE` con 10k rows, luego `CREATE EXTENSION pg_trgm` + `CREATE INDEX users_name_email_trgm ON users USING gin (name gin_trgm_ops, email gin_trgm_ops)` si p95 >200ms.

**Complejidad:** Media

**Prioridad:** Backlog (no optimizar prematuramente)

---

### [INFO] Observador `RoleObserver` solo en `saved/deleted` — `syncPermissions` no dispara `saved`

**Categoría:** Backend

**Ubicación:** `app/Modules/CoreModule/Observers/RoleObserver.php:12`, `app/Modules/CoreModule/Actions/SyncRolePermissionsAction.php:15`

**Problema:** `RoleObserver` escucha `saved`/`deleted` pero `syncPermissions` (Spatie) escribe en `role_has_permissions`, no hace `save()` en `roles`. El `forgetCachedPermissions` puede no dispararse tras `syncPermissions`.

**Evidencia:** Spatie internamente hace `forgetCachedPermissions` en el trait, pero observer es redundante y a la vez insuficiente. No es bug hoy por Spatie, pero es dead logic si se confía solo en observer.

**Recomendación:** Mantener Spatie's auto-invalidate; remover observer o cambiar a `Role::updated` + listener explícito en `SyncRolePermissionsAction`.

**Complejidad:** Baja

## 7. Matriz de riesgos

| ID   | Severidad | Categoría        | Hallazgo                                                                            | Impacto | Complejidad | Prioridad      |
| ---- | --------- | ---------------- | ----------------------------------------------------------------------------------- | ------- | ----------- | -------------- |
| M001 | P1        | Security/Backend | `syncRoles` parcial destruye roles (UpdateUserAction)                               | Alto    | Baja        | Inmediata      |
| M002 | P1        | Security         | Livewire admin sin authorize intra-componente (NotificationAdmin/SystemMaintenance) | Alto    | Baja        | Inmediata      |
| M003 | P1        | Performance      | `User::all()` + notify síncrono en SystemMaintenance                                | Alto    | Baja        | Próximo sprint |
| M004 | P1        | Security         | Password `min:5` sin complejidad/breach check                                       | Alto    | Baja        | Inmediata      |
| M005 | P1        | Backend          | CreateNewUser sin unique email                                                      | Medio   | Baja        | Próximo sprint |
| M006 | P1        | Performance      | CheckMaintenanceMode sin caché (query por request)                                  | Medio   | Baja        | Próximo sprint |
| M007 | P1        | Backend          | ListUsers `orWhere` sin agrupar rompe filtro rol+search                             | Medio   | Baja        | Próximo sprint |
| M008 | P2        | Security         | Toggle is_active no invalida sesiones DB                                            | Medio   | Baja        | Próximo sprint |
| M009 | P2        | Security/Logic   | `min(hierarchy_level)` rompe multi-rol (debe ser max)                               | Medio   | Baja        | Próximo sprint |
| M010 | P2        | Architecture     | `permission.php` usa modelos Spatie, no Core                                        | Bajo    | Baja        | Backlog        |
| M011 | P2        | DevOps           | LoginTestCommand con email hardcodeado                                              | Bajo    | Baja        | Backlog        |
| M012 | P2        | Testing          | Flujos críticos sin tests (RBAC negativo, maintenance, search)                      | Medio   | Media       | Próximo sprint |
| M013 | P3        | Backend          | UserForm duplica regla password sin `confirmed`                                     | Bajo    | Baja        | Backlog        |
| M014 | P3        | Code Quality     | AppSetting no usa BaseModel                                                         | Bajo    | Baja        | Backlog        |
| M015 | P2        | Database         | `ilike %search%` sin pg_trgm (seq scan a escala)                                    | Bajo    | Media       | Backlog        |
| M016 | INFO      | Backend          | RoleObserver `saved/deleted` no cubre syncPermissions                               | Bajo    | Baja        | Backlog        |

## 8. Ruta de trabajo

### Fase 0 — Bloqueadores (Inmediata, <1 día)

1. **M001 — Corregir UpdateUserAction**
    - Dependencias: ninguna
    - Esfuerzo: Bajo (1h)
    - Riesgo: Alto si se hace mal (probar con test multi-rol)
    - Resultado: sincronización atómica y predecible de roles

2. **M002 — Añadir authorize en NotificationAdmin/SystemMaintenance**
    - Dependencias: ninguna
    - Esfuerzo: Bajo (30min)
    - Riesgo: Bajo
    - Resultado: cierre de bypass Livewire

3. **M004 — Endurecer password o registrar ADR**
    - Dependencias: decisión producto
    - Esfuerzo: Bajo
    - Riesgo: Medio (afecta UX registro)
    - Resultado: alineación con OWASP/NIST o riesgo aceptado documentado

### Fase 1 — Riesgos críticos (Próximo sprint, 2-3 días)

4. **M005 — Unique email en CreateNewUser** — Dep: ninguna — Bajo — 422 correcto
5. **M006 — Cachear CheckMaintenanceMode** — Dep: M003 si comparte CachePolicyService — Bajo — -100 queries/s
6. **M007 — Agrupar orWhere en ListUsers** — Dep: ninguna — Bajo — resultados correctos
7. **M003 — Chunk/Queue en SystemMaintenance::toggle** — Dep: M002 — Bajo — evita OOM
8. **M008 — Invalidar sesiones al desactivar** — Dep: M001 — Bajo — close window post-offboarding
9. **M009 — hierarchy max() no min()** — Dep: M001 — Bajo — RBAC multi-rol correcto
10. **M012 — Tests de RBAC y maintenance** — Dep: M001,M002,M007 — Media — previene regresión

### Fase 2 — Estabilización (Backlog, 1 semana)

11. **M010 — Corregir config/permission modelos Core** — Bajo
12. **M015 — Evaluar índice pg_trgm solo si p95 >200ms** — Media — medir primero

### Fase 3 — Optimización (Solo si métrica lo justifica)

- `UserTourProgress::mapFor` N+1 no existe (single query), no tocar.
- `NotificationBell` 2 queries (count + take 5) — aceptable; no cachear sin evidencia.

### Fase 4 — Mejoras opcionales

- **M011** eliminar/ proteger LoginTestCommand
- **M013** unificar UserForm con PasswordValidationRules
- **M014** migrar AppSetting a BaseModel

**Orden mínimo para estado saludable:** M001 → M002 → M004 (o ADR) → M006 → M007 → M012. Con 6 cambios de baja complejidad el módulo pasa a 🟢.

## 9. Quick Wins

- **M001 fix en 5 líneas:** cambiar `syncRoles($rolesToAdd)` por `syncRoles($requestedRoles)` o `assignRole`/`removeRole` loop.
- **M002 guard en 2 líneas por componente:** `Gate::authorize('admin.system')` en `mount()` + `save()`/`toggle()`.
- **M007 agrupar orWhere:** envolver en `where(fn($q)=>...)` — 1 línea.
- **M005 unique:** agregar `Rule::unique(User::class)` en `CreateNewUser::profileRules()`.
- **M006 cache:** `Cache::remember('core:maintenance', 60, fn()=>AppSetting::get(...))` + `Cache::forget` en `set()`.
- **M011 delete:** `rm LoginTestCommand.php` o `->hidden()` en prod.

Todos <30min, bajo riesgo, alto impacto/riesgo reducido.

## 10. Qué NO hacer

- **No introducir Repository Pattern / Interface para User/Role** — Eloquent + Actions ya son suficientes; no hay segunda implementación.
- **No migrar a CQRS / Event Sourcing** — flujos son CRUD + RBAC; eventos innecesarios ocultarían efectos.
- **No dividir Core en microservicios (auth-service)** — latencia y ops innecesarias; monolito modular ya aísla bien.
- **No agregar DTOs para AppSetting / NotificationConfig simple** — overkill; array shape tipado basta.
- **No cachear `ListUsers` ni `NotificationBell` sin métrica** — hit rate bajo, invalidación compleja.
- **No mover `Auditable` a event sourcing** — trait con `report()` es suficiente; cambiar rompería 12 modelos.
- **No unificar `users` y `employees` en una tabla** — separación credencial/perfil es correcta (ver Model.md).
- **No añadir `Repository` para `UserTourProgress`** — métodos estáticos `mapFor/record/purge` son adecuados para su alcance.

## 11. Cobertura de pruebas

**Existente:**

- `tests/Feature/Core/UsersCrudTest.php:1` — 3 tests: soft delete via Action, delete via Livewire, unique email validación (solo CreateUser Form, no Fortify).
- `tests/Feature/Core/RBACFlowTest.php:1` — 3 tests: hierarchy admin > user, deny lower > higher, syncPermissions cache.
- `tests/Feature/Auth/*` — login, rate limit 5, 2FA challenge, logout, password reset/confirm (genéricos, no Core-specific).
- `tests/Feature/UserTourProgressTest.php:1` — 10 tests: mapFor por usuario, upsert, purge, Livewire events `tour:record`/`tour:purge`.
- `tests/Arch/ModuleBoundariesTest.php:1` — verifica `App\Modules` no usa `*\Internal`.

**Faltante crítico:**

| Flujo                                                | Estado     | Riesgo       |
| ---------------------------------------------------- | ---------- | ------------ |
| `UpdateUserAction` diff de roles multi-rol           | ❌ Sin test | Alto — M001  |
| `ToggleUserStatus` invalida token **y** sesión       | ❌ Sin test | Medio — M008 |
| `CheckMaintenanceMode` 503 vs `admin.system` bypass  | ❌ Sin test | Medio — M006 |
| `NotificationAdmin` requiere `admin.system`          | ❌ Sin test | Alto — M002  |
| `SystemMaintenance` chunk/queue con 500 usuarios     | ❌ Sin test | Medio — M003 |
| `ListUsers` search + role combinados                 | ❌ Sin test | Medio — M007 |
| `CreateNewUser` email duplicado (Fortify)            | ❌ Sin test | Bajo — M005  |
| `PasswordValidationRules` min:5 negativo + confirmed | ❌ Sin test | Medio — M004 |
| `RolePolicy` multi-rol max vs min                    | ❌ Sin test | Medio — M009 |

**Verificación sugerida:**

```bash
php artisan test --compact --filter=Core
php artisan test --compact --filter=UserTourProgress
php artisan test --compact --filter=RBACFlowTest
vendor/bin/pint --test --format agent  # ya en ci:check
php artisan route:list --name=users --except-vendor
EXPLAIN ANALYZE SELECT * FROM users WHERE name ILIKE '%test%' OR email ILIKE '%test%';
```

## 12. Riesgos pendientes

- **Spatie permission cache** — `RoleObserver` redundante; si se cambia `CACHE_STORE` a `redis` y hay múltiples pods, `forgetCachedPermissions` debe ser por tag. Monitorear `cache:monitor --stats` tras deploy.
- **`force_password_change` flujo** — `UpdateLastLoginAtListener` setea `force_password_change=true` en primer login, pero `EnsurePasswordChange` middleware no inspeccionado aquí — verificar que bloquea acceso a rutas excepto password change.
- **`AppSetting` sin cifrado** — hoy solo guarda `maintenance_mode` mensaje plano; si se usa para secretos, requiere `Encrypted` cast.
- **`User::initials()` con `Str::of()->explode(' ')`** — nombres con múltiples espacios o unicode pueden dar iniciales vacías; no crítico.
- **`NotificationConfigService::flushByPattern`** — invalida `core:config` en cada `upsert`; si se cachea `CheckMaintenanceMode` con mismo patrón, invalidación cruzada.

## 13. Conclusión

CoreModule está **bien arquitecturado y sin sobreingeniería**; resuelve correctamente identidad y RBAC para 18 módulos. No requiere refactor estructural. Los mayores riesgos son **lógica de roles (M001), autorización Livewire (M002) y política de passwords (M004)** — los tres son P1 de baja complejidad y deben ir en la próxima entrega. Con la ruta Fase 0 (M001+M002+M004) el módulo queda seguro para operar; añadiendo Fase 1 (M005-M009+M012) queda **saludable (🟢)** y listo para escalar a 10k usuarios sin cambios infraestructurales.

**Siguiente acción recomendada:** Crear rama `fix/core-rbac-maintenance` con commits atómicos: `fix(core): correct UpdateUserAction syncRoles`, `fix(core): authorize NotificationAdmin/SystemMaintenance`, `chore(core): harden password validation or record ADR`, y abrir PR con tests `Core/RBACNegativeTest` y `Core/MaintenanceModeTest`. No tocar arquitectura ni introducir abstracciones nuevas.
