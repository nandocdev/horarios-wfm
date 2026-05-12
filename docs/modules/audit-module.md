# Especificación Completa: AuditModule

## 🎯 Propósito del Módulo

El `AuditModule` es el sistema centralizado de trazabilidad de la plataforma. Su objetivo es proporcionar una evidencia inmutable de "quién hizo qué, cuándo y desde dónde", capturando estados de datos antes y después de cada cambio crítico para cumplir con estándares de cumplimiento (compliance) y facilitar la depuración técnica.

---

## 🚀 Catálogo Completo de Funcionalidades

### 1. Motor de Captura Automática (Event-Driven)

El módulo "escucha" activamente eventos en otros dominios para registrar cambios de estado sin que el desarrollador del módulo original tenga que escribir código de auditoría.

- **Auditoría de Horarios:**
  - `WeeklySchedulePublished`: Registra el momento exacto en que una malla de turnos se hace visible para la operación.
- **Auditoría de Permisos (Leave Requests):**
  - `LeaveRequestCreated`: Captura la creación de solicitudes de vacaciones o permisos.
  - `LeaveRequestDecision`: Registra la decisión final (Aprobado/Rechazado), quién la tomó y el estado final de la solicitud.
- **Auditoría de Intercambios (Shift Swaps):**
  - `ShiftSwapApproved`: Registra la culminación exitosa de un intercambio de turno entre dos colaboradores.

### 2. Helper de Auditoría Manual (CRUD Audit)

Proporciona una interfaz estática en el modelo `AuditLog` para auditoría quirúrgica de modelos Eloquent:

- **`AuditLog::log(Model $model, string $action)`**:
  - **Acción `created`**: Almacena un snapshot completo del modelo recién creado en el campo `after`.
  - **Acción `updated`**: Realiza una comparación automática; guarda los valores originales en `before` y los nuevos en `after`.
  - **Acción `deleted`**: Guarda el estado final del objeto antes de su eliminación en `before`.
  - **Metadatos Automáticos**: En cada llamada, el motor detecta automáticamente la `IP_ADDRESS` de la petición y el `USER_ID` autenticado.

### 3. Centro de Monitoreo (Interfaz Administrativa)

Componente Livewire de alto rendimiento (`audit.list-audit-logs`) con las siguientes capacidades:

- **Búsqueda Global:** Filtrado en tiempo real por tipo de entidad (clase PHP), nombre de la acción o dirección IP.
- **Filtros Avanzados:**
  - Por **Acción Específica** (ej: ver solo `updated`).
  - Por **Tipo de Entidad** (ej: ver solo cambios en el modelo `Employee`).
  - Por **Rango de Fechas**: Selectores de `Fecha Desde` y `Fecha Hasta`.
- **Navegación y UX:**
  - **Paginación Dinámica**: Selector de registros por página (10, 20, 50, 100).
  - **Sincronización de URL**: Los filtros se mantienen en la URL, permitiendo compartir vistas filtradas específicas entre administradores.
  - **Reset Inteligente**: Al cambiar un filtro, la paginación vuelve automáticamente a la página 1.

### 4. Sistema de Exportación Profesional

Capacidad de extraer datos para auditorías externas o análisis en herramientas de BI:

- **Formatos Soportados**: CSV y JSON.
- **Integridad de Filtros**: La exportación respeta exactamente los mismos filtros activos en la pantalla de monitoreo.
- **Transaccionalidad**: El proceso de exportación se ejecuta dentro de una transacción de base de datos para asegurar la consistencia de los registros obtenidos.

### 5. Capa de Seguridad y Acceso

Protección estricta de la información sensible de auditoría:

- **`AuditLogPolicy`**:
  - `viewAny`: Restringe el acceso a la lista completa solo a usuarios con el permiso `audit.view` o el rol `admin`.
  - `view`: Controla la visualización del detalle de un registro específico.
  - `export`: Permiso independiente (`audit.export`) para autorizar la descarga de datos.
- **Protección de Rutas**: Todas las rutas de auditoría están bajo el prefijo `admin/audit` y protegidas por los middlewares `auth` y `verified`.

---

## 🛠 Detalles Técnicos de la Implementación

### Estructura de Datos (`audit_logs`)

| Campo         | Tipo      | Descripción                                                                   |
| ------------- | --------- | ----------------------------------------------------------------------------- |
| `entity_type` | String    | Clase completa del modelo (ej: `App\Modules\EmployeesModule\Models\Employee`) |
| `entity_id`   | BigInt    | ID del registro auditado                                                      |
| `action`      | String    | Nombre de la acción (created, updated, deleted, login, etc.)                  |
| `before`      | JSON      | Estado previo de los datos (null en creaciones)                               |
| `after`       | JSON      | Estado posterior de los datos (null en eliminaciones)                         |
| `ip_address`  | String    | IP desde la cual se originó el cambio                                         |
| `user_id`     | ForeignID | Usuario que realizó la acción                                                 |

### Registro de Listeners (Service Provider)

```php
// app/Modules/AuditModule/Providers/ModuleServiceProvider.php
Event::listen(WeeklySchedulePublished::class, AuditWeeklySchedulePublishedListener::class);
Event::listen(LeaveRequestCreated::class, AuditLeaveRequestCreatedListener::class);
Event::listen(LeaveRequestDecision::class, AuditLeaveRequestDecisionListener::class);
Event::listen(ShiftSwapApproved::class, AuditShiftSwapApprovedListener::class);
```

---

## ⚠️ [RIESGOS TÉCNICOS]

1. **Sensibilidad de Datos**: El sistema captura snapshots completos via `toArray()`. Si un modelo no tiene correctamente definidos los campos `$hidden` (ej: contraseñas, tokens), estos podrían quedar expuestos en la bitácora de auditoría.
2. **Crecimiento Asintótico**: En una operación de 1,000 agentes, una semana de cambios de turnos y estados puede generar >50,000 registros. Es vital monitorear el tamaño del índice de la tabla `audit_logs`.
3. **Dependencia de Request**: El helper `AuditLog::log` depende de la fachada `request()` para la IP. En contextos de consola (Jobs/Commands), la IP será `null` o la IP del servidor.

---

## 📋 Guía de Extensión

### ¿Cómo auditar un nuevo proceso de negocio?

1. **Paso 1**: Identificar el Evento en el módulo origen (ej: `InventoryUpdated`).
2. **Paso 2**: Crear un Listener en `AuditModule\Listeners\AuditInventoryUpdateListener`.
3. **Paso 3**: Implementar la lógica usando `AuditLog::create([...])`.
4. **Paso 4**: Registrar el vínculo en `ModuleServiceProvider`.
