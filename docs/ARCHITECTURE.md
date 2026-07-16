# Arquitectura del Sistema — HorariosWFM

> Documento de Arquitectura de Software
> Sistema WFM — Call Center de la Caja de Seguro Social de Panamá
> Versión 2.0 — Julio 2026

---

## 1. Principios Arquitectónicos

| Principio | Aplicación |
|-----------|-----------|
| **Monolito Modular** | Despliegue único, base de datos compartida, dominios aislados en módulos. Sin microservicios. |
| **Acciones, no Services** | La lógica de negocio vive en `Actions/` (una clase = un caso de uso). Prohibido Fat Controllers o Service Classes genéricas. |
| **Comunicación desacoplada** | Los módulos no importan modelos de otros módulos directamente. Usan `Events`, `DTOs` y `Contracts`. |
| **ULID como estándar** | Todas las llaves primarias son ULIDs via `BaseModel`. La base de datos usa `bigint` auto-incremental como PK física; ULID es la identidad lógica. |
| **Inmutabilidad y trazabilidad** | `AuditLog` es append-only. Operaciones de escritura multi-entidad van en `DB::transaction()`. |
| **Prevención de N+1** | `Model::preventLazyLoading()` activo globalmente en producción. |
| **Seguridad por capas** | Fortify (auth) → Middleware (sesión, verificación) → Policy (autorización granular). Super-admin bypass via `Gate::before()`. |
| **declare(strict_types=1)** | Exigido por Pint en todo archivo PHP del proyecto. |

---

## 2. Diagrama de Dependencias entre Módulos

```
                         ┌──────────────┐
                         │  QualityMod  │
                         │  (en curso)  │
                         └──────┬───────┘
                                │
         ┌──────────────┐       │
         │  Workflows   │       │
         │  Module      │       │
         └──────┬───────┘       │
                │               │
┌───────────────┼───────────────┼──────────────────┐
│  Support      │               │                  │
│  Modules      │  Helpdesk │ Documentation       │
│  Filesystem   │  Knowledge                      │
└───────────────┼───────────────┼──────────────────┘
                │               │
         ┌──────┴───────┐ ┌────┴────────┐
         │Communications│ │ Operations  │
         │  Module      │ │  Module     │
         └──────┬───────┘ └────┬────────┘
                │              │
         ┌──────┴───────┐ ┌───┴──────────┐
         │  AuditMod    │ │  WfmModule   │
         └──────┬───────┘ └────┬─────────┘
                │              │
         ┌──────┴──────────────┴──────────┐
         │        ConnectModule           │
         │   (Cisco UCCX/Finesse/CUIC)    │
         └───────────────┬────────────────┘
                         │
         ┌───────────────┴────────────────┐
         │      PersonnelModule           │
         │   (Empleados, equipos, RRHH)   │
         └───────────────┬────────────────┘
                         │
         ┌───────────────┴────────────────┐
         │  OrganizationModule │ GeoModule│
         └───────────────┬────────────────┘
                         │
         ┌───────────────┴────────────────┐
         │          CoreModule            │
         │   (Auth, RBAC, Config global)  │
         └────────────────────────────────┘
```

**Regla:** Un módulo solo conoce a sus dependientes (flechas hacia abajo). La comunicación ascendente ocurre via `Events` y `Contracts` en `app/Shared/`.

---

## 3. Estructura Interna de un Módulo

```
app/Modules/{Module}/
├── Actions/           → Lógica de negocio (un archivo = un caso de uso)
├── Console/Commands/  → Comandos Artisan propios del módulo
├── Database/
│   ├── Migrations/    → Migraciones locales (alternativa a database/migrations/)
│   └── Seeders/       → Seeders específicos
├── DTOs/              → Objetos de transferencia inmutables (Spatie Data)
├── Emails/            → Clases Mailable
├── Enums/             → Enums PHP 8+ del dominio
├── Events/            → Eventos de dominio
├── Http/
│   ├── Controllers/   → Solo para APIs y webhooks (no Livewire)
│   └── Requests/      → Form Requests (solo para endpoints HTTP clásicos)
├── Jobs/              → Jobs encolables
├── Listeners/         → Manejadores de eventos
├── Livewire/
│   ├── *.php          → Componentes Livewire (orquestación UI, sin lógica de negocio)
│   └── Forms/         → Livewire Form Objects (validación)
├── Mail/              → Vistas de correo
├── Models/            → Eloquent Models (heredan de BaseModel)
├── Notifications/     → Clases de notificación
├── Observers/         → Efectos secundarios de modelos
├── Policies/          → Autorización (Spatie Permission)
├── Providers/
│   └── ModuleServiceProvider.php  → Registro del módulo
├── Repositories/      → Solo si hay consultas complejas reutilizables
├── Resources/Views/   → Vistas Blade (prefijo: `{module}::vista`)
├── Routes/
│   ├── web.php        → Rutas web
│   └── api.php        → Rutas API
└── Services/          → Solo si hay lógica reutilizable entre múltiples Actions
```

### 3.1 Reglas estrictas

- **Actions:** Un solo método público `execute()`. Operaciones de escritura usan `DB::transaction()`.
- **Livewire:** No contiene lógica de base de datos ni de negocio. Valida via `Form Objects`, delega a `Actions`, redirige con `navigate: true`.
- **DTOs:** Clases `readonly` tipadas. Se construyen desde `Form Objects` o `Form Requests`.
- **Policies:** Usan `$user->hasPermissionTo()` (Spatie). Toda entidad DEBE tener su Policy.
- **Observers:** Solo efectos secundarios (caché, logs, sincronizaciones). Sin lógica de negocio.

---

## 4. Comunicación entre Módulos

### 4.1 Shared Events (app/Shared/Events/)

Eventos de dominio compartidos entre módulos:

| Evento | Emisor | Propósito |
|--------|--------|-----------|
| `WeeklySchedulePublished` | WfmModule | Horario semanal publicado |
| `ScheduleAssignmentUpdated` | WfmModule | Asignación de turno modificada |
| `LeaveRequestCreated` | WfmModule | Solicitud de permiso creada |
| `LeaveRequestDecision` | WfmModule | Decisión sobre solicitud de permiso |
| `ShiftSwapRequested` | WfmModule | Intercambio de turno solicitado |
| `ShiftSwapApproved` | WfmModule | Intercambio de turno aprobado |

### 4.2 Shared Contracts (app/Shared/Contracts/)

14 interfaces que definen contratos entre módulos:

| Namespace | Interfaces | Consumidor |
|-----------|-----------|------------|
| `Employees/` | `EmployeeInterface`, `EmployeeRepositoryInterface`, `EmployeeLookupRepositoryInterface` | WfmModule, OperationsModule |
| `Schedules/` | `ScheduleServiceInterface`, `ScheduleRepositoryInterface`, `DashboardScheduleQueriesInterface`, `LeaveRequestServiceInterface`, `ShiftSwapServiceInterface` | OperationsModule |
| `Telemetry/` | `TelemetryServiceInterface`, `TelemetryRealtimeRepositoryInterface` | OperationsModule |
| `Operations/` | `AgentPerformanceRepositoryInterface` | Dashboard módulos |
| `Quality/` | `CriteriaRepositoryInterface`, `EvaluationRepositoryInterface` | QualityModule |
| `Identity/` | `UserInterface` | Todos los módulos |

### 4.3 Shared DTOs (app/Shared/DTOs/)

- `NotificationDTO` — Payload para notificaciones multi-canal
- `TimelineItemDTO` — Item de línea de tiempo de agente
- `AdherenceStatusDTO` — Estado de adherencia
- `ScheduleDayDTO` — Resumen de día de horario
- `TelemetryStateDTO` — Estado de telemetría
- `Operations/AgentCallRecordDTO`, `AgentDailyMetricDTO`, `AgentStateTransitionDTO` — Métricas operativas

### 4.4 Regla de comunicación

```
❌ INCORRECTO:  use App\Modules\Inventory\Models\Product;  // Dependencia directa
✅ CORRECTO:    event(new OrderCreated($orderDTO));         // Evento desacoplado
✅ CORRECTO:    app(ScheduleServiceInterface::class)        // Contrato
```

---

## 5. Shared Infrastructure

### 5.1 BaseModel

`app/Shared/Models/BaseModel.php` — Clase base abstracta para todos los modelos:

- Usa `HasUlids` trait de Laravel
- `$incrementing = false`, `$keyType = 'string'`
- La base de datos usa `$table->id()` (bigint auto-incremental) como PK física. ULID es la identidad lógica manejada por Eloquent.

### 5.2 MetricFormulas

`app/Shared/Support/Metrics/MetricFormulas.php` — Clase `final` con 16 métodos `static` que centralizan las fórmulas de cálculo:

- `productivity()`, `utilization()`, `utilizationDenominator()`
- `checkLate()`, `aht()`, `secondsToMinutes()`, `formatDuration()`
- `checkAdherence()`, `coverageRate()`, `absenteeismRate()`
- `absentPersonnel()`, `occupancy()`, `conformance()`, `asa()`, `serviceLevel()`

### 5.3 BaseNotification

`app/Shared/Notifications/BaseNotification.php` — Clase abstracta para notificaciones multi-canal:

- Implementa `ShouldQueue` (todas las notificaciones son asíncronas)
- Canales: `database` (notificaciones in-app), `broadcast` (WebSockets via Reverb), `webex` (condicional según configuración)
- Usa `NotificationDTO` como payload tipado

### 5.4 CiscoFinesseClient

`app/Shared/Infrastructure/Cisco/CiscoFinesseClient.php` — Cliente HTTP para API REST XML de Cisco Finesse:

- Autenticación Basic Auth
- Timeout configurable (15s default)
- `withoutVerifying()` para entornos con certificados auto-firmados
- Parseo XML → JSON → array via `simplexml_load_string` + `json_encode`

### 5.5 Helper global

`app/Helpers/toast.php` — Auto-cargado via `composer.json` `files` key. Provee la función global `toast()` para notificaciones flash (success/danger/warning/info).

---

## 6. Frontend Architecture

### 6.1 Stack

- **Livewire 4** — Componentes reactivos del lado del servidor
- **Flux UI 2** — Librería de componentes UI (todo `<flux:xxx>`)
- **TailwindCSS 4** — Estilos (vía Vite plugin)
- **Vite 8** — Bundler de assets
- **Laravel Echo 2** — Cliente WebSocket (Pusher/Reverb)
- **ApexCharts** — Gráficos en dashboards

### 6.2 Patrón de Componente Livewire

```
Form Object (validación) → Componente Livewire (orquestación) → Action (lógica) → Evento/Respuesta
```

Ejemplo de flujo:

```
CreateDirectorate (Livewire Component)
  ├── $this->authorize('create', Directorate::class)   → Policy
  ├── $this->validate()                                 → rules()
  ├── DirectorateDTO::fromArray($validated)              → DTO inmutable
  ├── (new CreateDirectorateAction)->execute($dto)       → Action transaccional
  └── redirect()->with('success', '...')                 → Respuesta
```

### 6.3 Reglas de UI

- **Siempre FluxUI:** `<flux:input>`, `<flux:button>`, `<flux:modal>`, `<flux:table>`, etc. Si un componente FluxUI no existe para el caso, usar HTML estándar con comentario `<!-- TODO: Refactor to FluxUI -->`.
- **SPA Navigation:** `wire:navigate` en enlaces internos, `navigate: true` en redirecciones.
- **Form Objects:** Toda validación de UI en `Livewire/Forms/`, no en el componente principal.
- **Lazy Loading:** Widgets pesados usan `<livewire:widget lazy />`.

### 6.4 Componentes registrados

Los componentes Livewire se registran manualmente en cada `ModuleServiceProvider`:

```php
Livewire::component('users::create', CreateUser::class);
```

Adicionalmente, `config/livewire.php` tiene un mapeo `component_namespaces` para resolución automática. Nuevos componentes pueden requerir entrada en ambos lugares.

---

## 7. Database Architecture

### 7.1 PostgreSQL Features

El proyecto aprovecha características específicas de PostgreSQL:

| Feature | Uso |
|---------|-----|
| `tsTZrange` / `tstzrange` | Rangos de tiempo en intraday activities (con GiST index para exclusión de solapamientos) |
| `UNLOGGED TABLE` | `agent_realtime_states` — tabla de alta frecuencia sin WAL |
| `jsonb` | Metadatos flexibles en `employees`, `schedules`, `agent_realtime_states` |
| `btree_gist` extension | Índices GiST para exclusión de rangos |
| Partial indexes | Índices condicionales para consultas frecuentes |
| `CHECK` constraints | Validación a nivel DB (ej. `parent_id <> id` en employees) |

### 7.2 Migraciones

- **64 migraciones** en `database/migrations/` — orden lineal con timestamps `2026_MM_DD_*`
- **~16 migraciones locales** en `app/Modules/{Module}/Database/Migrations/` — cargadas via `ModuleServiceProvider::loadMigrationsFrom()`
- Las migraciones verifican `DB::getDriverName() === 'pgsql'` para aplicar features PostgreSQL; caso contrario fallback a tipos estándar.

### 7.3 ULID vs PK física

- **Modelo:** `BaseModel` usa `HasUlids`, `$incrementing = false`, `$keyType = 'string'`
- **Base de datos:** Las migraciones usan `$table->id()` (bigint auto-incremental)
- El ULID se genera y almacena como string en la columna `id`. La base de datos ve un `bigint` pero Eloquent maneja el ULID como string.

### 7.4 Seeders

28 seeders ejecutados en orden de dependencias en `DatabaseSeeder.php`:

1. Geografía (Panamá)
2. Organización (Direcciones, Departamentos, Puestos)
3. Estados de contratación
4. Roles y permisos (130+ permisos, 7 roles jerárquicos + 4 roles de calidad)
5. Usuarios y empleados
6. Equipos y asignaciones
7. Comunicaciones, colas, subtipos
8. Horarios y catálogos WFM
9. Helpdesk, configuraciones operativas
10. Base de conocimiento

El admin (`ferncastillo@css.gob.pa`) recibe forzosamente el rol `admin` al final del seeding.

---

## 8. Integraciones Externas

### 8.1 Cisco UCCX/Finesse — Tiempo Real

| Aspecto | Detalle |
|---------|---------|
| **Propósito** | Estados de agente en vivo, diálogos activos |
| **Protocolo** | API REST XML (Basic Auth) |
| **Cliente** | `CiscoFinesseClient` en `app/Shared/Infrastructure/Cisco/` |
| **Frecuencia** | Cada 5 segundos via `CiscoSync` job auto-despachante |
| **Horario** | 5:00 AM — 7:00 PM (horario laboral del call center) |
| **Tabla destino** | `agent_realtime_states` (UNLOGGED, updateOrInsert) |
| **Configuración** | `config/contact-center.php` + variables de entorno `UCCX_*` |

### 8.2 Cisco CUIC — Histórico (ETL)

| Aspecto | Detalle |
|---------|---------|
| **Propósito** | CDRs, transiciones de estado, métricas de desempeño |
| **Protocolo** | API REST con UUIDs de reporte pre-configurados |
| **Reportes** | 7 tipos: agent_state_transitions, agent_detail, agent_performance_detail, agent_csq_detail, voice_csq_summary, agent_chat_detail, agent_realtime_detail |
| **Frecuencia** | Cada 15-300 segundos via comandos Artisan (`cuic:sync`, `cuic:sync-realtime`) |
| **Tablas destino** | `call_records`, `agent_state_transitions`, `agent_call_performance`, `chat_records`, `csq_realtime_stats` |

### 8.3 Cisco Sync Job

`app/Jobs/CiscoSync.php` — Job auto-despachante que:

1. Recibe un flag `syncMasterData` (true en primera ejecución del día para sincronizar nombres/equipos)
2. Itera empleados activos con username
3. Consulta `CiscoFinesseClient::getAgentInfo()` por cada uno
4. Para agentes en estado `TALKING`, obtiene datos de la llamada via `getAgentDialogs()`
5. Actualiza `agent_realtime_states` con `updateOrInsert()`
6. Se re-despacha a sí mismo con `delay(5 segundos)`
7. Usa cola `realtime-sync` (aislada en Horizon) para no bloquear otras colas

### 8.4 Webex

| Aspecto | Detalle |
|---------|---------|
| **Propósito** | Notificaciones a equipos de IT/operaciones |
| **Servicio** | `app/Services/WebexService.php` (singleton) |
| **Canal de notificación** | `WebexChannel` en `app/Notifications/Channels/` |
| **Métodos** | `sendText()`, `sendMarkdown()`, `sendToAll()` |

---

## 9. Queue y Job Architecture

### 9.1 Horizon Configuration

| Supervisor | Colas | Procesos | Timeout | Propósito |
|-----------|-------|----------|---------|-----------|
| `supervisor-1` | `default`, `notifications` | 10 (auto) | 60s | Trabajo general y notificaciones |
| `supervisor-wfm` | `wfm-heavy` | 5 (simple) | 300s | Cálculos pesados de planificación |

### 9.2 Colas del Sistema

| Cola | Conexión | Propósito |
|------|----------|-----------|
| `default` | Redis | Trabajo general |
| `notifications` | Redis | Notificaciones push/email/webex |
| `wfm-heavy` | Redis | Cálculos de planificación masiva |
| `realtime-sync` | Database | Sincronización Cisco (aislada por conexión database) |

### 9.3 Patrón de Job Auto-despachante

`CiscoSync` implementa el patrón de auto-re-despacho para reemplazar un while-loop tradicional:

```php
public function handle(): void
{
    // ... sync logic ...
    
    // Re-despachar con 5 segundos de retraso
    self::dispatch(false)->delay(now()->addSeconds(5));
}
```

Esto permite que Horizon maneje la concurrencia, reintentos y monitoreo sin bloquear workers.

---

## 10. Security Architecture

### 10.1 Capas de Seguridad (en orden)

```
Request
  │
  ├─ Laravel Fortify (auth)
  │   ├─ Email + Password
  │   ├─ 2FA (TOTP)
  │   └─ Email Verification
  │
  ├─ Middleware
  │   ├─ EncryptCookies
  │   ├─ VerifyCsrfToken
  │   ├─ EnsurePasswordChange (custom)
  │   └─ InjectMenuData (custom)
  │
  ├─ Policy (Spatie Permission)
  │   ├─ $user->hasPermissionTo('users.edit')
  │   ├─ $user->hasRole('admin') → super-bypass via Gate::before()
  │   └─ Jerarquía de roles (hierarchy_level: 1-99)
  │
  └─ Action (lógica de negocio)
      └─ DB::transaction() + validación de estado
```

### 10.2 RBAC — Roles y Jerarquía

7 roles principales con `hierarchy_level`:

| Rol | Código | Nivel | Permisos |
|-----|--------|-------|----------|
| operator | OP | 1 | Mínimos (ver horario propio, solicitar cambios) |
| supervisor | SUP | 2 | Visión de equipo, aprobar solicitudes básicas |
| coordinator | COOR | 3 | Operaciones, reportes, gestión intra-día |
| chief | JEF | 4 | Reportes gerenciales, analytics |
| wfm | WFM | 5 | **Todos los permisos** (igual que admin) |
| director | DIR | 6 | Visión ejecutiva, comunicaciones |
| admin | ADM | 99 | **Todos los permisos + super-bypass** |

Además, 4 roles específicos de calidad: `quality-evaluator`, `quality-supervisor`, `quality-coordinator`, `quality-admin`.

### 10.3 Super-admin Bypass

En `AppServiceProvider::boot()`:

```php
Gate::before(function ($user, $ability) {
    return $user->hasRole('admin') ? true : null;
});
```

El rol `admin` (nivel 99) tiene acceso total a todo. Retorna `true` para cualquier habilidad; retorna `null` para no-intervención si no es admin (delegando a la Policy).

### 10.4 Protecciones Globales

- `DB::prohibitDestructiveCommands()` en producción — protege contra `DB::statement('DROP TABLE...')`
- `Model::preventLazyLoading()` — lanza excepción si se intenta lazy load
- `Password::min(12)` con mixedCase + letters + numbers + symbols + uncompromised en producción
- `Date::use(CarbonImmutable)` — inmutabilidad de fechas para evitar efectos secundarios

---

## 11. Testing Strategy

### 11.1 Configuración

- **Framework:** Pest 4 + `pestphp/pest-plugin-laravel`
- **Base de datos:** SQLite in-memory (`phpunit.xml`: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`)
- **Trait global:** `RefreshDatabase` aplicado via `pest()->extend(TestCase::class)->use(RefreshDatabase::class)`
- **Colas:** `QUEUE_CONNECTION=sync` en tests
- **Cache:** `CACHE_STORE=array` en tests

### 11.2 Prioridad de Tests

1. **Feature Tests** — Prueban el flujo completo: Livewire → Action → DB → respuesta
2. **Unit Tests** — Solo para lógica pura sin DB (ej. `MetricFormulas`)
3. **No escribir** tests excesivamente aislados que no detecten problemas reales

### 11.3 Ubicación

- `tests/Feature/Modules/{Module}/` — Tests de módulo específicos
- `tests/Feature/` — Tests generales
- `tests/Unit/` — Tests unitarios puros

### 11.4 Comandos

- `composer test` — `config:clear` → `lint:check` → `php artisan test`
- `php artisan test --compact --filter=TestName` — Test específico

---

## 12. Infrastructure & Deployment

### 12.1 Entornos

| Entorno | DB | Colas | Cache |
|---------|----|-------|-------|
| Local | SQLite (dev) / PostgreSQL | Database (dev) | Database (array) |
| Producción | PostgreSQL 16 | Redis (Horizon) | Redis |

### 12.2 Scripts de Producción

| Script | Propósito |
|--------|-----------|
| `start-cisco-sync.sh` | Daemon de sincronización Cisco Finesse (loop 5s) |
| `start-cuic-sync.sh` | Daemon de sincronización CUIC (realtime cada 15s, ETL cada 300s) |
| `worker-cron.sh` | Worker queue con horario laboral (5AM-7PM) |
| `sincroniza.sh` | Rsync de develop → servidor producción |

### 12.3 Commands de Consola

| Comando | Propósito |
|---------|-----------|
| `php artisan cisco:sync --loop --interval=5` | Sincronización en vivo Cisco Finesse |
| `php artisan cuic:sync --loop --interval=300 --minutes=60` | ETL histórico CUIC |
| `php artisan cuic:sync-realtime --loop --interval=15` | Sincronización en tiempo real CUIC |
| `php artisan connect:sync-cuic` | Sincronización CUIC (una ejecución) |
| `php artisan connect:sync-realtime` | Sincronización tiempo real (una ejecución) |

### 12.4 Dev Server

```bash
composer dev   # Inicia 4 procesos concurrentes:
               # - php artisan serve (puerto 8000)
               # - php artisan queue:listen (tries=1, timeout=0)
               # - php artisan pail (logs en terminal)
               # - npm run dev (Vite HMR)
```

---

## 13. Glosario Arquitectónico

| Término | Definición |
|---------|-----------|
| **Action** | Clase con un único método `execute()` que encapsula un caso de uso transaccional |
| **DTO** | `readonly class` con propiedades tipadas para transporte de datos entre capas |
| **Form Object** | Clase Livewire que encapsula `rules()` y `validationAttributes()` para un formulario |
| **ModuleServiceProvider** | ServiceProvider que registra rutas, vistas, Livewire components y policies de un módulo |
| **Shared Contract** | Interface en `app/Shared/Contracts/` que define un contrato entre módulos |
| **Shared Event** | Evento de dominio en `app/Shared/Events/` para comunicación cross-module |
| **Policy** | Clase de autorización que usa `$user->hasPermissionTo()` de Spatie |
| **Self-dispatching Job** | Job que se re-despacha a sí mismo al finalizar para crear un loop manejado por la cola |
| **UNLOGGED TABLE** | Tabla PostgreSQL sin WAL (Write-Ahead Log), usada para datos transitorios de alta frecuencia |
| **ULID** | Identificador único lexicográficamente ordenable, generado por Eloquent via `HasUlids` trait |
