# Modelo de Datos — HorariosWFM

> Documento del Modelo de Datos
> Sistema WFM — Call Center de la Caja de Seguro Social de Panamá
> Versión 2.0 — Julio 2026

---

## 1. Convenciones

| Convención                  | Estándar                                                                                                      |
| --------------------------- | ------------------------------------------------------------------------------------------------------------- |
| **Motor de BD**             | PostgreSQL 16 (producción), SQLite (test)                                                                     |
| **Nombrado de tablas**      | `snake_case` plural (`employees`, `weekly_schedule_assignments`)                                              |
| **Nombrado de columnas**    | `snake_case` (`first_name`, `week_start_date`)                                                                |
| **LLaves primarias**        | `$table->id()` → `BIGINT UNSIGNED AUTO_INCREMENT` (PK física). ULIDs via `HasUlids` trait (identidad lógica). |
| **LLaves foráneas**         | `$table->foreignId('employee_id')->constrained()` — cascada restrictiva por defecto                           |
| **Timestamps**              | `created_at` + `updated_at` en todas las tablas. `timestamp(0) with time zone` en PostgreSQL.                 |
| **Soft Deletes**            | `deleted_at` nullable donde aplica (`employees`, `quality_evaluations`, `users`)                              |
| **JSON**                    | `jsonb` en PostgreSQL para datos semiestructurados                                                            |
| **Rangos**                  | `tstzrange` (PostgreSQL) con índice GiST para exclusión de solapamientos                                      |
| **UNLOGGED**                | Tabla `agent_realtime_states` sin WAL para maximizar throughput de escritura                                  |
| **declare(strict_types=1)** | Exigido en todo archivo PHP del proyecto                                                                      |

### 1.1 ULID vs PK física

- La migración de BD crea `$table->id()` que es `BIGINT UNSIGNED AUTO_INCREMENT`.
- `BaseModel` aplica `HasUlids` trait, `$incrementing = false`, `$keyType = 'string'`.
- El ULID se genera en PHP por Eloquent y se almacena como `VARCHAR(26)` en la columna `id`.
- Los modelos QualityModule (`quality_*`) y algunos modelos (`attendance_incidents`, `employee_import_batches`, `channels`) usan ULID como PK primaria explícita.

### 1.2 N/A vs SQLite

Las migraciones verifican `DB::getDriverName() === 'pgsql'` antes de aplicar features específicas de PostgreSQL (rangos, `UNLOGGED`, `btree_gist`). En SQLite se usa un tipo estándar equivalente.

---

## 2. Diagrama Entidad-Relación (Simplificado)

```
CoreModule                          OrganizationModule           GeoModule
┌──────────┐                        ┌──────────────┐            ┌──────────┐
│   users  │──1:1──>│ employees    │ directorates │──1:N──>│ departments │──1:N──>│   positions  │
└──────────┘        └──────────────┘ └──────────────┘                    └──────────────┘
     │                                                                         provinces──1:N──>districts──1:N──>townships
     │ hasRoles
     ▼
┌──────────┐     ┌──────────────┐
│   roles  │<──M:N──>│ permissions  │
└──────────┘     └──────────────┘

PersonnelModule (Employee-centric)
┌──────────────────────────────────────────────────────────────────┐
│                           employees                                │
├──────────────────────────────────────────────────────────────────┤
│ employee_number, username, cisco_username, first_name, last_name │
│ email, birth_date, gender, phone, address                         │
│ township_id ──> GeoModule::Township                               │
│ department_id ──> OrganizationModule::Department                  │
│ position_id ──> OrganizationModule::Position                      │
│ employment_status_id ──> EmploymentStatus                         │
│ team_id ──> Team                                                  │
│ parent_id ──> employees (manager)                                 │
│ user_id ──> CoreModule::User                                      │
│ metadata (jsonb)                                                   │
└──────────────────────────────────────────────────────────────────┘
     │ 1:N             │ 1:N              │ 1:N             │ 1:N
     ▼                 ▼                  ▼                 ▼
┌──────────┐  ┌───────────────┐  ┌────────────┐  ┌────────────────┐
│team_members│  │employee_positions│ │dependents│  │employee_diseases│
└──────────┘  └───────────────┘  └────────────┘  └────────────────┘
     │                                                 │
     ▼                                                 ▼
┌──────────┐                                ┌──────────────┐
│   teams  │                                │ disease_types │
│supervisor_id─>employees                   └──────────────┘
└──────────┘

WfmModule (Schedule-centric)
┌─────────────────┐
│    schedules     │──< plantilla de horario
└─────────────────┘

┌─────────────────┐       1:N              ┌──────────────────────────┐
│ weekly_schedules │──>│ weekly_schedule_assignments │
└─────────────────┘        │──< employee_id ──> Employee
     │                     │──< schedule_id ──> Schedule
     │                     │──< swap_request_id ──> ShiftSwapRequest
     │ team assignments     └──────────────────────────┘
     │
     ▼
┌─────────────────────┐
│ weekly_team_assignments│──< team_id ──> Team
└─────────────────────┘

┌──────────────────┐       ┌───────────────────────┐
│  leave_requests   │──1:N──>│ leave_request_approvals│
│ employee_id─>Employee│       │ approver_id─>Employee   │
└──────────────────┘       └───────────────────────┘

┌────────────────────┐     ┌──────────────────────┐
│ shift_swap_requests │──1:N─>│ shift_swap_approvals │
│ requester─>Employee │       │ approver_id─>Employee│
│ recipient─>Employee │     └──────────────────────┘
└────────────────────┘

┌─────────────────────┐     ┌──────────────────────┐
│ schedule_exceptions  │─M:1─>│ absence_reason_codes │
│ employee─>Employee   │     └──────────────────────┘
│ origin (MorphTo)     │
└─────────────────────┘

┌──────────────────────────────┐
│   approved_intraday_periods   │──< team_id ──> Team
│   max_slots control           │──< activity_definition_id ──> ScheduledActivityDefinition
└──────────────────────────────┘
     │ 1:N
     ▼
┌──────────────────┐     ┌──────────────────────────┐
│intraday_activities│──M:1─>│   activity_types          │
│ time_range tstzrange│     └──────────────────────────┘
│ employee─>Employee │
└──────────────────┘

┌───────────────────────┐
│  temporal_assignments │── employee─>Employee, supervisor─>Employee, team─>Team
└───────────────────────┘

ConnectModule (Cisco Integration)
┌──────────┐     ┌──────────────┐
│ channels │──1:N─>│ call_queues │──1:N──> case_subtypes
└──────────┘     └──────┬───────┘
                        │ 1:N
                        ▼
┌─────────────────────────────────────────────────┐
│                  call_records                     │
│ employee_id─>Employee, queue_id─>CallQueue       │
│ case_subtype_id─>CaseSubtype                      │
│ citizen_identifier (VARCHAR 12 — cédula)          │
└─────────────────────────────────────────────────┘

┌────────────────────────┐  ┌────────────────────────┐
│ agent_realtime_states   │  │ agent_call_performance │
│ (UNLOGGED, no timestamps)│  │ employee_id─>Employee  │
│ employee_id─>Employee    │  └────────────────────────┘
└────────────────────────┘
┌────────────────────────┐  ┌─────────────────────┐
│ agent_state_transitions │  │ csq_realtime_stats  │
│ employee_id─>Employee   │  │ (sin FK, datos CUIC)│
└────────────────────────┘  └─────────────────────┘
┌────────────────────┐
│   chat_records      │
│ employee_id─>Employee│
└────────────────────┘

OperationsModule
┌────────────────────────┐  ┌────────────────────┐
│   agent_daily_metrics   │  │attendance_incidents │
│ employee_id─>Employee   │  │ (ULID PK)           │
│ queue_distribution jsonb│  │ employee─>Employee  │
└────────────────────────┘  │ type─>IncidentType  │
                            └────────────────────┘
┌────────────────┐  ┌────────────────┐
│ incident_types │  │  agent_states  │
└────────────────┘  └────────────────┘

QualityModule (ULIDs en todas las entidades)
┌──────────────┐     ┌─────────────────────┐     ┌─────────────────────┐
│quality_queues│──1:N─>│quality_queue_criteria│──M:1─>│quality_criteria      │
└──────────────┘     └─────────────────────┘     └──────────┬──────────┘
                                                            │ 1:N
                                                            ▼
┌───────────────────┐                              ┌──────────────────────┐
│quality_evaluations│──1:N─>│quality_evaluation_scores│──M:1─>│quality_criteria_versions│
│ employee─>Employee │     └──────────────────────────┘     └──────────────────────┘
│ evaluator─>Employee│     ┌──────────────────────────┐
│ queue─>QualityQueue │     │quality_evaluation_red_flags│
└────────┬──────────┘     └──────────────────────────┘
         │ 1:1                        │ 1:N
         ▼                            ▼
┌────────────────┐        ┌──────────────────────┐
│quality_feedback│        │ quality_red_flag_criteria│
└────────────────┘        └──────────────────────┘
┌────────────────────────┐
│ quality_calibration_log│
└────────────────────────┘

CommunicationsModule
┌──────┐     ┌──────────┐     ┌───────────┐     ┌──────────┐
│ news │──1:N─>│comments │     │ polls ├──1:N─>│poll_responses│
└──────┘     └──────────┘     └──────────┘     └──────────┘
┌──────────┐     ┌──────────┐     ┌───────────────────┐
│shoutouts │──1:N─>│reactions│     │categories (polymorphic)│
└──────────┘     └──────────┘     └───────────────────┘
┌────────────────────────────────────────┐
│          notifications (Laravel)        │
│ user_id, title, message, is_read       │
└────────────────────────────────────────┘
┌────────────────────────────────────────┐
│          mentions (polymorphic)         │
└────────────────────────────────────────┘

HelpdeskModule
┌──────────────────┐     ┌──────────────────────────┐
│ helpdesk_categories│──1:N─>│ helpdesk_tickets │──1:N─>│ helpdesk_ticket_comments
└──────────────────┘     └──────────────────────────┘

KnowledgeModule
┌──────────────────┐     ┌──────────────────────┐     ┌──────────────────────┐
│knowledge_categories│──1:N─>│  knowledge_articles  │──1:N─>│knowledge_article_versions│
└──────────────────┘     └─────────┬────────────┘     └──────────────────────┘
                                   │   M:N
                                   ▼
                    ┌─────────────────────────────────┐
                    │ knowledge_queues (pivot: article_queue)│
                    │ knowledge_tags (pivot: article_tag)   │
                    └─────────────────────────────────┘

FilesystemModule
┌─────────┐     ┌───────┐     ┌──────────────┐
│ folders │──1:N─>│ files │──1:N─>│ file_shares  │
└─────────┘     └───────┘     └──────────────┘
┌────────────────┐
│ storage_quotas │ (polymorphic)
└────────────────┘
```

---

## 3. Módulo Core — CoreModule

### 3.1 users

Tabla de autenticación principal. Extiende `Authenticatable`.

| Columna                   | Tipo            | Restricciones      | Notas               |
| ------------------------- | --------------- | ------------------ | ------------------- |
| id                        | BIGINT UNSIGNED | PK, Auto-increment |                     |
| name                      | VARCHAR(255)    | NOT NULL           |                     |
| email                     | VARCHAR(255)    | NOT NULL, UNIQUE   |                     |
| email_verified_at         | TIMESTAMP TZ    | NULLABLE           |                     |
| password                  | VARCHAR(255)    | NOT NULL           | Hashed              |
| two_factor_secret         | TEXT            | NULLABLE           | Cifrado por Fortify |
| two_factor_recovery_codes | TEXT            | NULLABLE           | Cifrado por Fortify |
| two_factor_confirmed_at   | TIMESTAMP TZ    | NULLABLE           |                     |
| is_active                 | BOOLEAN         | DEFAULT true       |                     |
| last_login_at             | TIMESTAMP TZ    | NULLABLE           |                     |
| force_password_change     | BOOLEAN         | DEFAULT false      |                     |
| remember_token            | VARCHAR(100)    | NULLABLE           |                     |
| deleted_at                | TIMESTAMP TZ    | NULLABLE           | SoftDeletes         |

**Relaciones:**
- `employee()` → `HasOne` Employee (1:1)
- `roles()` → `MorphToMany` via `model_has_roles`
- `permissions()` → `MorphToMany` via `model_has_permissions`

### 3.2 roles

| Columna         | Tipo            | Notas                             |
| --------------- | --------------- | --------------------------------- |
| id              | BIGINT UNSIGNED | PK                                |
| name            | VARCHAR(255)    |                                   |
| guard_name      | VARCHAR(255)    | `web`                             |
| code            | VARCHAR(50)     | OP, SUP, COOR, JEF, WFM, DIR, ADM |
| hierarchy_level | INTEGER         | 1-99                              |

7 roles: operator(1), supervisor(2), coordinator(3), chief(4), wfm(5), director(6), admin(99).

### 3.3 permissions

| Columna    | Tipo            | Notas                |
| ---------- | --------------- | -------------------- |
| id         | BIGINT UNSIGNED | PK                   |
| name       | VARCHAR(255)    | `{recurso}.{accion}` |
| guard_name | VARCHAR(255)    | `web`                |

130+ permisos definidos en `RolesAndPermissionsSeeder`.

### 3.4 app_settings

| Columna     | Tipo         | Notas  |
| ----------- | ------------ | ------ |
| key         | VARCHAR(255) | UNIQUE |
| value       | JSONB        |        |
| description | VARCHAR(255) |        |

### 3.5 Tablas del sistema

| Tabla                                | Propósito                                   |
| ------------------------------------ | ------------------------------------------- |
| `sessions`                           | Sesiones de usuario (database driver)       |
| `cache`, `cache_locks`               | Caché en BD (driver array en test)          |
| `jobs`, `job_batches`, `failed_jobs` | Cola (Redis en producción, database en dev) |
| `password_reset_tokens`              | Reset de contraseña (Fortify)               |
| `personal_access_tokens`             | API tokens (Sanctum)                        |
| `migrations`                         | Control de versiones de migraciones         |

---

## 4. Módulo Organización — OrganizationModule

### 4.1 directorates

| Columna     | Tipo            | Notas |
| ----------- | --------------- | ----- |
| id          | BIGINT UNSIGNED | PK    |
| name        | VARCHAR(255)    |       |
| description | TEXT            |       |
| is_active   | BOOLEAN         |       |

### 4.2 departments

| Columna        | Tipo            | FK                    |
| -------------- | --------------- | --------------------- |
| id             | BIGINT UNSIGNED | PK                    |
| directorate_id | BIGINT UNSIGNED | FK → directorates(id) |
| name           | VARCHAR(255)    |                       |
| description    | TEXT            |                       |

### 4.3 positions

| Columna       | Tipo            | FK                   |
| ------------- | --------------- | -------------------- |
| id            | BIGINT UNSIGNED | PK                   |
| department_id | BIGINT UNSIGNED | FK → departments(id) |
| name          | VARCHAR(255)    |                      |
| position_code | VARCHAR(255)    |                      |
| description   | TEXT            |                      |
| salary        | DECIMAL(10,2)   |                      |
| is_active     | BOOLEAN         |                      |

---

## 5. Módulo Geográfico — GeoModule

### 5.1 provinces → districts → townships

Jerarquía geográfica de Panamá (10 provincias, ~80 distritos, ~700 corregimientos).

`townships` apuntado por `employees.address`.

| Tabla     | Columnas clave        | FK                 |
| --------- | --------------------- | ------------------ |
| provinces | id, name              |                    |
| districts | id, province_id, name | FK → provinces(id) |
| townships | id, district_id, name | FK → districts(id) |

---

## 6. Módulo Personal — PersonnelModule

### 6.1 employees

Tabla central del sistema. 35 columnas.

| Columna               | Tipo            | FK / Notas                         |
| --------------------- | --------------- | ---------------------------------- |
| id                    | BIGINT UNSIGNED | PK                                 |
| employee_number       | VARCHAR(20)     | Número de empleado CSS             |
| username              | VARCHAR(255)    | Usuario de red                     |
| cisco_username        | VARCHAR(255)    | Login Cisco UCCX                   |
| first_name, last_name | VARCHAR(255)    |                                    |
| email                 | VARCHAR(255)    |                                    |
| birth_date            | DATE            |                                    |
| gender                | VARCHAR(10)     | M / F / O                          |
| blood_type            | VARCHAR(5)      |                                    |
| phone, mobile_phone   | VARCHAR(20)     |                                    |
| address               | TEXT            |                                    |
| township_id           | BIGINT UNSIGNED | FK → townships(id)                 |
| department_id         | BIGINT UNSIGNED | FK → departments(id)               |
| position_id           | BIGINT UNSIGNED | FK → positions(id)                 |
| employment_status_id  | BIGINT UNSIGNED | FK → employment_statuses(id)       |
| team_id               | BIGINT UNSIGNED | FK → teams(id)                     |
| parent_id             | BIGINT UNSIGNED | FK → employees(id), jefe inmediato |
| user_id               | BIGINT UNSIGNED | FK → users(id), 1:1                |
| hire_date             | DATE            |                                    |
| salary                | DECIMAL(12,2)   |                                    |
| is_active             | BOOLEAN         |                                    |
| is_manager            | BOOLEAN         |                                    |
| metadata              | JSONB           | Datos flexibles                    |
| deleted_at            | TIMESTAMP TZ    | SoftDeletes                        |

**Índices y constraints:**
- `parent_id <> id` (CHECK constraint a nivel DB)
- `employee_number` UNIQUE
- `username` UNIQUE
- Recursive CTE en `getAllSubordinateIds()` para jerarquía de supervisión

### 6.2 employment_statuses

| Columna     | Tipo            | Notas                       |
| ----------- | --------------- | --------------------------- |
| id          | BIGINT UNSIGNED | PK                          |
| name        | VARCHAR(255)    |                             |
| code        | VARCHAR(50)     |                             |
| description | TEXT            |                             |
| is_active   | BOOLEAN         |                             |
| parent_id   | BIGINT UNSIGNED | Auto-referencia (jerarquía) |

### 6.3 teams

| Columna       | Tipo            | FK                             |
| ------------- | --------------- | ------------------------------ |
| id            | BIGINT UNSIGNED | PK                             |
| name          | VARCHAR(255)    |                                |
| cisco_team_id | VARCHAR(255)    | ID del equipo en Cisco Finesse |
| description   | TEXT            |                                |
| supervisor_id | BIGINT UNSIGNED | FK → employees(id)             |
| is_active     | BOOLEAN         |                                |

### 6.4 team_members

Pertenencia histórica de empleados a equipos.

| Columna     | Tipo            | FK                 |
| ----------- | --------------- | ------------------ |
| id          | BIGINT UNSIGNED | PK                 |
| team_id     | BIGINT UNSIGNED | FK → teams(id)     |
| employee_id | BIGINT UNSIGNED | FK → employees(id) |
| joined_at   | DATE            |                    |
| left_at     | DATE            | NULLABLE           |
| is_active   | BOOLEAN         |                    |

### 6.5 employee_positions

Asignaciones históricas de puestos.

| Columna     | Tipo            |
| ----------- | --------------- |
| employee_id | BIGINT UNSIGNED |
| position_id | BIGINT UNSIGNED |
| start_date  | DATE            |
| end_date    | DATE (NULLABLE) |
| is_primary  | BOOLEAN         |

### 6.6 employee_dependents

| Columna      | Tipo            |
| ------------ | --------------- |
| employee_id  | BIGINT UNSIGNED |
| name         | VARCHAR(255)    |
| relationship | VARCHAR(50)     |
| birth_date   | DATE            |

### 6.7 employee_diseases / disease_types

| Tabla             | Columnas                                             |
| ----------------- | ---------------------------------------------------- |
| disease_types     | id, name, description                                |
| employee_diseases | employee_id (FK), disease_type_id (FK), notes (TEXT) |

### 6.8 employee_disabilities / disability_types

| Tabla                 | Columnas                                                |
| --------------------- | ------------------------------------------------------- |
| disability_types      | id, name, description                                   |
| employee_disabilities | employee_id (FK), disability_type_id (FK), notes (TEXT) |

### 6.9 employee_import_batches

| Columna           | Tipo         | Notas          |
| ----------------- | ------------ | -------------- |
| id                | CHAR(26)     | ULID PK        |
| batch_id          | VARCHAR(255) |                |
| original_filename | VARCHAR(255) |                |
| stored_path       | VARCHAR(255) |                |
| status            | VARCHAR(32)  |                |
| chunk_size        | INTEGER      |                |
| total_rows        | INTEGER      |                |
| processed_rows    | INTEGER      |                |
| imported_rows     | INTEGER      |                |
| rejected_rows     | INTEGER      |                |
| errors            | JSONB        |                |
| created_by        | BIGINT       | FK → users(id) |

---

## 7. Módulo WFM — WfmModule (Schedule)

### 7.1 schedules

Plantillas de horarios (jornadas laborales base).

| Columna       | Tipo            | Notas                                               |
| ------------- | --------------- | --------------------------------------------------- |
| id            | BIGINT UNSIGNED | PK                                                  |
| name          | VARCHAR(100)    |                                                     |
| start_time    | TIME TZ         | Inicio de jornada                                   |
| end_time      | TIME TZ         | Fin de jornada                                      |
| total_minutes | INTEGER         | Duración total                                      |
| break_minutes | INTEGER         | Minutos de descanso                                 |
| lunch_minutes | INTEGER         | Minutos de almuerzo                                 |
| is_lunch_paid | BOOLEAN         |                                                     |
| is_break_paid | BOOLEAN         |                                                     |
| allowed_days  | JSONB           | Días de la semana permitidos (array de enteros 1-7) |
| is_active     | BOOLEAN         |                                                     |

### 7.2 weekly_schedules

Instancias de planificación semanal.

| Columna         | Tipo            | Notas                            |
| --------------- | --------------- | -------------------------------- |
| id              | BIGINT UNSIGNED | PK                               |
| week_start_date | DATE            | Lunes de la semana               |
| week_end_date   | DATE            | Domingo de la semana             |
| status          | VARCHAR(20)     | `draft`, `published`, `archived` |
| published_at    | TIMESTAMP TZ    | NULLABLE                         |

**Relaciones:**
- `assignments()` → HasMany `WeeklyScheduleAssignment`
- `teamAssignments()` → HasMany `WeeklyTeamAssignment`

### 7.3 weekly_schedule_assignments

Asignación empleado → horario para un día específico de la semana.

| Columna            | Tipo            | FK                                    |
| ------------------ | --------------- | ------------------------------------- |
| id                 | BIGINT UNSIGNED | PK                                    |
| weekly_schedule_id | BIGINT UNSIGNED | FK → weekly_schedules(id)             |
| employee_id        | BIGINT UNSIGNED | FK → employees(id)                    |
| schedule_id        | BIGINT UNSIGNED | FK → schedules(id)                    |
| day_of_week        | SMALLINT        | 1-7 (ISO: Lun=1, Dom=7)               |
| start_time         | TIME TZ         | Puede diferir del schedule base       |
| end_time           | TIME TZ         |                                       |
| lunch_start_time   | TIME TZ         |                                       |
| lunch_end_time     | TIME TZ         |                                       |
| break_start_time   | TIME TZ         |                                       |
| break_end_time     | TIME TZ         |                                       |
| swap_request_id    | BIGINT UNSIGNED | FK → shift_swap_requests(id) nullable |
| is_replaced        | BOOLEAN         | DEFAULT false (swap activo)           |
| replaced_at        | TIMESTAMP TZ    | NULLABLE                              |

**Global Scope:** `is_replaced = false` — las asignaciones reemplazadas por swaps no se ven por defecto.

### 7.4 weekly_team_assignments

Asignación equipo → horario (planificación base por equipo).

| Columna                         | Tipo            |
| ------------------------------- | --------------- |
| weekly_schedule_id              | BIGINT UNSIGNED |
| team_id                         | BIGINT UNSIGNED |
| schedule_id                     | BIGINT UNSIGNED |
| day_of_week                     | SMALLINT        |
| start_time/end_time/lunch/break | TIME TZ         |

---

## 8. Módulo WFM — Permisos e Intercambios

### 8.1 leave_requests

| Columna     | Tipo            | FK                                             |
| ----------- | --------------- | ---------------------------------------------- |
| id          | BIGINT UNSIGNED | PK                                             |
| employee_id | BIGINT UNSIGNED | FK → employees(id)                             |
| type        | VARCHAR(255)    | `quarterly`, `compensatorio`                   |
| start_time  | TIMESTAMP TZ    |                                                |
| end_time    | TIMESTAMP TZ    |                                                |
| minutes     | INTEGER         | Duración solicitada                            |
| status      | VARCHAR(255)    | `pending`, `approved`, `rejected`, `cancelled` |
| reason      | TEXT            |                                                |

**Relaciones:**
- `employee()` → BelongsTo Employee
- `approvals()` → HasMany LeaveRequestApproval

### 8.2 leave_request_approvals

Aprobaciones multi-nivel.

| Columna          | Tipo            | FK                                |
| ---------------- | --------------- | --------------------------------- |
| leave_request_id | BIGINT UNSIGNED | FK → leave_requests(id)           |
| approver_id      | BIGINT UNSIGNED | FK → employees(id)                |
| status           | VARCHAR(255)    | `pending`, `approved`, `rejected` |
| comment          | TEXT            |                                   |
| step_order       | INTEGER         | Orden de aprobación               |

### 8.3 shift_swap_requests

| Columna                       | Tipo            | FK                                 |
| ----------------------------- | --------------- | ---------------------------------- |
| id                            | BIGINT UNSIGNED | PK                                 |
| requester_id                  | BIGINT UNSIGNED | FK → employees(id)                 |
| recipient_id                  | BIGINT UNSIGNED | FK → employees(id)                 |
| start_date                    | DATE            |                                    |
| end_date                      | DATE            |                                    |
| status                        | VARCHAR(255)    |                                    |
| reason                        | TEXT            |                                    |
| requester_assignment_snapshot | JSONB           | Snapshot de la asignación original |
| recipient_assignment_snapshot | JSONB           | Snapshot de la asignación destino  |

**Snapshots JSONB:** Inmutabilidad — al aprobarse, se registra el estado de las asignaciones antes del swap para trazabilidad.

### 8.4 shift_swap_approvals

| Columna               | Tipo            |
| --------------------- | --------------- |
| shift_swap_request_id | BIGINT UNSIGNED |
| approver_id           | BIGINT UNSIGNED |
| status                | VARCHAR(255)    |
| comment               | TEXT            |
| step_order            | INTEGER         |

---

## 9. Módulo WFM — Excepciones e Intradía

### 9.1 absence_reason_codes

Catálogo de causas de ausencia.

| Columna             | Tipo         |
| ------------------- | ------------ |
| name                | VARCHAR(100) |
| short_code          | VARCHAR(10)  |
| requires_attachment | BOOLEAN      |
| is_excused          | BOOLEAN      |
| color               | VARCHAR(20)  |

### 9.2 schedule_exceptions

Excepciones puntuales al horario (inasistencias, permisos, etc.).

| Columna                | Tipo            | FK / Notas                                 |
| ---------------------- | --------------- | ------------------------------------------ |
| employee_id            | BIGINT UNSIGNED | FK → employees(id)                         |
| absence_reason_code_id | BIGINT UNSIGNED | FK → absence_reason_codes(id)              |
| start_at               | TIMESTAMP TZ    |                                            |
| end_at                 | TIMESTAMP TZ    |                                            |
| is_full_day            | BOOLEAN         |                                            |
| remarks                | TEXT            |                                            |
| created_by             | BIGINT          | FK → users(id)                             |
| metadata               | JSONB           |                                            |
| origin_type            | VARCHAR(255)    | Polymorphic: `leave_request`, `shift_swap` |
| origin_id              | BIGINT          | Polymorphic FK                             |

**Polimorfismo:** Las excepciones pueden originarse de `leave_requests`, `shift_swap_requests`, o crearse manualmente.

### 9.3 activity_types

| Columna       | Tipo        |
| ------------- | ----------- |
| name          | VARCHAR(50) |
| color         | VARCHAR(20) |
| is_productive | BOOLEAN     |
| is_paid       | BOOLEAN     |

### 9.4 scheduled_activity_definitions

| Columna                  | Tipo            | FK                      |
| ------------------------ | --------------- | ----------------------- |
| name                     | VARCHAR(150)    |                         |
| activity_type_id         | BIGINT UNSIGNED | FK → activity_types(id) |
| default_duration_minutes | INTEGER         |                         |
| default_location         | VARCHAR(255)    |                         |
| default_instructor       | VARCHAR(255)    |                         |
| is_active                | BOOLEAN         |                         |

### 9.5 approved_intraday_periods

Períodos aprobados por WFM para actividades intradía (ej. "Capacitación equipo A, 2-4pm, 15 slots").

| Columna                | Tipo            | FK                                      |
| ---------------------- | --------------- | --------------------------------------- |
| team_id                | BIGINT UNSIGNED | FK → teams(id)                          |
| activity_definition_id | BIGINT UNSIGNED | FK → scheduled_activity_definitions(id) |
| date                   | DATE            |                                         |
| start_time             | TIME TZ         |                                         |
| end_time               | TIME TZ         |                                         |
| max_slots              | SMALLINT        | Capacidad máxima                        |
| notes                  | VARCHAR(500)    |                                         |

**Control de concurrencia:** `max_slots` debe protegerse con `lockForUpdate()` en la transacción de asignación para evitar condiciones de carrera.

### 9.6 intraday_activities

Actividades intradía asignadas a empleados. Usa `tstzrange` de PostgreSQL.

| Columna            | Tipo            | FK / Notas                                  |
| ------------------ | --------------- | ------------------------------------------- |
| employee_id        | BIGINT UNSIGNED | FK → employees(id)                          |
| activity_type_id   | BIGINT UNSIGNED | FK → activity_types(id)                     |
| time_range         | TSTZRANGE       | PostgreSQL range type                       |
| notes              | TEXT            |                                             |
| approved_period_id | BIGINT UNSIGNED | FK → approved_intraday_periods(id) nullable |

**PostgreSQL:** `tstzrange` tiene índice GiST para `EXCLUDE USING gist (time_range WITH &&)` que previene solapamientos.

### 9.7 temporal_assignments

Asignaciones temporales de empleados a supervisores/equipos.

| Columna       | Tipo            | FK                 |
| ------------- | --------------- | ------------------ |
| employee_id   | BIGINT UNSIGNED | FK → employees(id) |
| supervisor_id | BIGINT UNSIGNED | FK → employees(id) |
| team_id       | BIGINT UNSIGNED | FK → teams(id)     |
| start_date    | DATE            |                    |
| end_date      | DATE            |                    |
| source_type   | VARCHAR(50)     |                    |
| source_id     | BIGINT          |                    |

---

## 10. Módulo Connect — ConnectModule (Cisco)

### 10.1 channels

Medios de atención (Voz, Chat, etc.).

| Columna     | Tipo         | Notas   |
| ----------- | ------------ | ------- |
| id          | CHAR(26)     | ULID PK |
| name        | VARCHAR(255) |         |
| description | VARCHAR(500) |         |
| is_active   | BOOLEAN      |         |

### 10.2 call_queues

Colas de atención (CSQs en Cisco).

| Columna     | Tipo            | FK                       |
| ----------- | --------------- | ------------------------ |
| id          | BIGINT UNSIGNED | PK                       |
| name        | VARCHAR(255)    |                          |
| channel_id  | CHAR(26)        | FK → channels(id)        |
| description | TEXT            |                          |
| aht_goal    | INTEGER         | AHT objetivo en segundos |
| is_active   | BOOLEAN         |                          |

### 10.3 case_subtypes

Subtipo de caso/trámite asociado a una cola.

| Columna     | Tipo            | FK                   |
| ----------- | --------------- | -------------------- |
| id          | BIGINT UNSIGNED | PK                   |
| code        | VARCHAR(255)    |                      |
| queue_id    | BIGINT UNSIGNED | FK → call_queues(id) |
| name        | VARCHAR(255)    |                      |
| description | TEXT            |                      |
| is_active   | BOOLEAN         |                      |

### 10.4 call_records

Registro de llamadas del CUIC. Tabla más grande del sistema.

| Columna             | Tipo            | FK / Notas                           |
| ------------------- | --------------- | ------------------------------------ |
| id                  | BIGINT UNSIGNED | PK                                   |
| cisco_call_id       | VARCHAR(255)    | ID único en Cisco                    |
| sequence_number     | INTEGER         |                                      |
| queue_id            | BIGINT UNSIGNED | FK → call_queues(id)                 |
| phone_number        | VARCHAR(255)    |                                      |
| destination_number  | VARCHAR(255)    |                                      |
| ivr_started_at      | TIMESTAMP TZ    |                                      |
| ivr_ended_at        | TIMESTAMP TZ    |                                      |
| talk_time           | INTEGER         | Segundos                             |
| ring_time           | INTEGER         | Segundos                             |
| work_time           | INTEGER         | Segundos post-llamada                |
| queue_time          | INTEGER         | Segundos en espera                   |
| contact_disposition | SMALLINT        | Disposición final                    |
| employee_id         | BIGINT UNSIGNED | FK → employees(id)                   |
| raw_agent_name      | VARCHAR(255)    | Nombre crudo de Cisco                |
| citizen_identifier  | VARCHAR(12)     | Cédula del ciudadano                 |
| case_subtype_id     | BIGINT UNSIGNED | FK → case_subtypes(id)               |
| description         | TEXT            |                                      |
| status              | VARCHAR(255)    | `pending_operator`, `open`, `closed` |
| closed_at           | TIMESTAMP TZ    |                                      |

### 10.5 agent_realtime_states

Tabla UNLOGGED para escritura de alta frecuencia (cada 5 segundos).

| Columna         | Tipo            | Notas              |
| --------------- | --------------- | ------------------ |
| id              | BIGINT UNSIGNED | PK                 |
| employee_id     | BIGINT UNSIGNED | FK → employees(id) |
| external_id     | VARCHAR(50)     | ID Cisco           |
| current_state   | VARCHAR(50)     |                    |
| reason_code     | VARCHAR(50)     |                    |
| last_changed_at | TIMESTAMP TZ    |                    |
| metadata        | JSONB           |                    |
| updated_at      | TIMESTAMP TZ    | Sin created_at     |

### 10.6 agent_call_performance

Rendimiento por llamada desde CUIC.

| Columna                         | Tipo            |
| ------------------------------- | --------------- |
| agent_login_id                  | VARCHAR(255)    |
| employee_id                     | BIGINT UNSIGNED |
| agent_ext                       | VARCHAR(255)    |
| start_time                      | TIMESTAMP TZ    |
| end_time                        | TIMESTAMP TZ    |
| total_duration                  | INTEGER         |
| talk_time, hold_time, work_time | INTEGER         |
| phone_number, ani               | VARCHAR(255)    |
| csq_name, call_skill, call_type | VARCHAR(255)    |
| raw_agent_name                  | VARCHAR(255)    |

**Índice único:** `(agent_login_id, start_time, call_skill)` — evita duplicados en la carga incremental.

### 10.7 agent_state_transitions

Transiciones de estado de agente.

| Columna         | Tipo            |
| --------------- | --------------- |
| agent_login_id  | VARCHAR(255)    |
| employee_id     | BIGINT UNSIGNED |
| transition_time | TIMESTAMP TZ    |
| agent_state     | VARCHAR(255)    |
| reason_code     | VARCHAR(255)    |
| duration        | INTEGER         |

### 10.8 chat_records

| Columna                                   | Tipo            |
| ----------------------------------------- | --------------- |
| conversation_id                           | VARCHAR(255)    |
| agent_login_id                            | VARCHAR(255)    |
| employee_id                               | BIGINT UNSIGNED |
| start_time, end_time, accepted_at         | TIMESTAMP TZ    |
| total_duration, talk_time                 | INTEGER         |
| author_identifier, destination_identifier | VARCHAR(255)    |
| chat_type, chat_source, chat_rating       | VARCHAR(255)    |

### 10.9 csq_realtime_stats

Estadísticas de cola en tiempo real desde CUIC.

| Columna                                           | Tipo         |
| ------------------------------------------------- | ------------ |
| csq_name                                          | VARCHAR(255) |
| calls_waiting, longest_call_in_queue              | INTEGER      |
| agents_logged_on, agents_talking                  | INTEGER      |
| agents_ready, agents_not_ready                    | INTEGER      |
| agents_after_call_work, agents_reserved           | INTEGER      |
| service_level_short_term, service_level_long_term | DECIMAL(5,2) |
| calls_abandoned_since_midnight                    | INTEGER      |
| calls_handled_since_midnight                      | INTEGER      |
| total_calls_since_midnight                        | INTEGER      |
| metadata                                          | JSONB        |

---

## 11. Módulo Operaciones — OperationsModule

### 11.1 agent_daily_metrics

Métricas diarias calculadas por empleado.

| Columna            | Tipo            | Notas                         |
| ------------------ | --------------- | ----------------------------- |
| employee_id        | BIGINT UNSIGNED | FK                            |
| metric_date        | DATE            |                               |
| login_seconds      | INTEGER         | Tiempo conectado              |
| productive_seconds | INTEGER         | Tiempo productivo             |
| calls_total        | INTEGER         |                               |
| talk_seconds       | INTEGER         |                               |
| weighted_aht       | DECIMAL(10,2)   | Average Handle Time ponderado |
| capacity_calls     | DECIMAL(10,2)   | Capacidad en llamadas         |
| capacity_gap       | DECIMAL(10,2)   | Brecha de capacidad           |
| work_units         | DECIMAL(10,2)   | Unidades de trabajo           |
| availability_pct   | DECIMAL(10,2)   | Disponibilidad %              |
| efficiency_pct     | DECIMAL(10,2)   | Eficiencia %                  |
| pwi_pct            | DECIMAL(10,2)   | PWI %                         |
| queue_distribution | JSONB           | Distribución por cola         |

### 11.2 attendance_incidents

Incidencias de asistencia. Usa ULID.

| Columna          | Tipo            | FK                      |
| ---------------- | --------------- | ----------------------- |
| id               | CHAR(26)        | ULID PK                 |
| employee_id      | BIGINT UNSIGNED | FK → employees(id)      |
| incident_type_id | BIGINT UNSIGNED | FK → incident_types(id) |
| incident_date    | DATE            |                         |
| start_time       | TIME TZ         |                         |
| end_time         | TIME TZ         |                         |
| user_comment     | TEXT            | Comentario del empleado |
| admin_comment    | TEXT            | Comentario del admin    |

### 11.3 incident_types

Catálogo de tipos de incidencia.

| Columna                | Tipo         |
| ---------------------- | ------------ |
| code                   | VARCHAR(20)  |
| name                   | VARCHAR(255) |
| color                  | VARCHAR(50)  |
| requires_justification | BOOLEAN      |
| affects_availability   | BOOLEAN      |
| is_active              | BOOLEAN      |

### 11.4 agent_states

Catálogo de estados de agente (mapeo Cisco → interno).

| Columna       | Tipo         |
| ------------- | ------------ |
| external_code | VARCHAR(50)  |
| display_name  | VARCHAR(100) |
| is_productive | BOOLEAN      |
| color_hex     | VARCHAR(7)   |

---

## 12. Módulo Calidad — QualityModule

Todas las tablas usan **ULID** como PK (CHAR(26)).

### 12.1 quality_queues

| Columna   | Tipo         |
| --------- | ------------ |
| id        | CHAR(26)     | ULID PK |
| code      | VARCHAR(20)  |
| name      | VARCHAR(255) |
| is_active | BOOLEAN      |

### 12.2 quality_criteria / quality_criteria_versions

Versionado de criterios (cambios históricos controlados).

| Tabla                     | Columnas clave                                                                                                                      |
| ------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| quality_criteria          | id (ULID), code (VARCHAR(50))                                                                                                       |
| quality_criteria_versions | id (ULID), criteria_id (FK), version (SMALLINT), criterio_text, puntaje (SMALLINT), descripcion, valid_from (DATE), valid_to (DATE) |

### 12.3 quality_queue_criteria

Asignación criterio-versión → cola de calidad.

| Columna             | Tipo     |
| ------------------- | -------- |
| queue_id            | CHAR(26) |
| criteria_version_id | CHAR(26) |
| orden               | SMALLINT |
| is_active           | BOOLEAN  |

### 12.4 quality_evaluations

Evaluación central.

| Columna        | Tipo            | FK                             |
| -------------- | --------------- | ------------------------------ |
| id             | CHAR(26)        | ULID PK                        |
| queue_id       | CHAR(26)        |                                |
| employee_id    | BIGINT UNSIGNED | FK → employees(id) — evaluado  |
| evaluator_id   | BIGINT UNSIGNED | FK → employees(id) — evaluador |
| clip_id        | BIGINT          | Referencia al clip de audio    |
| dtcall, tmcall | DATE, TIME      | Fecha/hora de la llamada       |
| dteval, tmeval | DATE, TIME      | Fecha/hora de la evaluación    |
| score          | SMALLINT        | Puntaje total                  |
| callobs        | TEXT            | Observaciones                  |
| has_redflag    | BOOLEAN         |                                |
| status         | VARCHAR(20)     |                                |
| deleted_at     | TIMESTAMP TZ    | SoftDeletes                    |

### 12.5 quality_evaluation_scores

Puntajes por criterio dentro de una evaluación.

| Columna             | Tipo     |
| ------------------- | -------- |
| evaluation_id       | CHAR(26) |
| criteria_version_id | CHAR(26) |
| puntaje_obtenido    | SMALLINT |

### 12.6 quality_evaluation_red_flags

Red flags levantadas en una evaluación.

| Columna              | Tipo     |
| -------------------- | -------- |
| evaluation_id        | CHAR(26) |
| red_flag_criteria_id | CHAR(26) |

### 12.7 quality_red_flag_criteria

| Columna       | Tipo         |
| ------------- | ------------ |
| criterio_text | VARCHAR(255) |
| perdida       | SMALLINT     | Penalización |
| is_active     | BOOLEAN      |

### 12.8 quality_feedback

Feedback post-evaluación.

| Columna       | Tipo     |
| ------------- | -------- |
| evaluation_id | CHAR(26) |
| obsfeed       | TEXT     |
| created_by    | BIGINT   |

### 12.9 quality_calibration_log

Bitácora de calibración (re-evaluaciones).

| Columna        | Tipo     |
| -------------- | -------- |
| evaluation_id  | CHAR(26) |
| score_anterior | SMALLINT |
| score_nuevo    | SMALLINT |
| obs            | TEXT     |
| created_by     | BIGINT   |

---

## 13. Módulo Comunicaciones — CommunicationsModule

### 13.1 news

| Columna          | Tipo         | Notas                                                  |
| ---------------- | ------------ | ------------------------------------------------------ |
| title            | VARCHAR(255) |                                                        |
| slug             | VARCHAR(255) |                                                        |
| excerpt          | TEXT         |                                                        |
| content          | TEXT         |                                                        |
| author_id        | BIGINT       |                                                        |
| status           | VARCHAR(255) | `draft`, `pending`, `approved`, `rejected`, `archived` |
| approved_by      | BIGINT       |                                                        |
| approved_at      | TIMESTAMP    |                                                        |
| moderation_notes | TEXT         |                                                        |
| version_history  | JSONB        | Historial de versiones                                 |
| is_active        | BOOLEAN      |                                                        |
| published_at     | TIMESTAMP    | Programable                                            |
| scheduled_at     | TIMESTAMP    |                                                        |
| archive_at       | TIMESTAMP    | Auto-archivado                                         |

### 13.2 polls

| Columna                   | Tipo         |
| ------------------------- | ------------ |
| question                  | VARCHAR(255) |
| options                   | JSONB        | Opciones de respuesta |
| status                    | VARCHAR(255) |                       |
| approved_by / approved_at |              |
| expires_at                | TIMESTAMP    |                       |
| reminder_sent_at          | TIMESTAMP    |                       |

### 13.3 shoutouts

Reconocimientos entre empleados.

| Columna                   | Tipo         |
| ------------------------- | ------------ |
| employee_id               | BIGINT       | Reconocido     |
| message                   | TEXT         |                |
| status                    | VARCHAR(255) | Con moderación |
| approved_by / approved_at |              |

### 13.4 comments, reactions, mentions

| Tabla     | Propósito                                                  |
| --------- | ---------------------------------------------------------- |
| comments  | Comentarios en news (auto-referencia parent_id para hilos) |
| reactions | Reacciones a shoutouts (type: like, celebrate, etc.)       |
| mentions  | Menciones polimórficas a usuarios                          |

### 13.5 categories / tags (polimórficos)

| Tabla          | Propósito                                              |
| -------------- | ------------------------------------------------------ |
| categories     | Categorización polimórfica de contenido                |
| tags           | Etiquetado polimórfico                                 |
| categorizables | Pivot polimórfico (category_id, categorizable_type/id) |
| taggables      | Pivot polimórfico (tag_id, taggable_type/id)           |

### 13.6 notifications

Tabla nativa de Laravel + columnas extendidas.

| Columna         | Tipo         | Notas                    |
| --------------- | ------------ | ------------------------ |
| id              | UUID         | PK                       |
| type            | VARCHAR(255) | Clase de la notificación |
| notifiable_type | VARCHAR(255) |                          |
| notifiable_id   | BIGINT       |                          |
| data            | JSON         |                          |
| read_at         | TIMESTAMP    |                          |
| user_id         | BIGINT       | FK extendida             |
| title           | VARCHAR(255) |                          |
| message         | TEXT         |                          |
| is_read         | BOOLEAN      |                          |
| expires_at      | TIMESTAMP    |                          |

---

## 14. Módulo Helpdesk — HelpdeskModule

### 14.1 helpdesk_categories

Categorías con SLA.

| Columna     | Tipo         |
| ----------- | ------------ |
| name        | VARCHAR(255) |
| description | VARCHAR(255) |
| sla_hours   | INTEGER      |
| color       | VARCHAR(20)  |
| is_active   | BOOLEAN      |

### 14.2 helpdesk_tickets

| Columna           | Tipo         |
| ----------------- | ------------ |
| subject           | VARCHAR(255) |
| description       | TEXT         |
| category_id       | BIGINT FK    |
| creator_id        | BIGINT FK    |
| assigned_agent_id | BIGINT FK    |
| status            | VARCHAR(255) | `open`, `in_progress`, `resolved`, `closed` |
| priority          | VARCHAR(255) | `low`, `medium`, `high`, `critical`         |
| resolved_at       | TIMESTAMP TZ |
| closed_at         | TIMESTAMP TZ |

### 14.3 helpdesk_ticket_comments

| Columna     | Tipo      |
| ----------- | --------- |
| ticket_id   | BIGINT FK |
| author_id   | BIGINT FK |
| content     | TEXT      |
| is_internal | BOOLEAN   | Notas internas no visibles al creador |

---

## 15. Módulo Conocimiento — KnowledgeModule

### 15.1 knowledge_categories

| Columna     | Tipo         |
| ----------- | ------------ |
| name        | VARCHAR(255) |
| description | TEXT         |

### 15.2 knowledge_articles

Artículos versionados.

| Columna      | Tipo         | Notas                            |
| ------------ | ------------ | -------------------------------- |
| title        | VARCHAR(255) |                                  |
| slug         | VARCHAR(255) |                                  |
| summary      | TEXT         |                                  |
| content      | TEXT         |                                  |
| category_id  | BIGINT FK    |                                  |
| status       | VARCHAR(255) | `draft`, `published`, `archived` |
| version      | INTEGER      | Versión actual                   |
| published_at | TIMESTAMP    |                                  |
| expires_at   | TIMESTAMP    | Auto-expiración                  |
| created_by   | BIGINT       |                                  |
| updated_by   | BIGINT       |                                  |

### 15.3 knowledge_article_versions

| Columna    | Tipo      |
| ---------- | --------- |
| article_id | BIGINT FK |
| version    | INTEGER   |
| content    | TEXT      |
| created_by | BIGINT    |

### 15.4 knowledge_queues / knowledge_tags

| Tabla                   | Relación                  |
| ----------------------- | ------------------------- |
| knowledge_queues        | name, priority, is_active |
| knowledge_tags          | name                      |
| knowledge_article_queue | article_id ↔ queue_id     |
| knowledge_article_tag   | article_id ↔ tag_id       |

---

## 16. Módulo Documentación — DocumentationModule

### 16.1 documentation_articles

| Columna      | Tipo         |
| ------------ | ------------ |
| title        | VARCHAR(255) |
| slug         | VARCHAR(255) |
| content      | TEXT         |
| is_published | BOOLEAN      |
| author_id    | BIGINT FK    |
| view_count   | INTEGER      |
| sort_order   | INTEGER      |

---

## 17. Módulo Sistema de Archivos — FilesystemModule

### 17.1 folders

Jerarquía de carpetas por usuario.

| Columna   | Tipo                              |
| --------- | --------------------------------- |
| uuid      | UUID                              |
| user_id   | BIGINT FK                         |
| parent_id | BIGINT NULLABLE (auto-referencia) |
| name      | VARCHAR(255)                      |
| color     | VARCHAR(7)                        |

### 17.2 files

Archivos almacenados (Spatie Media Library + custom).

| Columna       | Tipo         |
| ------------- | ------------ |
| uuid          | UUID         |
| user_id       | BIGINT FK    |
| folder_id     | BIGINT FK    |
| name          | VARCHAR(255) |
| original_name | VARCHAR(255) |
| path          | VARCHAR(255) |
| disk          | VARCHAR(255) |
| size          | BIGINT       |
| mime_type     | VARCHAR(255) |
| extension     | VARCHAR(10)  |
| is_public     | BOOLEAN      |

### 17.3 file_shares

| Columna      | Tipo                       |
| ------------ | -------------------------- |
| file_id      | BIGINT FK (nullable)       |
| folder_id    | BIGINT FK (nullable)       |
| user_id      | BIGINT FK — destinatario   |
| shared_by_id | BIGINT FK — quien comparte |
| access_level | VARCHAR(255)               |
| expires_at   | TIMESTAMP TZ               |

### 17.4 storage_quotas

Polimórfico (por usuario, equipo, etc.).

| Columna     | Tipo         |
| ----------- | ------------ |
| target_type | VARCHAR(255) |
| target_id   | BIGINT       |
| quota_limit | BIGINT       |

---

## 18. Módulo Auditoría — AuditModule

### 18.1 audit_logs

Append-only log de operaciones CRUD.

| Columna     | Tipo         | Notas                                       |
| ----------- | ------------ | ------------------------------------------- |
| entity_type | VARCHAR(255) |                                             |
| entity_id   | VARCHAR(255) |                                             |
| action      | VARCHAR(255) | `created`, `updated`, `deleted`, `restored` |
| before      | JSONB        | Estado anterior                             |
| after       | JSONB        | Estado posterior                            |
| ip_address  | VARCHAR(45)  | IPv4 o IPv6                                 |
| user_id     | BIGINT FK    |

---

## 19. Tablas Legacy / No Activas

| Tabla                  | Notas                                                                                   |
| ---------------------- | --------------------------------------------------------------------------------------- |
| `funcionarios`         | Migración 2026_07_07_103642, tabla vacía/generada, no referenciada en modelos actuales  |
| `cargos`               | Migración 2026_07_07_103643, tabla vacía                                                |
| `relaciones_laborales` | Migración 2026_07_07_103644, tabla vacía                                                |
| `media`                | Tabla de Spatie Media Library (instalada pero no activamente usada en modelos actuales) |

---

## 20. Resumen de Relaciones Cross-Module

La columna `employee_id` es el punto de integración más común entre módulos. Aparece en 18 tablas externas:

| Módulo           | Tabla                       | Columna FK                 |
| ---------------- | --------------------------- | -------------------------- |
| PersonnelModule  | employees                   | — (tabla fuente)           |
| PersonnelModule  | team_members                | employee_id                |
| PersonnelModule  | employee_positions          | employee_id                |
| PersonnelModule  | employee_dependents         | employee_id                |
| PersonnelModule  | employee_diseases           | employee_id                |
| PersonnelModule  | employee_disabilities       | employee_id                |
| WfmModule        | weekly_schedule_assignments | employee_id                |
| WfmModule        | leave_requests              | employee_id                |
| WfmModule        | shift_swap_requests         | requester_id, recipient_id |
| WfmModule        | schedule_exceptions         | employee_id                |
| WfmModule        | intraday_activities         | employee_id                |
| WfmModule        | temporal_assignments        | employee_id, supervisor_id |
| ConnectModule    | call_records                | employee_id                |
| ConnectModule    | agent_realtime_states       | employee_id                |
| ConnectModule    | agent_call_performance      | employee_id                |
| ConnectModule    | agent_state_transitions     | employee_id                |
| ConnectModule    | chat_records                | employee_id                |
| OperationsModule | agent_daily_metrics         | employee_id                |
| OperationsModule | attendance_incidents        | employee_id                |
| OperationsModule | quality_evaluations         | employee_id, evaluator_id  |

---

## 21. Migraciones — Estrategia

### 21.1 Organización

- **59 migraciones** en `database/migrations/`
- **~16 migraciones locales** en `app/Modules/*/Database/Migrations/`
- Ordenadas por timestamp con prefijo `YYYY_MM_DD`

### 21.2 Agrupación por fase

| Fecha      | Tema                                                                | Migraciones |
| ---------- | ------------------------------------------------------------------- | ----------- |
| 0001-01-01 | Laravel base (users, cache, jobs, sessions)                         | 3           |
| 2026-03-23 | Fundación (organización, geografía, empleados, detalles, auditoría) | 6           |
| 2026-03-25 | Media, comunicaciones                                               | 2           |
| 2026-03-26 | Categorías, tags, comments, reactions, mentions                     | 5           |
| 2026-03-30 | Import batches                                                      | 1           |
| 2026-04-07 | ConnectModule (channels, queues, subtypes, call_records)            | 4           |
| 2026-04-10 | Extensiones PostgreSQL (btree_gist)                                 | 1           |
| 2026-04-13 | Operaciones (agent_states)                                          | 1           |
| 2026-04-20 | Schedule module (horarios, asignaciones)                            | 2           |
| 2026-04-21 | Workflows (swap, leave)                                             | 2           |
| 2026-04-22 | Excepciones, helpdesk, settings                                     | 3           |
| 2026-04-23 | Connect datos CUIC (calls, chats, transitions)                      | 3           |
| 2026-04-29 | Índices únicos (agent_call_performance)                             | 1           |
| 2026-05-04 | Refactors (swap traceability, app_settings)                         | 2           |
| 2026-05-07 | Fix notifications                                                   | 1           |
| 2026-05-13 | Schedule exceptions origin, attendance incidents, drop obsolete     | 3           |
| 2026-05-14 | Fix column types, notes, documentation, filesystem                  | 4           |
| 2026-05-15 | Storage quotas                                                      | 1           |
| 2026-05-20 | KPI goals, agent_daily metrics, approved periods, intraday FK       | 5           |
| 2026-05-22 | Shift swap date range                                               | 1           |
| 2026-06-03 | Knowledge module                                                    | 1           |
| 2026-07-07 | Tablas legacy                                                       | 3           |
| 2026-07-15 | Temporal assignments                                                | 1           |

### 21.3 Principios de diseño de migraciones

- **Idempotencia:** `create table IF NOT EXISTS`, `updateOrInsert` en seeders
- **PostgreSQL guard:** Verificar `DB::getDriverName() === 'pgsql'` para features específicas
- **Rollback completo:** Cada `up()` tiene su `down()` correspondiente
- **Sin datos en migraciones:** Los datos se siembran en seeders, no en migraciones

---

## 22. Índices y Constraints Destacados

| Tabla                         | Índice / Constraint                             | Propósito                         |
| ----------------------------- | ----------------------------------------------- | --------------------------------- |
| `employees`                   | UNIQUE (employee_number)                        | No duplicar números de empleado   |
| `employees`                   | UNIQUE (username)                               | Usuario único                     |
| `employees`                   | CHECK (parent_id <> id)                         | Auto-referencia no circular       |
| `agent_realtime_states`       | UNLOGGED TABLE                                  | Sin WAL para escritura 5s         |
| `intraday_activities`         | GiST (time_range WITH &&)                       | EXCLUDE solapamientos de rangos   |
| `agent_call_performance`      | UNIQUE (agent_login_id, start_time, call_skill) | Evitar duplicados CUIC            |
| `call_records`                | INDEX (employee_id, ivr_started_at)             | Consultas de agente por fecha     |
| `weekly_schedule_assignments` | INDEX (weekly_schedule_id, employee_id)         | Lookup asignaciones semanales     |
| `sessions`                    | INDEX (user_id)                                 | Consultas de sesión activa        |
| `audit_logs`                  | INDEX (entity_type, entity_id)                  | Búsqueda de auditoría por entidad |
