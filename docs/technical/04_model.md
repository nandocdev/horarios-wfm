CAJA DE SEGURO SOCIAL --- CALL CENTER

Sistema de Gestión de Horarios (WFM)

**Modelo de Datos (alineado al DDL)**

_RUP --- Diagrama de Entidad-Relación (ERD)_
Versión: v1.1
Fecha: Febrero 2026
Stack: PHP 8.3 · Laravel 12 · PostgreSQL 16

> **Fuente de verdad**
>
> Este documento está sincronizado con `docs/00-overview/ddl.md`.
> Si existe conflicto entre ambos, prevalece `ddl.md`.

---

## 1) Principios de diseño vigentes

- Separación `users` / `employees`.
- Jerarquía operativa por `employees.parent_id` (adjacency list).
- Seguridad basada en Spatie Permission:
    - `roles`, `permissions`, `role_has_permissions`, `model_has_roles`, `model_has_permissions`.
- Organización tipada por catálogos y relaciones explícitas (`directorates` → `departments` → `positions`).
- Sin `org_units` en el modelo vigente.

---

## 2) Seguridad y acceso

### 2.1 Tabla: users

Campos clave:

- `id`, `name`, `email`, `password`
- `is_active`, `email_verified_at`, `last_login_at`
- `remember_token`, `deleted_at`, `force_password_change`

Notas:

- `email` único.
- No existe columna `username` en `users`.

### 2.2 Tabla: roles

Campos clave:

- `id`, `name`, `guard_name`, `code`, `hierarchy_level`
- `created_at`, `updated_at`

Notas:

- Unicidad en (`name`, `guard_name`).

### 2.3 Tabla: permissions

Campos clave:

- `id`, `name`, `guard_name`
- `created_at`, `updated_at`

Notas:

- Unicidad en (`name`, `guard_name`).

### 2.4 Tablas pivote de autorización

- `role_has_permissions` (PK: `permission_id`, `role_id`)
- `model_has_roles` (PK: `role_id`, `model_id`, `model_type`)
- `model_has_permissions` (PK: `permission_id`, `model_id`, `model_type`)

---

## 3) Organización y empleados

### 3.1 Catálogos organizacionales

- `directorates`
- `departments` (FK a `directorates`)
- `positions` (FK a `departments`)
- `teams`
- `employment_statuses`

### 3.2 Ubicación geográfica

- `provinces`
- `districts` (FK a `provinces`)
- `townships` (FK a `districts`)

### 3.3 Tabla: employees (entidad central)

Campos clave:

- Identidad laboral: `employee_number`, `username`, `first_name`, `last_name`, `email`
- Datos personales: `birth_date`, `gender`, `blood_type`, `phone`, `mobile_phone`, `address`
- Organización: `township_id`, `department_id`, `position_id`, `employment_status_id`
- Jerarquía: `parent_id`
- Usuario: `user_id`
- Laboral: `hire_date`, `salary`, `is_active`, `is_manager`, `metadata`
- Auditoría temporal: `created_at`, `updated_at`

Restricciones destacadas:

- `employees_employee_number_unique`
- `employees_username_unique`
- `employees_email_unique`
- `employees_parent_not_self` (`parent_id` no puede ser igual a `id`)
- FK de `parent_id` hacia `employees.id`

### 3.4 Otras tablas de empleado

- `team_members` (historial de pertenencia a equipos)
- `employee_positions` (historial de cargos/FTE)
- `employee_dependents`
- `employee_diseases`
- `employee_disabilities`

---

## 4) Horarios e intradía

### 4.1 Plantillas y programación semanal

- `schedules`
    - Incluye `lunch_minutes`, `break_minutes`, `total_minutes`, `is_active`.
- `weekly_schedules`
    - Control semanal con estado (`draft`/`published`).

### 4.2 Asignaciones y descansos

- `weekly_schedule_assignments`
    - Relaciona semana, empleado y horario.
    - Unicidad por (`weekly_schedule_id`, `employee_id`).
- `break_templates`
- `employee_break_overrides`

### 4.3 Actividades intradía

- `intraday_activities`
- `intraday_activity_assignments`

---

## 5) Incidencias, permisos y cambios de turno

### 5.1 Incidencias

- `incident_types`
    - Catálogo de incidencias operativas.
- `attendance_incidents`
    - Registro de incidencias por empleado y fecha.

### 5.2 Permisos

- `leave_requests`
    - Tipos `partial`/`full` y estados `pending|approved|rejected|cancelled`.
- `leave_request_approvals`
    - Flujo de aprobación por pasos.

### 5.3 Cambios de turno

- `shift_swap_requests`
    - Solicitudes entre empleados con estado de flujo.
- `shift_swap_approvals`
    - Aprobaciones por pasos.

---

## 6) Auditoría y soporte operativo

### 6.1 Auditoría

- `audit_logs`
    - `entity_type`, `entity_id`, `action`, `before`, `after`, `ip_address`, `user_id`.
    - Índices: por entidad, usuario y fecha.

### 6.2 Soporte de plataforma

- `cache`, `cache_locks`, `sessions`, `jobs`, `job_batches`, `failed_jobs`, `notifications`, `password_reset_tokens`, `migrations`.

---

## 7) Diferencias importantes frente a versiones previas

- No se usa `org_units`.
- No se usan tablas `employee_schedules` / `team_schedules`; el esquema vigente usa `weekly_schedule_assignments` y `team_members`.
- No se usan tablas `exception_types` / `exceptions`; el esquema vigente usa `incident_types` y `attendance_incidents`.
- No se usa `shift_change_requests`; el esquema vigente usa `shift_swap_requests`.
- No existe tabla `attendances`; el registro operativo vigente está en `attendance_incidents`.

---

## 8) Resumen relacional de alto nivel

- `users` 1 : N `employees` (por `employees.user_id`, nullable para casos sin acceso).
- `employees` N : 1 `positions`, `departments`, `employment_statuses`, `townships`.
- `employees` N : 0..1 `employees` (cadena de mando por `parent_id`).
- `roles` N : M `permissions` (vía `role_has_permissions`).
- Modelos del sistema N : M `roles` / `permissions` (vía `model_has_roles`, `model_has_permissions`).
- `weekly_schedules` 1 : N `weekly_schedule_assignments`.
- `employees` 1 : N `leave_requests`, `attendance_incidents`, `shift_swap_requests`.

---

## 9) Regla de mantenimiento documental

Al modificar estructura en migraciones o en `ddl.md`, este documento debe actualizarse en el mismo cambio para evitar desalineación semántica.
