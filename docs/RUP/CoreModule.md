# Especificación Técnica Detallada: CoreModule (Módulo Principal y Base)

> Documento RUP Centrado en Arquitectura
> **Módulo:** CoreModule
> **Ruta:** `app/Modules/CoreModule`

## 1. Resumen Ejecutivo y Propósito del Módulo

El **CoreModule** es el pilar fundacional, o *Kernel* arquitectónico, sobre el cual se edifica todo el ecosistema del Monolito Modular. Es el único módulo estrictamente obligatorio para el funcionamiento de la aplicación.

Su propósito unívoco es gestionar la **Identidad y el Acceso (IAM - Identity and Access Management)**, orquestando la autenticación (login/logout, sesiones, tokens), la autorización (Role-Based Access Control - RBAC) y el ciclo de vida de los usuarios. Adicionalmente, provee la infraestructura para las configuraciones globales del sistema (`AppSetting`). Al ser la "raíz" del árbol de dependencias, establece los modelos base (`User`) que todos los demás módulos de negocio utilizarán para resolver auditorías, autorías y asignaciones.

---

## 2. Casos de Uso Detallados

A continuación, el detalle de los flujos principales de gestión de identidad y configuración:

### CU-COR-01: Autenticación y Ciclo de Sesión (Cualquier Usuario)

- **Actor:** Usuario anónimo / Usuario autenticado.
- **Descripción:** Gestión segura del ingreso y salida del sistema.
- **Flujo Principal:**
  1. El usuario envía credenciales en texto plano (email y password) mediante el portal de login.
  2. Laravel Fortify intercepta la petición, busca el `User` activo por email y verifica el hash de la contraseña (Bcrypt/Argon2).
  3. Si la verificación es exitosa y el usuario no está inhabilitado, se regenera el ID de sesión (prevención de Session Fixation) y se crea una cookie autenticada.
  4. Si el sistema detecta que el usuario activó la Autenticación de Dos Factores (2FA), se retiene el ingreso hasta validar el código TOTP (Time-based One-Time Password).

### CU-COR-02: Gestión del Ciclo de Vida del Usuario (IAM)

- **Actor:** Administrador (Recursos Humanos / IT).
- **Descripción:** Alta, modificación y baja lógica de credenciales.
- **Flujo Principal:**
  1. El actor llena el formulario de alta de nuevo empleado.
  2. El sistema invoca `CreateUserAction`, el cual valida reglas estrictas (unicidad de email, fortaleza de contraseña).
  3. Persiste el modelo `User`, hashea la contraseña y emite el Domain Event `UserCreated`.
  4. (Opcional) Si se detecta un comportamiento anómalo o retiro del empleado, el administrador ejecuta `ToggleUserStatusAction`. El sistema marca el flag `is_active = false` (o `deleted_at`) e invalida inmediatamente todas sus sesiones web y tokens de API activos.

### CU-COR-03: Mapeo y Delegación de Permisos (RBAC)

- **Actor:** Super Administrador.
- **Descripción:** Definición dinámica de los niveles de autorización en la plataforma.
- **Flujo Principal:**
  1. El actor accede al panel de roles. Selecciona un rol (ej. "Auditor") y una lista de permisos discretos (ej. `[audit.view, audit.export]`).
  2. Al guardar, se ejecuta `SyncRolePermissionsAction`. Este Action actualiza la tabla pivote de Spatie en DB y, de forma crítica, **purga la memoria caché (Redis)** donde se almacenan los permisos para que los cambios surtan efecto en el siguiente request del usuario.

### CU-COR-04: Administración de Configuraciones Globales en Caliente

- **Actor:** Administrador del Sistema.
- **Descripción:** Cambiar parámetros globales sin requerir despliegues (deployments) de código.
- **Flujo Principal:**
  1. El usuario altera la variable `wfm.max_toleration_minutes` (ej. de 15 a 10 minutos).
  2. El sistema guarda la llave-valor en `AppSetting` y limpia la caché de configuraciones para que los demás módulos (ej. `WfmModule`) adopten el nuevo comportamiento instantáneamente.

---

## 3. Requerimientos Funcionales (RF)

- **RF-COR-01 (Identidad Centralizada):** El módulo debe exponer de manera inequívoca la entidad `App\Modules\CoreModule\Models\User`. Esta entidad es la única autorizada para servir de Foreign Key (`user_id`) en el resto de la base de datos (ej. autor de una noticia, agente de una llamada).
- **RF-COR-02 (Delegación de Autenticación):** El módulo no debe programar lógica de login customizada, sino depender de los controladores nativos de Laravel Fortify (o Breeze/Jetstream si aplican) para cumplir con los estándares OWASP (protección contra fuerza bruta y CSRF).
- **RF-COR-03 (Granularidad RBAC):** La autorización debe soportar la validación tanto por Rol (`$user->hasRole('admin')`) como por Permiso atómico (`$user->can('articles.delete')`), dando prioridad siempre a la validación por permisos para mantener bajo acoplamiento con nombres de roles rígidos.
- **RF-COR-04 (Configuraciones Tipadas):** El diccionario `AppSetting` debe soportar el casteo automático de sus valores almacenados (strings JSON) a sus tipos nativos de PHP (boolean, integer, array) para facilitar su consumo por otros módulos.

---

## 4. Requerimientos No Funcionales (RNF)

- **RNF-COR-01 (Rendimiento Crítico - Caché de Autorización):** Como la validación de permisos (`->can()`) se ejecuta múltiples veces por cada petición HTTP (para ocultar botones en Blade/Livewire y validar endpoints), la lista de permisos del usuario DEBE estar almacenada en memoria rápida (Redis/Memcached). Consultar la DB por permisos en cada request es un anti-patrón inaceptable.
- **RNF-COR-02 (Alta Disponibilidad):** Las consultas de autenticación (búsqueda por `email` en la tabla `users`) deben poseer un índice B-Tree (Unique Index) a nivel de base de datos para garantizar retornos en menos de 5ms.
- **RNF-COR-03 (Seguridad Criptográfica):** Prohibición estricta de loguear (en logs de auditoría o de aplicación) contraseñas en texto plano o modificar el hash algorítmico fuera de los flujos de `Fortify` o el `Hash` facade de Laravel.

---

## 5. Modelos de Datos Detallados

A continuación, la estructura relacional fundacional:

| Atributo | Tipo de Dato DB | Cast Eloquent | Descripción y Lógica de Negocio |
| :--- | :--- | :--- | :--- |
| **Entidad: `User`** | | | **Actor central del sistema.** |
| `id` | `bigint` (PK) | `int` | Llave primaria y de relación global. |
| `name`, `email` | `string` | `string` | Identificadores personales (`email` con Unique Index). |
| `password` | `string` | `hashed` | Cadena cifrada (Bcrypt). Invisible en serialización JSON. |
| `two_factor_secret` | `text` | `string` | Semilla cifrada para generación de códigos TOTP. |
| `is_active` / `status` | `boolean` / `string` | `bool` | *Feature Flag* para inhabilitar el ingreso sin borrar el registro. |
| **Entidad: `Role`** | | | **Esquema Spatie Permission (Agrupador)** |
| `name` | `string` | `string` | Nombre del rol (Ej. `SuperAdmin`, `HR_Manager`). |
| `guard_name` | `string` | `string` | Generalmente `web` o `api`. |
| **Entidad: `Permission`** | | | **Esquema Spatie Permission (Regla Atómica)** |
| `name` | `string` | `string` | Nomenclatura sugerida: `modulo.recurso.accion` (Ej. `news.view`). |
| **Entidad: `AppSetting`** | | | **Configuración Global Key-Value** |
| `key` | `string` (PK) | `string` | Llave de búsqueda (Ej. `system.maintenance_mode`). |
| `value` | `jsonb` / `text` | `array`/`auto` | Valor almacenado que puede ser primitivo o un array complejo. |

---

## 6. Roles y Permisos (Policies)

La paradoja del CoreModule es que **gestiona** los permisos, pero también requiere estar protegido por ellos:

- **Rol `Super Admin`:** Rol de sistema que posee un *Wildcard* o intersección global (`Gate::before(fn($user) => $user->hasRole('Super Admin') ? true : null)`). Tiene acceso ilimitado y total a cualquier acción del sistema sin necesidad de permisos individuales.
- **Gestor IAM (`users.create`, `users.update`, `users.delete`):** Permiso habitualmente asignado al departamento de Recursos Humanos o IT, permite la gestión del ciclo de vida del `User`. Sin embargo, no pueden auto-asignarse roles de mayor privilegio.
- **Gestor de Seguridad (`roles.manage`, `settings.manage`):** Permiso de alto riesgo que faculta alterar el comportamiento global de la aplicación (`AppSetting`) y las matrices de seguridad (`Role` / `Permission`).

---

## 7. Eventos, Listeners y Notificaciones

El CoreModule no suele escuchar eventos de otros (porque no depende de nadie), pero es el principal **emisor** de eventos semilla de la aplicación:

### Eventos de Dominio Emitidos

- `UserCreated`: Disparado inmediatamente después de que `CreateUserAction` commitea la transacción.
  - **Módulo Afectado (Ejemplo):** El `PersonnelModule` escucha esto mediante su `CreateEmployeeListener` para generar el expediente laboral y perfil demográfico acoplado al usuario nuevo.
- `UserStatusChanged` / `UserDeactivated`: Notifica al sistema que una cuenta fue apagada.
  - **Módulo Afectado (Ejemplo):** El `ConnectModule` podría escucharlo para inhabilitar al agente en la central telefónica externa (Cisco).
- `RoleAssigned`: Útil para invalidar cachés secundarias dependientes de permisos.

---

## 8. Servicios y Acciones Detallados (Actions & DTOs)

El CoreModule delega las operaciones sensibles de seguridad a Actions fuertemente tipados:

### `CreateUserAction`

- **Responsabilidad:** Orquestar la inserción segura de una credencial.
- **Implementación:**
  1. Recibe un DTO validado (`UserCreateDTO`).
  2. Verifica que el `email` no exista previamente.
  3. Inicia `DB::transaction()`.
  4. Crea el `User`, aplicando `Hash::make()` a la contraseña si no vino ya pre-cifrada por Fortify.
  5. Vincula los IDs de roles primarios (si el DTO los incluye) usando `$user->assignRole()`.
  6. Finaliza la transacción y emite `event(new UserCreated($user))`.
  
### `SyncRolePermissionsAction`

- **Ubicación:** `App\Modules\CoreModule\Actions\SyncRolePermissionsAction`
- **Responsabilidad:** Mantener la matriz RBAC.
- **Lógica Crítica:** Altera la tabla pivote de Spatie `role_has_permissions`. Posteriormente, debe invocar imperativamente `app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions()` para evitar condiciones de carrera donde un usuario no adquiera sus nuevos permisos sino hasta que su sesión caduque naturalmente.

### Carpeta `Fortify/` (Acciones Nativas de Laravel)

- Contiene clases como `CreateNewUser`, `ResetUserPassword`, `UpdateUserProfileInformation` que actúan como adaptadores para el motor de autenticación nativo, manteniendo el estándar oficial del Framework.

---

## 9. Endpoints o Rutas Detalladas (Livewire / Web / Auth)

El módulo divide sus rutas entre autenticación estandarizada y administración SPA (Single Page Application via Livewire):

- **Autenticación Base (Gatillada por Fortify):**
  - `POST /login`: Valida credenciales.
  - `POST /logout`: Invalida sesión y cookie.
  - `POST /forgot-password` / `POST /reset-password`: Flujos de recuperación.
  - `POST /user/two-factor-authentication`: Habilitación TOTP.
- **Administración del Sistema (Rutas protegidas por middleware `can:users.view`):**
  - `GET /admin/users` -> Renderiza el componente Livewire `CoreModule\Livewire\UserIndex` (Data table con opciones de filtrado, paginación e interruptor de On/Off de estado `is_active`).
  - `GET /admin/roles` -> Componente `CoreModule\Livewire\RoleManager`. Incluye una matriz (grid) interactiva cruzando Roles vs Permisos, disparando el Action de Sincronización en tiempo real (`wire:click`).
  - `GET /admin/settings` -> Componente `CoreModule\Livewire\GlobalSettings`.

---

## 10. Dependencias con otros Módulos

El CoreModule dicta el principio de dependencia del Monolito:

- **Dependencia Downstream:** Ninguna. (El CoreModule no conoce la existencia de `News`, `Calls`, `Employees`, `Files`, etc.).
- **Interacción Upstream (Es consumido por todos):**
  - Absolutamente todo módulo que requiera auditoría (`AuditModule`), asignación de recursos (`ConnectModule`), trazabilidad social (`CommunicationsModule`), expedientes (`PersonnelModule`) **DEBE** poseer una llave foránea apuntando estricta e inmutablemente a `users.id`.
  - Todas las Policies del sistema, sin importar el módulo, resuelven sus reglas inyectando la instancia del `User` autenticado provisto por este módulo base.

---

## 11. Estructura de Carpetas

```tree
app/Modules/CoreModule
├── Actions
│   ├── CreateRoleAction.php
│   ├── CreateUserAction.php
│   ├── DeleteUserAction.php
│   ├── Fortify
│   │   ├── CreateNewUser.php
│   │   └── ResetUserPassword.php
│   ├── SyncRolePermissionsAction.php
│   ├── ToggleUserStatusAction.php
│   └── UpdateUserAction.php
├── Concerns
│   └── Auditable.php
├── DTOs
│   ├── RoleDTO.php
│   └── UserDTO.php
├── Http
│   └── Middleware
│       └── CheckMaintenanceMode.php
├── Listeners
│   └── UpdateLastLoginAtListener.php
├── Livewire
│   ├── Forms
│   │   └── UserForm.php
│   ├── Roles
│   │   └── ListRoles.php
│   ├── Shared
│   │   └── NotificationBell.php
│   ├── SystemMaintenance.php
│   ├── Toast.php
│   └── Users
│       ├── CreateUser.php
│       ├── EditUser.php
│       └── ListUsers.php
├── Models
│   ├── AppSetting.php
│   ├── AuditLog.php
│   ├── Permission.php
│   ├── Role.php
│   └── User.php
├── Notifications
│   ├── MaintenanceModeNotification.php
│   ├── PasswordChangedNotification.php
│   └── ResetPasswordNotification.php
├── Observers
│   └── RoleObserver.php
├── Policies
│   ├── RolePolicy.php
│   └── UserPolicy.php
├── Providers
│   └── ModuleServiceProvider.php
├── Resources
│   └── Views
│       ├── auth
│       │   ├── confirm-password.blade.php
│       │   ├── forgot-password.blade.php
│       │   ├── login.blade.php
│       │   ├── register.blade.php
│       │   ├── reset-password.blade.php
│       │   ├── two-factor-challenge.blade.php
│       │   └── verify-email.blade.php
│       ├── livewire
│       │   ├── roles
│       │   │   └── list-roles.blade.php
│       │   ├── shared
│       │   │   └── notification-bell.blade.php
│       │   ├── system-maintenance.blade.php
│       │   ├── toast.blade.php
│       │   └── users
│       │       ├── create-user.blade.php
│       │       ├── edit-user.blade.php
│       │       └── list-users.blade.php
│       ├── maintenance.blade.php
│       └── settings
│           ├── ⚡appearance.blade.php
│           ├── ⚡delete-user-form.blade.php
│           ├── ⚡delete-user-modal.blade.php
│           ├── layout.blade.php
│           ├── partials
│           │   └── heading.blade.php
│           ├── ⚡profile.blade.php
│           ├── ⚡security.blade.php
│           ├── two-factor
│           │   └── ⚡recovery-codes.blade.php
│           └── ⚡two-factor-setup-modal.blade.php
└── Routes
    └── web.php
```
