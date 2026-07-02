# Especificación Técnica Detallada: AuditModule (Módulo de Auditoría)

> Documento RUP Centrado en Arquitectura
> **Módulo:** AuditModule
> **Ruta:** `app/Modules/AuditModule`

## 1. Resumen Ejecutivo y Propósito del Módulo

El **AuditModule** es un componente de infraestructura transversal (Cross-Cutting Concern) dentro del Monolito Modular. Su propósito principal es asegurar la trazabilidad, inmutabilidad y transparencia de todas las operaciones críticas realizadas en la plataforma. Actúa como el registrador central (Ledger) del sistema, interceptando mutaciones de estado de los modelos de negocio y registrando una bitácora detallada de qué usuario realizó qué cambio, sobre qué entidad, en qué momento exacto y desde qué dirección IP.

El módulo está diseñado para cumplir con estrictas normativas de seguridad y auditoría (ej. ISO 27001), garantizando que las reconstrucciones de eventos pasados sean fiables, detalladas y libres de manipulación incluso por parte de los administradores.

---

## 2. Casos de Uso Detallados

A continuación, se describen de manera pormenorizada los casos de uso que cubre el módulo:

### CU-AUD-01: Intercepción y Registro de Cambios de Estado (Sistema)

- **Actor:** Sistema (Automático).
- **Descripción:** Cuando una entidad de negocio marcada como auditable (ej. `User`, `Employee`, `Contract`) sufre un evento de creación, actualización o eliminación en la base de datos, el módulo intercepta este evento automáticamente.
- **Flujo Principal:**
  1. El sistema dispara el evento de persistencia de Eloquent (`saved`, `deleted`).
  2. El listener de auditoría extrae el estado previo (`getOriginal()`) y el estado nuevo (`toArray()`).
  3. Se captura el contexto (ID del usuario autenticado, IP).
  4. Se persiste un nuevo registro inmutable en la tabla `audit_logs`.
- **Excepciones:** Si el usuario no está autenticado (ej. Job asíncrono), el `user_id` puede quedar nulo, pero la IP y la acción se registran.

### CU-AUD-02: Búsqueda y Filtrado de Bitácora (Auditor)

- **Actor:** Auditor / Oficial de Seguridad / Administrador.
- **Descripción:** El usuario accede al panel de auditoría para investigar incidentes específicos.
- **Flujo Principal:**
  1. El actor ingresa a la vista de Livewire (Dashboard de Auditoría).
  2. Aplica filtros complejos: Rango de fechas (Date From/To), Tipo de Acción (Created/Updated/Deleted), Módulo o Tipo de Entidad (ej. `PersonnelModule\Employee`), o búsqueda libre.
  3. El sistema utiliza el Scope `filter` del modelo `AuditLog` para retornar los resultados paginados en milisegundos.

### CU-AUD-03: Exportación Forense de Registros (Auditor)

- **Actor:** Auditor.
- **Descripción:** Generación de un reporte descargable con valor pericial para revisiones externas.
- **Flujo Principal:**
  1. El actor filtra los registros deseados.
  2. Presiona "Exportar". Se invoca el `ExportAuditLogsAction` pasándole un `AuditLogExportDTO`.
  3. El sistema procesa los registros y devuelve un archivo (CSV/Excel) con los logs desencriptados/formateados, listo para ser analizado por auditores externos.

### CU-AUD-04: Exploración Diferencial de Cambios (Diff)

- **Actor:** Supervisor / Administrador.
- **Descripción:** Al inspeccionar un log de tipo "Updated", el sistema desglosa exactamente qué campos cambiaron, comparando el JSON `before` y el JSON `after` resaltando las diferencias.

---

## 3. Requerimientos Funcionales (RF)

- **RF-AUD-01 (Captura Contextual):** El sistema debe capturar invariablemente la dirección IP de la petición (mediante `request()->ip()`) y el ID del usuario activo (`auth()->id()`).
- **RF-AUD-02 (Captura de Estado Diferencial):** Para acciones `updated`, el log debe guardar el payload JSON completo del estado antes de los cambios (`before`) y después de los cambios (`after`). Para `created`, el `before` será nulo; para `deleted`, el `after` será nulo.
- **RF-AUD-03 (Filtrado Avanzado):** El motor de consultas debe soportar filtros relacionales (`ilike` en búsqueda de acciones o tipos de entidad) definidos en el método `scopeFilter`.
- **RF-AUD-04 (Polimorfismo Simple):** La columna `entity_type` debe almacenar el namespace completo de la clase del modelo afectado (ej. `App\Modules\CoreModule\Models\User`), y `entity_id` su llave primaria.
- **RF-AUD-05 (Exportación Centralizada):** Todo proceso de extracción de datos masivos de auditoría debe usar estrictamente el `ExportAuditLogsAction` para garantizar que la consulta use los Scopes correctos.

---

## 4. Requerimientos No Funcionales (RNF)

- **RNF-AUD-01 (Inmutabilidad Estricta):** El registro de auditoría es *Append-Only*. Bajo ninguna circunstancia (ni siquiera por un super-administrador) la aplicación debe exponer un Endpoint, Action o interfaz que permita ejecutar `UPDATE` o `DELETE` sobre la tabla de auditoría.
- **RNF-AUD-02 (Rendimiento y Persistencia de Logs):** Dado que la auditoría puede generar miles de registros por minuto, la tabla debe usar tipo de dato `JSONB` (PostgreSQL nativo) para las columnas `before` y `after`. Esto minimiza el consumo de espacio y acelera las consultas.
- **RNF-AUD-03 (Indexación Compuesta):** Se debe garantizar la existencia de índices en base de datos para `(entity_type, entity_id)` para búsquedas rápidas del historial de un registro específico, y un índice sobre `created_at` para filtros temporales rápidos.
- **RNF-AUD-04 (Tolerancia a Fallos):** La inserción del log de auditoría debe ser asíncrona o ejecutada al final del ciclo de vida (Terminating) para evitar que una falla en el guardado del log aborte la transacción principal del usuario.

---

## 5. Modelos de Datos Detallados

### Entidad: `AuditLog`

**Ubicación:** `App\Modules\AuditModule\Models\AuditLog`
**Relaciones:**

- `belongsTo(User::class)`: Relación hacia el autor de la acción.

**Esquema de Base de Datos y Casteos:**

| Atributo      | Tipo de Dato DB | Cast Eloquent | Descripción y Lógica de Negocio                                        |
| :------------ | :-------------- | :------------ | :--------------------------------------------------------------------- |
| `id`          | `bigint` (PK)   | `int`         | Llave primaria auto-incremental.                                       |
| `entity_type` | `varchar(255)`  | `string`      | FQCN (Fully Qualified Class Name) del modelo afectado.                 |
| `entity_id`   | `bigint`        | `integer`     | Llave primaria (ID) del modelo afectado.                               |
| `action`      | `varchar(50)`   | `string`      | Acción realizada: `created`, `updated`, `deleted`, `restored`.         |
| `before`      | `jsonb`         | `array`       | Volcado del modelo `getOriginal()`. Nulo si `action == created`.       |
| `after`       | `jsonb`         | `array`       | Volcado del modelo `toArray()`. Nulo si `action == deleted`.           |
| `ip_address`  | `varchar(45)`   | `string`      | Dirección IPv4 o IPv6 del originador de la petición HTTP.              |
| `user_id`     | `bigint` (FK)   | `integer`     | ID del usuario autenticado en `CoreModule`. Nulo si es un Job/Consola. |
| `created_at`  | `timestamp`     | `datetime`    | Fecha y hora exacta del registro inmutable.                            |
| `updated_at`  | `timestamp`     | `datetime`    | Mantenido por estándar, aunque no se usa por la inmutabilidad.         |

**Métodos Clave:**

- `public static function log(Model $model, string $action): self`: Fábrica principal estática que calcula automáticamente los estados `before` y `after` de un modelo Eloquent y persiste el log en base al Request y Auth actual.
- `public function scopeFilter(Builder $query, array $filters): void`: Método de abstracción de queries complejas para aplicar búsqueda por texto, entidad y rangos de fechas de manera reutilizable.

---

## 6. Roles y Permisos (Policies)

La gestión de autorización recae en `App\Modules\AuditModule\Policies\AuditLogPolicy`:

- **Permiso: `audit.view`**
  - **Rol Esperado:** Administrador / Auditor Oficial.
  - **Alcance:** Otorga el derecho de entrar a la interfaz de Livewire y navegar por el registro histórico de logs.
- **Permiso: `audit.export`**
  - **Rol Esperado:** Auditor Externo / Jefe de Seguridad.
  - **Alcance:** Permite la descarga de los datos sensibles de auditoría.
- **Restricciones Universales:**
  - Los métodos `create`, `update`, `delete` y `forceDelete` de la policy siempre devuelven `false` explícitamente para todo rol, para proteger la integridad de la bitácora.

---

## 7. Eventos, Listeners y Notificaciones

El funcionamiento de AuditModule es puramente pasivo y reactivo frente al resto del ecosistema:

- **Traits de Dominio (Ej. `Auditable`):**
  - En lugar de escuchar eventos genéricos de aplicación que puedan fallar, el patrón sugerido es que todo modelo que requiera auditoría implemente un Trait (ej. `AuditableTrait`) que registre *Observers* nativos de Eloquent (`created`, `updated`, `deleted`).
- **Generación Silenciosa:**
  - La creación de logs no emite notificaciones a los usuarios para no generar ruido (fatiga de alertas). Solo operaciones críticas y anómalas (ej. 10 eliminaciones en 1 minuto) podrían ser captadas por un monitor (Laravel Pulse) ajeno a este módulo para generar alertas de seguridad.

---

## 8. Servicios y Acciones Detallados (Actions & DTOs)

El módulo aísla la lógica compleja de negocio en la capa de Acciones, promoviendo reutilización:

### `ExportAuditLogsAction`

- **Ubicación:** `App\Modules\AuditModule\Actions\ExportAuditLogsAction`
- **Responsabilidad:** Extracción segura y ordenada de logs basada en filtros.
- **Entrada:** `AuditLogExportDTO` (Contiene propiedades tipadas para `$search`, `$action`, `$entityType`, `$dateFrom`, `$dateTo`).
- **Implementación:**
  1. Inicia una transacción de sólo lectura `DB::transaction()`.
  2. Inicializa el Query Builder de `AuditLog`.
  3. Carga ansiosamente (`with('user')`) la relación del usuario para evitar N+1 queries al generar el CSV.
  4. Pasa los filtros del DTO al scope `filter()`.
  5. Ordena descendentemente por `created_at`.
  6. Devuelve un `Collection<AuditLog>`.

### (Futuro/Proyectado) `LogAuditAction`

- Aunque actualmente la lógica reside en `AuditLog::log()`, en una arquitectura DDD estricta, la persistencia puede delegarse a un `LogAuditAction` que procese un `AuditLogCreateDTO` que extraiga la dependencia directa de la función global `request()` y `auth()`, facilitando pruebas unitarias (Testing).

---

## 9. Endpoints o Rutas Detalladas (Livewire / Web / API)

El módulo expone sus funcionalidades primarias a través de Livewire y FluxUI:

- **Ruta Livewire Principal:**
  - `GET /admin/audit-logs`
  - **Componente asociado:** `AuditModule\Livewire\AuditLogIndex`
  - **Detalles:** Contiene una tabla de datos (Data Table) renderizada por FluxUI. Incluye campos de entrada en la parte superior acoplados mediante `wire:model` al DTO de filtros.
- **Flujo de Exportación:**
  - Invocado internamente desde el componente Livewire (`wire:click="export"`). Llama a `ExportAuditLogsAction` y retorna una respuesta de transmisión (Stream) binaria utilizando el helper de respuesta de descarga de Laravel para no saturar memoria.

---

## 10. Dependencias con otros Módulos

El AuditModule se encuentra en el nivel fundacional de la arquitectura, actuando como infraestructura transversal:

- **Dependencia Estricta (Downstream):** Depende de `CoreModule` (Específicamente el modelo `User`). Requiere este módulo para resolver las relaciones `user_id` y las credenciales del ejecutor.
- **Inversión de Dependencia (Upstream):** Es consumido por **todos** los demás módulos del sistema (`PersonnelModule`, `OperationsModule`, etc.). Sin embargo, la dependencia se invierte: El AuditModule no conoce la existencia del módulo de personal, sino que el módulo de personal consume los servicios del AuditModule al invocar `AuditLog::log($this, 'updated')` o al usar un Trait de autodescubrimiento, manteniendo al AuditModule limpio de reglas de negocio específicas ajenas.

---

## 11. Estructura de Carpetas

```tree
app/Modules/AuditModule
├── Actions
│   └── ExportAuditLogsAction.php
├── Console
│   └── Commands
│       └── AuditPruneCommand.php
├── DTOs
│   └── AuditLogExportDTO.php
├── Http
│   └── Controllers
│       └── AuditExportController.php
├── Listeners
│   ├── AuditLeaveRequestCreatedListener.php
│   ├── AuditLeaveRequestDecisionListener.php
│   ├── AuditShiftSwapApprovedListener.php
│   └── AuditWeeklySchedulePublishedListener.php
├── Livewire
│   └── ListAuditLogs.php
├── Models
│   └── AuditLog.php
├── Policies
│   └── AuditLogPolicy.php
├── Providers
│   └── ModuleServiceProvider.php
├── Resources
│   └── Views
│       └── livewire
│           └── list-audit-logs.blade.php
└── Routes
    └── web.php
```
