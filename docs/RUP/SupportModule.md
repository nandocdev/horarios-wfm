# Especificación Técnica Detallada: SupportModule (Módulo de Soporte Base y Utilidades Transversales)

> Documento RUP Centrado en Arquitectura
> **Módulo:** SupportModule
> **Ruta:** `app/Modules/SupportModule`

## 1. Resumen Ejecutivo y Propósito del Módulo

El **SupportModule** es un módulo de infraestructura (*Cross-cutting Concern*) diseñado para albergar herramientas, utilidades y servicios que son transversales a toda la plataforma, pero que no pertenecen a la lógica de negocio de ningún dominio específico.

Actualmente, su responsabilidad principal es la **Auditoría de Datos (Audit Logging)**, proporcionando un mecanismo estandarizado para rastrear mutaciones (`created`, `updated`, `deleted`) en cualquier entidad de Eloquent a lo largo del monolito modular. Se proyecta que en el futuro albergue integraciones de telemetría (Logs de errores, métricas de rendimiento) y utilidades de caché compartidas.

---

## 2. Casos de Uso Detallados

Al ser un módulo técnico, sus "Actores" suelen ser otros módulos (El Sistema):

### CU-SM-01: Auditoría Transversal de Mutaciones (Audit Log)

- **Actor:** Sistema (Observers de otros módulos).
- **Descripción:** Capturar y persistir el estado "Antes" y "Después" de cualquier registro alterado.
- **Flujo Principal:**
  1. Un usuario edita un registro (ej. Un `Employee` en el `PersonnelModule`).
  2. El `EmployeeObserver` (ubicado en PersonnelModule) intercepta el evento de guardado.
  3. El Observer invoca estáticamente el método `AuditLog::log($model, 'updated')` provisto por este `SupportModule`.
  4. El método captura mágicamente el `$model->getOriginal()` (estado previo) y el `$model->toArray()` (estado nuevo), serializándolos como JSON.
  5. Se inserta un registro en la tabla `audit_logs` vinculando el ID del usuario autenticado (`Auth::id()`) y su IP (`Request::ip()`).

### CU-SM-02: Consulta Forense de Cambios (Proyectado)

- **Actor:** Oficial de Seguridad / Administrador IT.
- **Descripción:** Rastrear quién realizó una modificación fraudulenta o errónea.
- **Flujo Principal:**
  1. El Administrador ingresa a una vista genérica de "Visor de Auditoría".
  2. Filtra por un `entity_type` (ej. `App\Modules\WfmModule\Models\Schedule`) y un rango de fechas.
  3. El sistema muestra la diferencia de los JSONs (`before` vs `after`) permitiendo ver exactamente qué campos alteró el usuario.

---

## 3. Requerimientos Funcionales (RF)

- **RF-SM-01 (Auditoría Polimórfica):** El modelo `AuditLog` debe ser capaz de relacionarse con cualquier modelo de la base de datos sin restricciones de llave foránea dura. Se logra guardando el `entity_type` (FQN de la clase) y el `entity_id`.
- **RF-SM-02 (Captura de Contexto):** Todo log de auditoría debe capturar implícitamente la dirección IP (`Request::ip()`) y el ID del usuario autenticado de la sesión actual, sin que el módulo cliente tenga que pasarlos explícitamente por parámetro.

---

## 4. Requerimientos No Funcionales (RNF)

- **RNF-SM-01 (Optimización de Almacenamiento):** Dado que la tabla `audit_logs` crecerá de forma exponencial, las columnas `before` y `after` deben utilizar el tipo nativo `JSONB` en PostgreSQL. Esto permite compresión nativa y búsquedas de texto eficientes dentro de los cambios.
- **RNF-SM-02 (Desacoplamiento - Fire and Forget):** (Recomendación Arquitectónica) La inserción de los logs no debería bloquear la transacción principal del usuario. A futuro, `AuditLog::log()` debería despachar un Job a la cola en lugar de escribir de forma síncrona, previniendo latencia en las actualizaciones de negocio.

---

## 5. Modelos de Datos Detallados

| Atributo | Tipo / Cast | Descripción y Lógica de Negocio |
| :--- | :--- | :--- |
| **Entidad: `AuditLog`** | | **Registro Histórico Genérico** |
| `id` | `bigint` (PK)| Identificador de la traza. |
| `user_id` | `integer` (FK)| Autor del cambio (Relación a `CoreModule\User`). |
| `entity_type` | `string` | FQN del modelo afectado (ej. `App\Modules\PersonnelModule\Models\Employee`). |
| `entity_id` | `integer`/`uuid`| Llave primaria del modelo afectado. |
| `action` | `string` | Acción gatillada (`created`, `updated`, `deleted`, `restored`). |
| `before` | `json` (`JSONB`) | Snapshot del modelo antes de guardar (null si es *created*). |
| `after` | `json` (`JSONB`) | Snapshot del modelo después de guardar (null si es *deleted*). |
| `ip_address` | `string` | Para rastreo de seguridad. |

---

## 6. Roles y Permisos (Policies)

- **Auditoría Global (`system.audit.view`):** Este permiso debe ser extremadamente restrictivo, limitado únicamente al rol `SuperAdmin` o `SecurityOfficer`, ya que los JSON guardados en la tabla de auditoría podrían revelar información sensible en texto plano (ej. salarios modificados, enfermedades).

---

## 7. Eventos, Listeners y Notificaciones

Al ser el receptor final de los Observers, el SupportModule **no emite** eventos para evitar *Loops* infinitos (Ej. si emitiera un evento de guardado de auditoría, que a su vez sea auditado). Actúa como un sumidero (Sink) silencioso.

---

## 8. Servicios y Acciones Detallados

- **`AuditLog::log(Model $model, string $action)`:**
  Un método estático utilitario dentro del mismo modelo que encapsula toda la complejidad de extraer los cambios originales (`$model->getOriginal()`) y el estado actual (`$model->toArray()`), asegurando que todos los módulos invoquen el log con una sola línea de código estandarizada.

---

## 9. Endpoints o Rutas Detalladas

Actualmente, el módulo carece de interfaz de usuario. Opera puramente a nivel de consola (Backend).
Se proyecta:

- **`GET /admin/support/audit`** -> Componente Livewire genérico para paginar y buscar `AuditLogs` filtrando por modelo o por ID de usuario.

---

## 10. Dependencias con otros Módulos

El SupportModule es un **Proveedor Transversal Puro**:

- **Es consumido por (Dependencia Upstream):** Absolutamente todos los demás módulos de la aplicación (Personnel, Helpdesk, Operations, etc.) que requieran auditar entidades sensibles.
- **Dependencia Downstream (`CoreModule`):** Requiere imperativamente al modelo `User` para la llave foránea `user_id` de quién causó la acción.

---

## 11. Estructura de Carpetas

```tree
app/Modules/SupportModule
├── Models
│   └── AuditLog.php
├── Providers
│   └── ModuleServiceProvider.php
└── Routes
    └── web.php
```

---

*Documento técnico profundo generado bajo lineamientos de arquitectura iterativa RUP.*
