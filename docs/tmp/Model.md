```sql
-- ============================================================================
-- HorariosWFM — DDL Refactorizado (base para migraciones Laravel)
-- Stack: PostgreSQL 16 + Laravel 13
-- Convención: bigserial para todos los IDs, nomenclatura en inglés
--
-- Notas de arquitectura (decisiones):
--  * Las secciones están ordenadas por dependencia (las tablas referenciadas
--    se crean antes que las que las referencian), de modo que el DDL puede
--    ejecutarse de arriba hacia abajo sin ALTERs circulares. La sección de
--    Analytics/Facts va después de Forecast y Quality porque sus fact tables
--    referencian tablas de esas secciones.
--  * Se mantienen DOS jerarquías organizativas: directorates→departments→
--    positions (funcional) y organizational_units (jerárquica multinivel).
--    employees referencia ambas (department_id/position_id + organizational_unit_id).
--  * Actores/aprobadores/gestores del dominio apuntan a public.users.
--    employees es el perfil operativo del empleado; user_id es el enlace a la
--    credencial de acceso. Las columnas employee_id en tablas WFM/asistencia/
--    métricas son el SUJETO de los datos y permanecen como employees.
-- ============================================================================

-- ============================================================================
-- 1. CORE: Auth, RBAC, Sessions, Cache, Jobs
-- ============================================================================

CREATE TABLE public.users (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    email_verified_at timestamptz NULL,
    password varchar(255) NOT NULL,
    two_factor_secret text NULL,              -- Fortify cifra automáticamente
    two_factor_recovery_codes text NULL,      -- Fortify cifra automáticamente
    two_factor_confirmed_at timestamptz NULL,
    is_active bool DEFAULT true NOT NULL,
    last_login_at timestamptz NULL,
    force_password_change bool DEFAULT false NOT NULL,
    remember_token varchar(100) NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    deleted_at timestamptz NULL,
    CONSTRAINT users_pkey PRIMARY KEY (id),
    CONSTRAINT users_email_unique UNIQUE (email)
);

CREATE TABLE public.roles (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    guard_name varchar(255) NOT NULL,
    code varchar(50) NULL,
    hierarchy_level int4 DEFAULT 0 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT roles_pkey PRIMARY KEY (id),
    CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name)
);

CREATE TABLE public.permissions (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    guard_name varchar(255) NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT permissions_pkey PRIMARY KEY (id),
    CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name)
);

CREATE TABLE public.model_has_roles (
    role_id int8 NOT NULL,
    model_type varchar(255) NOT NULL,
    model_id int8 NOT NULL,
    CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type),
    CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE
);
CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);

CREATE TABLE public.model_has_permissions (
    permission_id int8 NOT NULL,
    model_type varchar(255) NOT NULL,
    model_id int8 NOT NULL,
    CONSTRAINT model_has_permissions_pkey PRIMARY KEY (permission_id, model_id, model_type),
    CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE
);
CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);

CREATE TABLE public.role_has_permissions (
    permission_id int8 NOT NULL,
    role_id int8 NOT NULL,
    CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id),
    CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE,
    CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE
);

CREATE TABLE public.personal_access_tokens (
    id bigserial NOT NULL,
    tokenable_type varchar(255) NOT NULL,
    tokenable_id int8 NOT NULL,
    name text NOT NULL,
    token varchar(64) NOT NULL,
    abilities text NULL,
    last_used_at timestamptz NULL,
    expires_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id),
    CONSTRAINT personal_access_tokens_token_unique UNIQUE (token)
);
CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);
CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);

CREATE TABLE public.sessions (
    id varchar(255) NOT NULL,
    user_id int8 NULL,
    ip_address varchar(45) NULL,
    user_agent text NULL,
    payload text NOT NULL,
    last_activity int4 NOT NULL,
    CONSTRAINT sessions_pkey PRIMARY KEY (id)
);
CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);
CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);

CREATE TABLE public.cache (
    key varchar(255) NOT NULL,
    value text NOT NULL,
    expiration int4 NOT NULL,
    CONSTRAINT cache_pkey PRIMARY KEY (key)
);
CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);

CREATE TABLE public.cache_locks (
    key varchar(255) NOT NULL,
    owner varchar(255) NOT NULL,
    expiration int4 NOT NULL,
    CONSTRAINT cache_locks_pkey PRIMARY KEY (key)
);
CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);

CREATE TABLE public.jobs (
    id bigserial NOT NULL,
    queue varchar(255) NOT NULL,
    payload text NOT NULL,
    attempts int2 NOT NULL,
    reserved_at int4 NULL,
    available_at int4 NOT NULL,
    created_at int4 NOT NULL,
    CONSTRAINT jobs_pkey PRIMARY KEY (id)
);
CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);

CREATE TABLE public.job_batches (
    id varchar(255) NOT NULL,
    name varchar(255) NOT NULL,
    total_jobs int4 NOT NULL,
    pending_jobs int4 NOT NULL,
    failed_jobs int4 NOT NULL,
    failed_job_ids text NOT NULL,
    options text NULL,
    cancelled_at int4 NULL,
    created_at int4 NOT NULL,
    finished_at int4 NULL,
    CONSTRAINT job_batches_pkey PRIMARY KEY (id)
);

CREATE TABLE public.failed_jobs (
    id bigserial NOT NULL,
    uuid varchar(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamptz DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT failed_jobs_pkey PRIMARY KEY (id),
    CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid)
);

CREATE TABLE public.migrations (
    id serial4 NOT NULL,
    migration varchar(255) NOT NULL,
    batch int4 NOT NULL,
    CONSTRAINT migrations_pkey PRIMARY KEY (id)
);

CREATE TABLE public.password_reset_tokens (
    email varchar(255) NOT NULL,
    token varchar(255) NOT NULL,
    created_at timestamptz NULL,
    CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email)
);

-- Notifications: schema Laravel estándar (sin columnas custom)
CREATE TABLE public.notifications (
    id uuid NOT NULL,
    type varchar(255) NOT NULL,
    notifiable_type varchar(255) NOT NULL,
    notifiable_id int8 NOT NULL,
    data json NOT NULL,
    read_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT notifications_pkey PRIMARY KEY (id)
);
CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);

CREATE TABLE public.user_tour_progress (
    id bigserial NOT NULL,
    user_id int8 NOT NULL,
    tour_key varchar(255) NOT NULL,
    version int2 DEFAULT 1 NOT NULL,
    state varchar(255) DEFAULT 'completed' NOT NULL,
    seen_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT user_tour_progress_pkey PRIMARY KEY (id),
    CONSTRAINT user_tour_progress_user_id_tour_key_unique UNIQUE (user_id, tour_key),
    CONSTRAINT user_tour_progress_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE
);

-- ============================================================================
-- 2. SETTINGS
-- ============================================================================

CREATE TABLE public.app_settings (
    id bigserial NOT NULL,
    key varchar(255) NOT NULL,
    value jsonb NULL,
    description varchar(255) NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT app_settings_pkey PRIMARY KEY (id),
    CONSTRAINT app_settings_key_unique UNIQUE (key)
);

CREATE TABLE public.operational_settings (
    id bigserial NOT NULL,
    key varchar(255) NOT NULL,
    value varchar(255) NOT NULL,
    description varchar(255) NULL,
    category varchar(255) DEFAULT 'threshold' NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT operational_settings_pkey PRIMARY KEY (id),
    CONSTRAINT operational_settings_key_unique UNIQUE (key)
);

CREATE TABLE public.notification_configs (
    id bigserial NOT NULL,
    event_type varchar(255) NOT NULL,
    label varchar(255) NOT NULL,
    description text NULL,
    is_enabled bool DEFAULT true NOT NULL,
    channels jsonb NOT NULL,
    recipient_type varchar(255) NULL,
    recipient_config jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT notification_configs_pkey PRIMARY KEY (id),
    CONSTRAINT notification_configs_event_type_unique UNIQUE (event_type)
);

CREATE TABLE public.alert_rules (
    id bigserial NOT NULL,
    event_type varchar(255) NOT NULL,
    label varchar(255) NOT NULL,
    description text NULL,
    is_enabled bool DEFAULT true NOT NULL,
    threshold_seconds int4 NULL,
    escalation_minutes jsonb NULL,
    escalation_roles jsonb NULL,
    channels jsonb DEFAULT '["database", "broadcast"]'::jsonb NOT NULL,
    cooldown_minutes int4 DEFAULT 15 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT alert_rules_pkey PRIMARY KEY (id),
    CONSTRAINT alert_rules_event_type_unique UNIQUE (event_type)
);

-- ============================================================================
-- 3. GEO (Panamá)
-- ============================================================================

CREATE TABLE public.provinces (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    code varchar(10) NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT provinces_pkey PRIMARY KEY (id),
    CONSTRAINT provinces_name_unique UNIQUE (name),
    CONSTRAINT provinces_code_unique UNIQUE (code)
);

CREATE TABLE public.districts (
    id bigserial NOT NULL,
    province_id int8 NOT NULL,
    name varchar(255) NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT districts_pkey PRIMARY KEY (id),
    CONSTRAINT districts_province_id_name_unique UNIQUE (province_id, name),
    CONSTRAINT districts_province_id_foreign FOREIGN KEY (province_id) REFERENCES public.provinces(id) ON DELETE CASCADE
);

CREATE TABLE public.townships (
    id bigserial NOT NULL,
    district_id int8 NOT NULL,
    name varchar(255) NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT townships_pkey PRIMARY KEY (id),
    CONSTRAINT townships_district_id_name_unique UNIQUE (district_id, name),
    CONSTRAINT townships_district_id_foreign FOREIGN KEY (district_id) REFERENCES public.districts(id) ON DELETE CASCADE
);

-- ============================================================================
-- 4. ORGANIZATION
-- ============================================================================

CREATE TABLE public.employment_statuses (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    code varchar(50) NULL,
    description text NULL,
    is_active bool DEFAULT true NOT NULL,
    parent_id int8 NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT employment_statuses_pkey PRIMARY KEY (id),
    CONSTRAINT employment_statuses_name_unique UNIQUE (name),
    CONSTRAINT employment_statuses_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.employment_statuses(id) ON DELETE SET NULL
);
CREATE INDEX employment_statuses_parent_idx ON public.employment_statuses USING btree (parent_id);

CREATE TABLE public.directorates (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    description text NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    deleted_at timestamptz NULL,
    CONSTRAINT directorates_pkey PRIMARY KEY (id),
    CONSTRAINT directorates_name_unique UNIQUE (name)
);

CREATE TABLE public.departments (
    id bigserial NOT NULL,
    directorate_id int8 NOT NULL,
    name varchar(255) NOT NULL,
    description text NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    deleted_at timestamptz NULL,
    CONSTRAINT departments_pkey PRIMARY KEY (id),
    CONSTRAINT departments_name_directorate_id_unique UNIQUE (name, directorate_id),
    CONSTRAINT departments_directorate_id_foreign FOREIGN KEY (directorate_id) REFERENCES public.directorates(id) ON DELETE CASCADE
);

CREATE TABLE public.positions (
    id bigserial NOT NULL,
    department_id int8 NOT NULL,
    name varchar(255) NOT NULL,
    description text NULL,
    position_code varchar(255) NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    deleted_at timestamptz NULL,
    CONSTRAINT positions_pkey PRIMARY KEY (id),
    CONSTRAINT positions_position_code_unique UNIQUE (position_code),
    CONSTRAINT positions_department_id_foreign FOREIGN KEY (department_id) REFERENCES public.departments(id) ON DELETE CASCADE
);
-- Salary removed from positions; stored exclusively in employees.salary with strict access policy.
-- No employee_compensation table defined; salary history tracked via analytics_employee_snapshot SCD2 pattern if needed.

-- teams.supervisor_id apunta a users (gestor del equipo), no a employees.
CREATE TABLE public.teams (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    cisco_team_id varchar(255) NULL,
    description text NULL,
    is_active bool DEFAULT true NOT NULL,
    supervisor_id int8 NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT teams_pkey PRIMARY KEY (id),
    CONSTRAINT teams_name_unique UNIQUE (name),
    CONSTRAINT teams_cisco_team_id_unique UNIQUE (cisco_team_id),
    CONSTRAINT teams_supervisor_id_foreign FOREIGN KEY (supervisor_id) REFERENCES public.users(id) ON DELETE SET NULL
);

-- ORGANIZATIONAL UNITS: Estructura jerárquica multinivel.
-- head_employee_id (cabeza de la unidad) apunta a users.
-- Se crea ANTES de employees (employees.organizational_unit_id lo referencia).
CREATE TABLE public.organizational_units (
    id bigserial NOT NULL,
    parent_id int8 NULL,
    code varchar(50) NOT NULL,
    name varchar(255) NOT NULL,
    acronym varchar(20) NULL,
    level varchar(50) NOT NULL,  -- direction, management, coordination, supervision, operational
    description text NULL,
    head_employee_id int8 NULL,  -- cabeza de la unidad (puede ser diferente de parent_id)
    is_active bool DEFAULT true NOT NULL,
    sort_order int4 DEFAULT 0 NOT NULL,
    metadata jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    deleted_at timestamptz NULL,
    CONSTRAINT organizational_units_pkey PRIMARY KEY (id),
    CONSTRAINT organizational_units_code_unique UNIQUE (code),
    CONSTRAINT organizational_units_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.organizational_units(id) ON DELETE SET NULL,
    CONSTRAINT organizational_units_head_employee_id_foreign FOREIGN KEY (head_employee_id) REFERENCES public.users(id) ON DELETE SET NULL
);

CREATE INDEX organizational_units_parent_id_index ON public.organizational_units USING btree (parent_id);
CREATE INDEX organizational_units_level_index ON public.organizational_units USING btree (level);
CREATE INDEX organizational_units_is_active_index ON public.organizational_units USING btree (is_active);
CREATE INDEX organizational_units_head_employee_id_index ON public.organizational_units USING btree (head_employee_id);

-- ============================================================================
-- 5. PERSONNEL
-- ============================================================================

CREATE TABLE public.employees (
    id bigserial NOT NULL,
    employee_number varchar(20) NOT NULL,
    username varchar(255) NOT NULL,
    cisco_username varchar(255) NULL,
    first_name varchar(255) NOT NULL,
    last_name varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    birth_date date NULL,
    gender varchar(10) NULL,
    blood_type varchar(5) NULL,
    phone varchar(20) NULL,          -- considerar cifrado Laravel Encrypted casting
    mobile_phone varchar(20) NULL,
    address text NULL,
    township_id int8 NULL,
    organizational_unit_id int8 NULL,
    department_id int8 NULL,
    position_id int8 NULL,           -- posición actual (denormalizada para performance)
    employment_status_id int8 NULL,
    team_id int8 NULL,               -- equipo actual (denormalizada para performance)
    parent_id int8 NULL,             -- supervisor (apunta a users, ver nota)
    user_id int8 NULL,
    hire_date date NULL,
    salary numeric(12, 2) NULL,      -- REQUIERE política de acceso estricta
    is_active bool DEFAULT true NOT NULL,
    is_manager bool DEFAULT false NOT NULL,
    metadata jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    deleted_at timestamptz NULL,
    CONSTRAINT employees_pkey PRIMARY KEY (id),
    CONSTRAINT employees_employee_number_unique UNIQUE (employee_number),
    CONSTRAINT employees_username_unique UNIQUE (username),
    CONSTRAINT employees_email_unique UNIQUE (email),
    CONSTRAINT employees_cisco_username_unique UNIQUE (cisco_username),
    CONSTRAINT employees_parent_not_self CHECK ((parent_id <> id)),
    CONSTRAINT employees_township_id_foreign FOREIGN KEY (township_id) REFERENCES public.townships(id) ON DELETE SET NULL,
    CONSTRAINT employees_organizational_unit_id_foreign FOREIGN KEY (organizational_unit_id) REFERENCES public.organizational_units(id) ON DELETE SET NULL,
    CONSTRAINT employees_department_id_foreign FOREIGN KEY (department_id) REFERENCES public.departments(id) ON DELETE SET NULL,
    CONSTRAINT employees_position_id_foreign FOREIGN KEY (position_id) REFERENCES public.positions(id) ON DELETE RESTRICT,
    CONSTRAINT employees_employment_status_id_foreign FOREIGN KEY (employment_status_id) REFERENCES public.employment_statuses(id) ON DELETE SET NULL,
    CONSTRAINT employees_team_id_foreign FOREIGN KEY (team_id) REFERENCES public.teams(id) ON DELETE SET NULL,
    CONSTRAINT employees_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.users(id) ON DELETE SET NULL,
    CONSTRAINT employees_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL
);
CREATE INDEX employees_team_status_deleted_idx ON public.employees USING btree (team_id, employment_status_id, deleted_at);

-- Nota (decisión): employees.parent_id es el supervisor / línea de reporte.
-- Con la regla "actores/gestores → users", el supervisor apunta a users.
-- employees.user_id es el enlace opcional a la credencial de acceso; un
-- empleado puede existir sin user (p.ej. recién importado).

CREATE TABLE public.employee_positions (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    position_id int8 NOT NULL,
    start_date date DEFAULT CURRENT_DATE NOT NULL,
    end_date date NULL,
    is_primary bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT employee_positions_pkey PRIMARY KEY (id),
    CONSTRAINT employee_positions_employee_id_position_id_start_date_unique UNIQUE (employee_id, position_id, start_date),
    CONSTRAINT employee_positions_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE,
    CONSTRAINT employee_positions_position_id_foreign FOREIGN KEY (position_id) REFERENCES public.positions(id) ON DELETE CASCADE
);

CREATE TABLE public.team_members (
    id bigserial NOT NULL,
    team_id int8 NOT NULL,
    employee_id int8 NOT NULL,
    joined_at date DEFAULT CURRENT_DATE NOT NULL,
    left_at date NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT team_members_pkey PRIMARY KEY (id),
    CONSTRAINT team_members_team_id_employee_id_joined_at_unique UNIQUE (team_id, employee_id, joined_at),
    CONSTRAINT team_members_team_id_foreign FOREIGN KEY (team_id) REFERENCES public.teams(id) ON DELETE CASCADE,
    CONSTRAINT team_members_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE
);

CREATE TABLE public.skills (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    code varchar(50) NOT NULL,
    description text NULL,
    category varchar(100) NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT skills_pkey PRIMARY KEY (id),
    CONSTRAINT skills_code_unique UNIQUE (code)
);
CREATE INDEX skills_category_index ON public.skills USING btree (category);
CREATE INDEX skills_is_active_index ON public.skills USING btree (is_active);

CREATE TABLE public.employee_skills (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    skill_id int8 NOT NULL,
    level int2 DEFAULT 1 NOT NULL,
    years_experience numeric(4, 1) NULL,
    is_primary bool DEFAULT false NOT NULL,
    certified_at date NULL,
    expires_at date NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT employee_skills_pkey PRIMARY KEY (id),
    CONSTRAINT employee_skills_employee_id_skill_id_unique UNIQUE (employee_id, skill_id),
    CONSTRAINT employee_skills_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id),
    CONSTRAINT employee_skills_skill_id_foreign FOREIGN KEY (skill_id) REFERENCES public.skills(id)
);
CREATE INDEX employee_skills_level_index ON public.employee_skills USING btree (level);
CREATE INDEX employee_skills_is_primary_index ON public.employee_skills USING btree (is_primary);

CREATE TABLE public.skill_history (
    id bigserial NOT NULL,
    employee_skill_id int8 NULL,
    employee_id int8 NOT NULL,
    skill_id int8 NOT NULL,
    old_level int2 NULL,
    new_level int2 NOT NULL,
    changed_by int8 NULL,
    changed_at timestamptz NOT NULL,
    reason text NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT skill_history_pkey PRIMARY KEY (id),
    CONSTRAINT skill_history_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id),
    CONSTRAINT skill_history_skill_id_foreign FOREIGN KEY (skill_id) REFERENCES public.skills(id),
    CONSTRAINT skill_history_employee_skill_id_foreign FOREIGN KEY (employee_skill_id) REFERENCES public.employee_skills(id),
    CONSTRAINT skill_history_changed_by_foreign FOREIGN KEY (changed_by) REFERENCES public.users(id)
);
CREATE INDEX skill_history_employee_id_index ON public.skill_history USING btree (employee_id);
CREATE INDEX skill_history_skill_id_index ON public.skill_history USING btree (skill_id);
CREATE INDEX skill_history_changed_at_index ON public.skill_history USING btree (changed_at);

CREATE TABLE public.disability_types (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    description text NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT disability_types_pkey PRIMARY KEY (id),
    CONSTRAINT disability_types_name_unique UNIQUE (name)
);

CREATE TABLE public.disease_types (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    description text NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT disease_types_pkey PRIMARY KEY (id),
    CONSTRAINT disease_types_name_unique UNIQUE (name)
);

CREATE TABLE public.employee_dependents (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    name varchar(255) NOT NULL,
    relationship varchar(50) NOT NULL,
    birth_date date NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT employee_dependents_pkey PRIMARY KEY (id),
    CONSTRAINT employee_dependents_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE
);

CREATE TABLE public.employee_disabilities (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    disability_type_id int8 NOT NULL,
    notes text NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT employee_disabilities_pkey PRIMARY KEY (id),
    CONSTRAINT employee_disabilities_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE,
    CONSTRAINT employee_disabilities_disability_type_id_foreign FOREIGN KEY (disability_type_id) REFERENCES public.disability_types(id) ON DELETE CASCADE
);

CREATE TABLE public.employee_diseases (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    disease_type_id int8 NOT NULL,
    notes text NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT employee_diseases_pkey PRIMARY KEY (id),
    CONSTRAINT employee_diseases_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE,
    CONSTRAINT employee_diseases_disease_type_id_foreign FOREIGN KEY (disease_type_id) REFERENCES public.disease_types(id) ON DELETE CASCADE
);

CREATE TABLE public.employee_import_batches (
    id bigserial NOT NULL,
    batch_id varchar(255) NULL,
    original_filename varchar(255) NOT NULL,
    stored_path varchar(255) NOT NULL,
    status varchar(32) DEFAULT 'pending' NOT NULL,
    chunk_size int4 DEFAULT 1000 NOT NULL,
    total_rows int4 DEFAULT 0 NOT NULL,
    processed_rows int4 DEFAULT 0 NOT NULL,
    imported_rows int4 DEFAULT 0 NOT NULL,
    rejected_rows int4 DEFAULT 0 NOT NULL,
    errors jsonb NULL,
    created_by int8 NULL,
    started_at timestamptz NULL,
    finished_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT employee_import_batches_pkey PRIMARY KEY (id),
    CONSTRAINT employee_import_batches_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL
);
CREATE INDEX employee_import_batches_batch_id_index ON public.employee_import_batches USING btree (batch_id);
CREATE INDEX employee_import_batches_status_index ON public.employee_import_batches USING btree (status);

-- ============================================================================
-- 6. WFM CORE: Schedules, Assignments, Exceptions
-- ============================================================================

CREATE TABLE public.schedules (
    id bigserial NOT NULL,
    name varchar(100) NOT NULL,
    start_time time(0) NOT NULL,
    end_time time(0) NOT NULL,
    total_minutes int4 NOT NULL,
    break_minutes int4 DEFAULT 15 NOT NULL,
    lunch_minutes int4 DEFAULT 45 NOT NULL,
    is_lunch_paid bool DEFAULT true NOT NULL,
    is_break_paid bool DEFAULT true NOT NULL,
    allowed_days jsonb DEFAULT '[1, 2, 3, 4, 5]'::jsonb NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT schedules_pkey PRIMARY KEY (id),
    CONSTRAINT schedules_name_unique UNIQUE (name)
);

CREATE TABLE public.weekly_schedules (
    id bigserial NOT NULL,
    week_start_date date NOT NULL,
    week_end_date date NOT NULL,
    status varchar(20) DEFAULT 'draft' NOT NULL,
    published_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT weekly_schedules_pkey PRIMARY KEY (id)
);
-- Índice parcial: solo un publicado por semana, pero múltiples drafts permitidos
CREATE UNIQUE INDEX weekly_schedules_published_unique ON public.weekly_schedules USING btree (week_start_date) WHERE (status::text = 'published'::text);
CREATE INDEX weekly_schedules_status_index ON public.weekly_schedules USING btree (status);

CREATE TABLE public.weekly_schedule_assignments (
    id bigserial NOT NULL,
    weekly_schedule_id int8 NOT NULL,
    employee_id int8 NOT NULL,
    schedule_id int8 NOT NULL,
    day_of_week int2 NOT NULL,
    start_time time(0) NULL,
    end_time time(0) NULL,
    lunch_start_time time(0) NULL,
    lunch_end_time time(0) NULL,
    break_start_time time(0) NULL,
    break_end_time time(0) NULL,
    swap_request_id int8 NULL,
    is_replaced bool DEFAULT false NOT NULL,
    replaced_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT weekly_schedule_assignments_pkey PRIMARY KEY (id),
    CONSTRAINT weekly_schedule_assignments_weekly_schedule_id_foreign FOREIGN KEY (weekly_schedule_id) REFERENCES public.weekly_schedules(id) ON DELETE CASCADE,
    CONSTRAINT weekly_schedule_assignments_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE,
    CONSTRAINT weekly_schedule_assignments_schedule_id_foreign FOREIGN KEY (schedule_id) REFERENCES public.schedules(id)
);
CREATE INDEX idx_weekly_schedule_assignments_lookup ON public.weekly_schedule_assignments USING btree (weekly_schedule_id, employee_id, day_of_week);
CREATE UNIQUE INDEX ws_assignments_active_unique ON public.weekly_schedule_assignments USING btree (weekly_schedule_id, employee_id, day_of_week) WHERE (is_replaced = false);

CREATE TABLE public.weekly_team_assignments (
    id bigserial NOT NULL,
    weekly_schedule_id int8 NOT NULL,
    team_id int8 NOT NULL,
    schedule_id int8 NOT NULL,
    day_of_week int2 NOT NULL,
    start_time time(0) NULL,
    end_time time(0) NULL,
    lunch_start_time time(0) NULL,
    lunch_end_time time(0) NULL,
    break_start_time time(0) NULL,
    break_end_time time(0) NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT weekly_team_assignments_pkey PRIMARY KEY (id),
    CONSTRAINT ws_team_assignments_unique UNIQUE (weekly_schedule_id, team_id, day_of_week),
    CONSTRAINT weekly_team_assignments_weekly_schedule_id_foreign FOREIGN KEY (weekly_schedule_id) REFERENCES public.weekly_schedules(id) ON DELETE CASCADE,
    CONSTRAINT weekly_team_assignments_team_id_foreign FOREIGN KEY (team_id) REFERENCES public.teams(id) ON DELETE CASCADE,
    CONSTRAINT weekly_team_assignments_schedule_id_foreign FOREIGN KEY (schedule_id) REFERENCES public.schedules(id)
);

CREATE TABLE public.absence_reason_codes (
    id bigserial NOT NULL,
    name varchar(100) NOT NULL,
    short_code varchar(10) NOT NULL,
    requires_attachment bool DEFAULT false NOT NULL,
    is_excused bool DEFAULT true NOT NULL,
    color varchar(20) NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT absence_reason_codes_pkey PRIMARY KEY (id),
    CONSTRAINT absence_reason_codes_name_unique UNIQUE (name),
    CONSTRAINT absence_reason_codes_short_code_unique UNIQUE (short_code)
);

CREATE TABLE public.schedule_exceptions (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    absence_reason_code_id int8 NOT NULL,
    start_at timestamptz NOT NULL,
    end_at timestamptz NOT NULL,
    is_full_day bool DEFAULT true NOT NULL,
    remarks text NULL,
    created_by int8 NULL,            -- actor → users
    metadata jsonb NULL,
    origin_type varchar(255) NULL,
    origin_id int8 NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT schedule_exceptions_pkey PRIMARY KEY (id),
    CONSTRAINT schedule_exceptions_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE,
    CONSTRAINT schedule_exceptions_absence_reason_code_id_foreign FOREIGN KEY (absence_reason_code_id) REFERENCES public.absence_reason_codes(id) ON DELETE CASCADE,
    CONSTRAINT schedule_exceptions_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL
);
CREATE INDEX schedule_exceptions_employee_id_start_at_end_at_index ON public.schedule_exceptions USING btree (employee_id, start_at, end_at);
CREATE INDEX schedule_exceptions_origin_type_origin_id_index ON public.schedule_exceptions USING btree (origin_type, origin_id);

CREATE TABLE public.shift_swap_requests (
    id bigserial NOT NULL,
    requester_id int8 NOT NULL,      -- actor → users
    recipient_id int8 NOT NULL,      -- actor → users
    start_date date NOT NULL,
    end_date date NULL,
    status varchar(255) DEFAULT 'pending' NOT NULL,
    reason text NULL,
    requester_assignment_snapshot jsonb NULL,
    recipient_assignment_snapshot jsonb NULL,
    rejection_reason text NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT shift_swap_requests_pkey PRIMARY KEY (id),
    CONSTRAINT shift_swap_requests_status_check CHECK (((status)::text = ANY (ARRAY['pending', 'accepted', 'rejected', 'approved', 'cancelled']))),
    CONSTRAINT shift_swap_requests_requester_id_foreign FOREIGN KEY (requester_id) REFERENCES public.users(id) ON DELETE CASCADE,
    CONSTRAINT shift_swap_requests_recipient_id_foreign FOREIGN KEY (recipient_id) REFERENCES public.users(id) ON DELETE CASCADE
);
CREATE INDEX shift_swap_requests_requester_id_status_index ON public.shift_swap_requests USING btree (requester_id, status);
CREATE INDEX shift_swap_requests_recipient_id_status_index ON public.shift_swap_requests USING btree (recipient_id, status);
CREATE INDEX shift_swap_requests_start_date_index ON public.shift_swap_requests USING btree (start_date);

CREATE TABLE public.shift_swap_approvals (
    id bigserial NOT NULL,
    shift_swap_request_id int8 NOT NULL,
    approver_id int8 NOT NULL,       -- actor → users
    status varchar(255) DEFAULT 'pending' NOT NULL,
    comment text NULL,
    step_order int4 DEFAULT 1 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT shift_swap_approvals_pkey PRIMARY KEY (id),
    CONSTRAINT shift_swap_approvals_status_check CHECK (((status)::text = ANY (ARRAY['pending', 'approved', 'rejected']))),
    CONSTRAINT shift_swap_approvals_shift_swap_request_id_foreign FOREIGN KEY (shift_swap_request_id) REFERENCES public.shift_swap_requests(id) ON DELETE CASCADE,
    CONSTRAINT shift_swap_approvals_approver_id_foreign FOREIGN KEY (approver_id) REFERENCES public.users(id) ON DELETE CASCADE
);

CREATE TABLE public.leave_requests (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    type varchar(255) NOT NULL,
    start_time timestamptz NOT NULL,
    end_time timestamptz NOT NULL,
    minutes int4 NOT NULL,
    status varchar(255) DEFAULT 'pending' NOT NULL,
    reason text NULL,
    rejection_reason text NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT leave_requests_pkey PRIMARY KEY (id),
    CONSTRAINT leave_requests_status_check CHECK (((status)::text = ANY (ARRAY['pending', 'approved', 'rejected', 'cancelled']))),
    CONSTRAINT leave_requests_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE
);
CREATE INDEX leave_requests_employee_id_status_type_index ON public.leave_requests USING btree (employee_id, status, type);
CREATE INDEX leave_requests_start_time_index ON public.leave_requests USING btree (start_time);

CREATE TABLE public.leave_request_approvals (
    id bigserial NOT NULL,
    leave_request_id int8 NOT NULL,
    approver_id int8 NOT NULL,       -- actor → users
    status varchar(255) DEFAULT 'pending' NOT NULL,
    comment text NULL,
    step_order int4 DEFAULT 1 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT leave_request_approvals_pkey PRIMARY KEY (id),
    CONSTRAINT leave_request_approvals_status_check CHECK (((status)::text = ANY (ARRAY['pending', 'approved', 'rejected']))),
    CONSTRAINT leave_request_approvals_leave_request_id_foreign FOREIGN KEY (leave_request_id) REFERENCES public.leave_requests(id) ON DELETE CASCADE,
    CONSTRAINT leave_request_approvals_approver_id_foreign FOREIGN KEY (approver_id) REFERENCES public.users(id) ON DELETE CASCADE
);

CREATE TABLE public.temporal_assignments (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    supervisor_id int8 NULL,         -- actor → users
    team_id int8 NULL,
    start_date date NOT NULL,
    end_date date NOT NULL,
    source_type varchar(50) DEFAULT 'shift_swap' NOT NULL,
    source_id int8 NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT temporal_assignments_pkey PRIMARY KEY (id),
    CONSTRAINT temporal_assignments_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE,
    CONSTRAINT temporal_assignments_supervisor_id_foreign FOREIGN KEY (supervisor_id) REFERENCES public.users(id) ON DELETE SET NULL,
    CONSTRAINT temporal_assignments_team_id_foreign FOREIGN KEY (team_id) REFERENCES public.teams(id) ON DELETE SET NULL
);
CREATE INDEX temporal_assignments_employee_id_start_date_end_date_index ON public.temporal_assignments USING btree (employee_id, start_date, end_date);
CREATE INDEX temporal_assignments_team_id_start_date_end_date_index ON public.temporal_assignments USING btree (team_id, start_date, end_date);

-- ============================================================================
-- 7. INTRADAY & ATTENDANCE
-- ============================================================================

CREATE TABLE public.activity_types (
    id bigserial NOT NULL,
    name varchar(50) NOT NULL,
    color varchar(20) NULL,
    is_productive bool DEFAULT false NOT NULL,
    is_paid bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT activity_types_pkey PRIMARY KEY (id),
    CONSTRAINT activity_types_name_unique UNIQUE (name)
);

CREATE TABLE public.scheduled_activity_definitions (
    id bigserial NOT NULL,
    name varchar(150) NOT NULL,
    activity_type_id int8 NOT NULL,
    default_duration_minutes int4 NULL,
    default_location varchar(255) NULL,
    default_instructor varchar(255) NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT scheduled_activity_definitions_pkey PRIMARY KEY (id),
    CONSTRAINT scheduled_activity_definitions_activity_type_id_foreign FOREIGN KEY (activity_type_id) REFERENCES public.activity_types(id) ON DELETE CASCADE
);

CREATE TABLE public.approved_intraday_periods (
    id bigserial NOT NULL,
    team_id int8 NOT NULL,
    activity_definition_id int8 NOT NULL,
    date date NOT NULL,
    start_time time(0) NOT NULL,
    end_time time(0) NOT NULL,
    max_slots int2 DEFAULT 1 NOT NULL,
    notes varchar(500) NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT approved_intraday_periods_pkey PRIMARY KEY (id),
    CONSTRAINT approved_intraday_periods_team_id_foreign FOREIGN KEY (team_id) REFERENCES public.teams(id) ON DELETE CASCADE,
    CONSTRAINT approved_intraday_periods_activity_definition_id_foreign FOREIGN KEY (activity_definition_id) REFERENCES public.scheduled_activity_definitions(id) ON DELETE CASCADE
);
CREATE INDEX approved_intraday_periods_team_id_date_index ON public.approved_intraday_periods USING btree (team_id, date);

CREATE TABLE public.intraday_activities (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    activity_type_id int8 NOT NULL,
    approved_period_id int8 NULL,
    notes text NULL,
    time_range tstzrange NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT intraday_activities_pkey PRIMARY KEY (id),
    CONSTRAINT intraday_no_overlap EXCLUDE USING gist (employee_id WITH =, time_range WITH &&),
    CONSTRAINT intraday_activities_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE,
    CONSTRAINT intraday_activities_activity_type_id_foreign FOREIGN KEY (activity_type_id) REFERENCES public.activity_types(id)
);
CREATE INDEX intraday_activities_approved_period_id_index ON public.intraday_activities USING btree (approved_period_id);

CREATE TABLE public.incident_types (
    id bigserial NOT NULL,
    code varchar(20) NOT NULL,
    name varchar(255) NOT NULL,
    color varchar(50) DEFAULT '#3b82f6' NULL,
    requires_justification bool DEFAULT false NOT NULL,
    affects_availability bool DEFAULT false NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT incident_types_pkey PRIMARY KEY (id),
    CONSTRAINT incident_types_code_unique UNIQUE (code)
);

CREATE TABLE public.attendance_incidents (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    incident_type_id int8 NOT NULL,
    incident_date date NOT NULL,
    start_time time(0) NULL,
    end_time time(0) NULL,
    user_comment text NULL,
    admin_comment text NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT attendance_incidents_pkey PRIMARY KEY (id),
    CONSTRAINT attendance_incidents_employee_id_incident_date_unique UNIQUE (employee_id, incident_date),
    CONSTRAINT attendance_incidents_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE SET NULL,
    CONSTRAINT attendance_incidents_incident_type_id_foreign FOREIGN KEY (incident_type_id) REFERENCES public.incident_types(id) ON DELETE CASCADE
);

-- ============================================================================
-- 8. CONNECT: Cisco Integration, Call Records, Agent States
-- ============================================================================

CREATE TABLE public.channels (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    description varchar(500) NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT channels_pkey PRIMARY KEY (id),
    CONSTRAINT channels_name_unique UNIQUE (name)
);
CREATE INDEX channels_is_active_index ON public.channels USING btree (is_active);

CREATE TABLE public.call_queues (
    id bigserial NOT NULL,
    finesse_queue_id int4 NULL,
    name varchar(255) NOT NULL,
    channel_id int8 NULL,
    description text NULL,
    aht_goal int4 DEFAULT 300 NULL,
    is_active bool DEFAULT true NOT NULL,
    is_quality_evaluable bool DEFAULT false NOT NULL,  -- reemplaza quality_queues
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT call_queues_pkey PRIMARY KEY (id),
    CONSTRAINT call_queues_name_unique UNIQUE (name),
    CONSTRAINT call_queues_finesse_queue_id_unique UNIQUE (finesse_queue_id),
    CONSTRAINT call_queues_channel_id_foreign FOREIGN KEY (channel_id) REFERENCES public.channels(id) ON DELETE SET NULL
);

CREATE TABLE public.queue_skills (
    id bigserial NOT NULL,
    queue_id int8 NOT NULL,
    skill_id int8 NOT NULL,
    priority int2 DEFAULT 0 NOT NULL,
    minimum_level int2 DEFAULT 1 NOT NULL,
    is_required bool DEFAULT false NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT queue_skills_pkey PRIMARY KEY (id),
    CONSTRAINT queue_skills_queue_id_skill_id_unique UNIQUE (queue_id, skill_id),
    CONSTRAINT queue_skills_queue_id_foreign FOREIGN KEY (queue_id) REFERENCES public.call_queues(id),
    CONSTRAINT queue_skills_skill_id_foreign FOREIGN KEY (skill_id) REFERENCES public.skills(id)
);
CREATE INDEX queue_skills_priority_index ON public.queue_skills USING btree (priority);

CREATE TABLE public.case_subtypes (
    id bigserial NOT NULL,
    code varchar(255) NOT NULL,
    queue_id int8 NOT NULL,
    name varchar(255) NOT NULL,
    description text NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT case_subtypes_pkey PRIMARY KEY (id),
    CONSTRAINT case_subtypes_code_unique UNIQUE (code),
    CONSTRAINT case_subtypes_queue_id_code_unique UNIQUE (queue_id, code),
    CONSTRAINT case_subtypes_queue_id_foreign FOREIGN KEY (queue_id) REFERENCES public.call_queues(id) ON DELETE CASCADE
);

CREATE TABLE public.agent_states (
    id bigserial NOT NULL,
    external_code varchar(50) NOT NULL,
    display_name varchar(100) NOT NULL,
    is_productive bool DEFAULT false NOT NULL,
    color_hex varchar(7) DEFAULT '#cbd5e1' NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT agent_states_pkey PRIMARY KEY (id),
    CONSTRAINT agent_states_external_code_unique UNIQUE (external_code)
);

CREATE TABLE public.agent_realtime_states (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    external_id varchar(50) NULL,
    current_state varchar(50) NOT NULL,
    reason_code varchar(50) NULL,
    last_changed_at timestamptz NOT NULL,
    metadata jsonb NULL,
    updated_at timestamptz NOT NULL,
    created_at timestamptz NULL,
    CONSTRAINT agent_realtime_states_pkey PRIMARY KEY (id),
    CONSTRAINT agent_realtime_states_external_id_key UNIQUE (external_id),
    CONSTRAINT agent_realtime_states_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE
);
CREATE INDEX idx_agent_realtime_employee ON public.agent_realtime_states USING btree (employee_id);
CREATE INDEX idx_agent_realtime_state ON public.agent_realtime_states USING btree (current_state);

CREATE TABLE public.agent_state_transitions (
    id bigserial NOT NULL,
    agent_login_id varchar(255) NOT NULL,
    employee_id int8 NULL,
    transition_time timestamptz NOT NULL,
    agent_state varchar(255) NOT NULL,
    reason_code varchar(255) NULL,
    duration int4 DEFAULT 0 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT agent_state_transitions_pkey PRIMARY KEY (id),
    CONSTRAINT agent_transition_unique UNIQUE (agent_login_id, transition_time, agent_state),
    CONSTRAINT agent_state_transitions_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE SET NULL
);
CREATE INDEX agent_state_transitions_agent_login_id_index ON public.agent_state_transitions USING btree (agent_login_id);
CREATE INDEX agent_state_transitions_transition_time_index ON public.agent_state_transitions USING btree (transition_time);
CREATE INDEX agent_state_transitions_employee_id_transition_time_index ON public.agent_state_transitions USING btree (employee_id, transition_time);

-- PII: phone_number y citizen_identifier deben usar Laravel Encrypted Casting.
-- NOTA: citizen_identifier lleva índice btree, pero si se cifra (Encrypted
-- casting) ese índice deja de ser útil para búsquedas por valor; se mantiene
-- el índice para el caso de datos sin cifrar / búsquedas exactas en procesos
-- de importación, pero evaluar un mecanismo alternativo (hash/otro) si se
-- requiere indexar por PII.
CREATE TABLE public.call_records (
    id bigserial NOT NULL,
    cisco_call_id varchar(255) NOT NULL,
    sequence_number int4 DEFAULT 0 NOT NULL,
    queue_id int8 NULL,
    phone_number varchar(255) NOT NULL,
    destination_number varchar(255) NULL,
    dialed_number varchar(255) NULL,
    original_dialed_number varchar(255) NULL,
    ivr_started_at timestamptz NOT NULL,
    ivr_ended_at timestamptz NULL,
    talk_time int4 DEFAULT 0 NOT NULL,
    ring_time int4 DEFAULT 0 NOT NULL,
    work_time int4 DEFAULT 0 NOT NULL,
    queue_time int4 DEFAULT 0 NOT NULL,
    hold_time int4 DEFAULT 0 NOT NULL,
    contact_disposition int2 NULL,
    contact_type int2 NULL,
    employee_id int8 NULL,
    raw_agent_name varchar(255) NULL,
    citizen_identifier varchar(12) NULL,
    case_subtype_id int8 NULL,
    description text NULL,
    status varchar(255) DEFAULT 'pending_operator' NOT NULL,
    closed_at timestamptz NULL,
    application_name varchar(255) NULL,
    node_id int2 NULL,
    originator_type int2 NULL,
    originator_id varchar(255) NULL,
    destination_type int2 NULL,
    destination_id varchar(255) NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT call_records_pkey PRIMARY KEY (id),
    CONSTRAINT call_records_session_sequence_unique UNIQUE (cisco_call_id, sequence_number),
    CONSTRAINT call_records_queue_id_foreign FOREIGN KEY (queue_id) REFERENCES public.call_queues(id) ON DELETE SET NULL,
    CONSTRAINT call_records_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE SET NULL,
    CONSTRAINT call_records_case_subtype_id_foreign FOREIGN KEY (case_subtype_id) REFERENCES public.case_subtypes(id) ON DELETE SET NULL
);
CREATE INDEX call_records_ivr_started_at_index ON public.call_records USING btree (ivr_started_at);
CREATE INDEX call_records_queue_id_ivr_started_at_index ON public.call_records USING btree (queue_id, ivr_started_at);
CREATE INDEX call_records_employee_id_ivr_started_at_index ON public.call_records USING btree (employee_id, ivr_started_at);
CREATE INDEX call_records_status_index ON public.call_records USING btree (status);
CREATE INDEX call_records_contact_disposition_index ON public.call_records USING btree (contact_disposition);
CREATE INDEX call_records_contact_type_index ON public.call_records USING btree (contact_type);
CREATE INDEX call_records_citizen_identifier_index ON public.call_records USING btree (citizen_identifier);

CREATE TABLE public.chat_records (
    id bigserial NOT NULL,
    conversation_id varchar(255) NOT NULL,
    agent_login_id varchar(255) NOT NULL,
    employee_id int8 NULL,
    start_time timestamptz NOT NULL,
    end_time timestamptz NULL,
    accepted_at timestamptz NULL,
    total_duration int4 DEFAULT 0 NOT NULL,
    talk_time int4 DEFAULT 0 NOT NULL,
    author_identifier varchar(255) NULL,
    destination_identifier varchar(255) NULL,
    chat_type varchar(255) NULL,
    chat_source varchar(255) NULL,
    chat_rating varchar(255) NULL,
    raw_agent_name varchar(255) NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT chat_records_pkey PRIMARY KEY (id),
    CONSTRAINT chat_records_conversation_id_unique UNIQUE (conversation_id),
    CONSTRAINT chat_records_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE SET NULL
);
CREATE INDEX chat_records_agent_login_id_index ON public.chat_records USING btree (agent_login_id);
CREATE INDEX chat_records_agent_login_id_start_time_index ON public.chat_records USING btree (agent_login_id, start_time);
CREATE INDEX chat_records_start_time_index ON public.chat_records USING btree (start_time);

CREATE TABLE public.agent_call_performance (
    id bigserial NOT NULL,
    agent_login_id varchar(255) NOT NULL,
    employee_id int8 NULL,
    agent_ext varchar(255) NULL,
    start_time timestamptz NOT NULL,
    end_time timestamptz NULL,
    total_duration int4 DEFAULT 0 NOT NULL,
    talk_time int4 DEFAULT 0 NOT NULL,
    hold_time int4 DEFAULT 0 NOT NULL,
    work_time int4 DEFAULT 0 NOT NULL,
    phone_number varchar(255) NULL,
    ani varchar(255) NULL,
    csq_name varchar(255) NULL,
    call_skill varchar(255) NULL,
    call_type varchar(255) NULL,
    raw_agent_name varchar(255) NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT agent_call_performance_pkey PRIMARY KEY (id),
    CONSTRAINT agent_performance_unique UNIQUE (agent_login_id, start_time),
    CONSTRAINT agent_call_performance_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE SET NULL
);
CREATE INDEX agent_call_performance_agent_login_id_index ON public.agent_call_performance USING btree (agent_login_id);
CREATE INDEX agent_call_performance_start_time_index ON public.agent_call_performance USING btree (start_time);
CREATE INDEX agent_call_performance_employee_id_start_time_index ON public.agent_call_performance USING btree (employee_id, start_time);

CREATE TABLE public.uploaded_files (
    id bigserial NOT NULL,
    agent_call_performance_id int8 NOT NULL,
    original_name varchar(255) NOT NULL,
    path varchar(255) NOT NULL,
    disk varchar(50) DEFAULT 'public' NOT NULL,
    mime_type varchar(127) NULL,
    size int8 NULL,
    uploaded_by int8 NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT uploaded_files_pkey PRIMARY KEY (id),
    CONSTRAINT uploaded_files_agent_call_performance_id_foreign FOREIGN KEY (agent_call_performance_id) REFERENCES public.agent_call_performance(id) ON DELETE CASCADE,
    CONSTRAINT uploaded_files_uploaded_by_foreign FOREIGN KEY (uploaded_by) REFERENCES public.users(id) ON DELETE SET NULL
);

CREATE TABLE public.csq_realtime_stats (
    id bigserial NOT NULL,
    csq_name varchar(255) NOT NULL,
    calls_waiting int4 DEFAULT 0 NOT NULL,
    longest_call_in_queue int4 DEFAULT 0 NOT NULL,
    agents_logged_on int4 DEFAULT 0 NOT NULL,
    agents_talking int4 DEFAULT 0 NOT NULL,
    agents_ready int4 DEFAULT 0 NOT NULL,
    agents_not_ready int4 DEFAULT 0 NOT NULL,
    agents_after_call_work int4 DEFAULT 0 NOT NULL,
    agents_reserved int4 DEFAULT 0 NOT NULL,
    service_level_short_term numeric(5, 2) DEFAULT 0 NOT NULL,
    service_level_long_term numeric(5, 2) DEFAULT 0 NOT NULL,
    calls_abandoned_since_midnight int4 DEFAULT 0 NOT NULL,
    calls_handled_since_midnight int4 DEFAULT 0 NOT NULL,
    total_calls_since_midnight int4 DEFAULT 0 NOT NULL,
    metadata jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT csq_realtime_stats_pkey PRIMARY KEY (id),
    CONSTRAINT csq_realtime_stats_csq_name_unique UNIQUE (csq_name)
);

-- ============================================================================
-- 9. OPERATIONS: Metrics (consolidadas)
-- ============================================================================

CREATE TABLE public.agent_daily_metrics (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    metric_date date NOT NULL,
    login_seconds int4 DEFAULT 0 NOT NULL,
    productive_seconds int4 DEFAULT 0 NOT NULL,
    calls_total int4 DEFAULT 0 NOT NULL,
    handled_calls int4 DEFAULT 0 NOT NULL,
    talk_seconds int4 DEFAULT 0 NOT NULL,
    hold_seconds int4 DEFAULT 0 NOT NULL,
    work_seconds int4 DEFAULT 0 NOT NULL,
    weighted_aht numeric(10, 2) DEFAULT 0 NOT NULL,
    capacity_calls numeric(10, 2) DEFAULT 0 NOT NULL,
    capacity_gap numeric(10, 2) DEFAULT 0 NOT NULL,
    work_units numeric(10, 2) DEFAULT 0 NOT NULL,
    availability_pct numeric(10, 2) DEFAULT 0 NOT NULL,
    efficiency_pct numeric(10, 2) DEFAULT 0 NOT NULL,
    pwi_pct numeric(10, 2) DEFAULT 0 NOT NULL,
    queue_distribution jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT agent_daily_metrics_pkey PRIMARY KEY (id),
    CONSTRAINT agent_daily_metrics_employee_id_metric_date_unique UNIQUE (employee_id, metric_date),
    CONSTRAINT agent_daily_metrics_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE SET NULL
);
CREATE INDEX agent_daily_metrics_metric_date_index ON public.agent_daily_metrics USING btree (metric_date);

CREATE TABLE public.queue_daily_metrics (
    id bigserial NOT NULL,
    queue_id int8 NOT NULL,
    metric_date date NOT NULL,
    offered_calls int4 DEFAULT 0 NOT NULL,
    handled_calls int4 DEFAULT 0 NOT NULL,
    abandoned_calls int4 DEFAULT 0 NOT NULL,
    sl_calls int4 DEFAULT 0 NOT NULL,
    total_talk_seconds int4 DEFAULT 0 NOT NULL,
    total_work_seconds int4 DEFAULT 0 NOT NULL,
    total_hold_seconds int4 DEFAULT 0 NOT NULL,
    total_wait_seconds int4 DEFAULT 0 NOT NULL,
    max_wait_seconds int4 DEFAULT 0 NOT NULL,
    min_wait_seconds int4 DEFAULT 0 NOT NULL,
    total_abandon_seconds int4 DEFAULT 0 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT queue_daily_metrics_pkey PRIMARY KEY (id),
    CONSTRAINT queue_daily_metrics_queue_id_metric_date_unique UNIQUE (queue_id, metric_date),
    CONSTRAINT queue_daily_metrics_queue_id_foreign FOREIGN KEY (queue_id) REFERENCES public.call_queues(id) ON DELETE CASCADE
);

CREATE TABLE public.agent_interval_metrics (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    interval_start timestamptz NOT NULL,
    interval_end timestamptz NOT NULL,
    talk_seconds int4 DEFAULT 0 NOT NULL,
    hold_seconds int4 DEFAULT 0 NOT NULL,
    ready_seconds int4 DEFAULT 0 NOT NULL,
    not_ready_seconds int4 DEFAULT 0 NOT NULL,
    wrap_seconds int4 DEFAULT 0 NOT NULL,
    calls_handled int2 DEFAULT 0 NOT NULL,
    aht_seconds numeric(10, 2) DEFAULT 0 NOT NULL,
    occupancy numeric(5, 2) DEFAULT 0 NOT NULL,
    utilization numeric(5, 2) DEFAULT 0 NOT NULL,
    adherence numeric(5, 2) DEFAULT 0 NOT NULL,
    queue_distribution jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT agent_interval_metrics_pkey PRIMARY KEY (id),
    CONSTRAINT agent_interval_metrics_employee_id_interval_start_unique UNIQUE (employee_id, interval_start),
    CONSTRAINT agent_interval_metrics_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE SET NULL
);
CREATE INDEX agent_interval_metrics_interval_start_index ON public.agent_interval_metrics USING btree (interval_start);

-- ============================================================================
-- 10. FORECAST & CAPACITY
-- ============================================================================

CREATE TABLE public.forecast_groups (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    group_type varchar(50) NOT NULL,
    reference_id varchar(255) NULL,
    description text NULL,
    is_active bool DEFAULT true NOT NULL,
    metadata jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT forecast_groups_pkey PRIMARY KEY (id)
);
CREATE INDEX forecast_groups_group_type_index ON public.forecast_groups USING btree (group_type);
CREATE INDEX forecast_groups_is_active_index ON public.forecast_groups USING btree (is_active);

CREATE TABLE public.forecast_versions (
    id bigserial NOT NULL,
    forecast_group_id int8 NOT NULL,
    version_number int4 NOT NULL,
    name varchar(255) NOT NULL,
    status varchar(50) DEFAULT 'draft' NOT NULL,
    generated_by int8 NULL,
    generated_at timestamptz NULL,
    description text NULL,
    metadata jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT forecast_versions_pkey PRIMARY KEY (id),
    CONSTRAINT forecast_versions_forecast_group_id_version_number_unique UNIQUE (forecast_group_id, version_number),
    CONSTRAINT forecast_versions_forecast_group_id_foreign FOREIGN KEY (forecast_group_id) REFERENCES public.forecast_groups(id),
    CONSTRAINT forecast_versions_generated_by_foreign FOREIGN KEY (generated_by) REFERENCES public.users(id)
);
CREATE INDEX forecast_versions_status_index ON public.forecast_versions USING btree (status);

CREATE TABLE public.forecast_scenarios (
    id bigserial NOT NULL,
    forecast_version_id int8 NOT NULL,
    name varchar(255) NOT NULL,
    scenario_type varchar(50) DEFAULT 'base' NOT NULL,
    multiplier numeric(5, 2) DEFAULT 1 NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    metadata jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT forecast_scenarios_pkey PRIMARY KEY (id),
    CONSTRAINT forecast_scenarios_forecast_version_id_foreign FOREIGN KEY (forecast_version_id) REFERENCES public.forecast_versions(id)
);
CREATE INDEX forecast_scenarios_scenario_type_index ON public.forecast_scenarios USING btree (scenario_type);

CREATE TABLE public.forecast_intervals (
    id bigserial NOT NULL,
    forecast_scenario_id int8 NOT NULL,
    interval_start timestamptz NOT NULL,
    interval_end timestamptz NOT NULL,
    interval_minutes int2 DEFAULT 15 NOT NULL,
    call_volume_forecast int4 DEFAULT 0 NOT NULL,
    talk_time_seconds_forecast int4 DEFAULT 0 NOT NULL,
    aht_seconds_forecast numeric(10, 2) DEFAULT 0 NOT NULL,
    staff_required numeric(10, 2) DEFAULT 0 NOT NULL,
    metadata jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT forecast_intervals_pkey PRIMARY KEY (id),
    CONSTRAINT forecast_intervals_scenario_interval_unique UNIQUE (forecast_scenario_id, interval_start),
    CONSTRAINT forecast_intervals_forecast_scenario_id_foreign FOREIGN KEY (forecast_scenario_id) REFERENCES public.forecast_scenarios(id)
);
CREATE INDEX forecast_intervals_interval_start_index ON public.forecast_intervals USING btree (interval_start);

CREATE TABLE public.forecast_accuracy (
    id bigserial NOT NULL,
    forecast_version_id int8 NULL,
    forecast_scenario_id int8 NULL,
    queue_id int8 NOT NULL,
    evaluation_date date NOT NULL,
    forecast_call_volume int4 DEFAULT 0 NOT NULL,
    actual_call_volume int4 DEFAULT 0 NOT NULL,
    forecast_aht numeric(10, 2) DEFAULT 0 NOT NULL,
    actual_aht numeric(10, 2) DEFAULT 0 NOT NULL,
    volume_error int4 DEFAULT 0 NOT NULL,
    volume_abs_error int4 DEFAULT 0 NOT NULL,
    volume_ape numeric(5, 2) DEFAULT 0 NOT NULL,
    mape numeric(5, 2) DEFAULT 0 NOT NULL,
    bias numeric(5, 2) DEFAULT 0 NOT NULL,
    rmse numeric(10, 2) DEFAULT 0 NOT NULL,
    accuracy numeric(5, 2) DEFAULT 0 NOT NULL,
    metadata jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT forecast_accuracy_pkey PRIMARY KEY (id),
    CONSTRAINT forecast_accuracy_forecast_version_id_foreign FOREIGN KEY (forecast_version_id) REFERENCES public.forecast_versions(id) ON DELETE SET NULL,
    CONSTRAINT forecast_accuracy_forecast_scenario_id_foreign FOREIGN KEY (forecast_scenario_id) REFERENCES public.forecast_scenarios(id) ON DELETE SET NULL,
    CONSTRAINT forecast_accuracy_queue_id_foreign FOREIGN KEY (queue_id) REFERENCES public.call_queues(id) ON DELETE CASCADE
);
CREATE INDEX forecast_accuracy_evaluation_date_queue_id_index ON public.forecast_accuracy USING btree (evaluation_date, queue_id);
CREATE INDEX forecast_accuracy_forecast_version_id_index ON public.forecast_accuracy USING btree (forecast_version_id);

CREATE TABLE public.capacity_plans (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    description text NULL,
    status varchar(50) DEFAULT 'draft' NOT NULL,
    plan_date date NOT NULL,
    generated_by int8 NULL,
    generated_at timestamptz NULL,
    forecast_version_id int8 NULL,
    shrinkage_rate numeric(5, 2) DEFAULT 0 NOT NULL,
    metadata jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT capacity_plans_pkey PRIMARY KEY (id),
    CONSTRAINT capacity_plans_generated_by_foreign FOREIGN KEY (generated_by) REFERENCES public.users(id),
    CONSTRAINT capacity_plans_forecast_version_id_foreign FOREIGN KEY (forecast_version_id) REFERENCES public.forecast_versions(id) ON DELETE SET NULL
);
CREATE INDEX capacity_plans_plan_date_index ON public.capacity_plans USING btree (plan_date);
CREATE INDEX capacity_plans_status_index ON public.capacity_plans USING btree (status);

CREATE TABLE public.capacity_results (
    id bigserial NOT NULL,
    capacity_plan_id int8 NOT NULL,
    queue_id int8 NOT NULL,
    total_intervals int2 DEFAULT 0 NOT NULL,
    intervals_with_gap int2 DEFAULT 0 NOT NULL,
    intervals_with_skill_gap int2 DEFAULT 0 NOT NULL,
    max_gap numeric(10, 2) DEFAULT 0 NOT NULL,
    avg_coverage numeric(5, 2) DEFAULT 0 NOT NULL,
    total_staff_required numeric(10, 2) DEFAULT 0 NOT NULL,
    total_staff_available numeric(10, 2) DEFAULT 0 NOT NULL,
    metadata jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT capacity_results_pkey PRIMARY KEY (id),
    CONSTRAINT capacity_results_capacity_plan_id_queue_id_unique UNIQUE (capacity_plan_id, queue_id),
    CONSTRAINT capacity_results_capacity_plan_id_foreign FOREIGN KEY (capacity_plan_id) REFERENCES public.capacity_plans(id),
    CONSTRAINT capacity_results_queue_id_foreign FOREIGN KEY (queue_id) REFERENCES public.call_queues(id) ON DELETE CASCADE
);
CREATE INDEX capacity_results_queue_id_index ON public.capacity_results USING btree (queue_id);

CREATE TABLE public.capacity_intervals (
    id bigserial NOT NULL,
    capacity_plan_id int8 NOT NULL,
    interval_start timestamptz NOT NULL,
    interval_end timestamptz NOT NULL,
    interval_minutes int2 DEFAULT 15 NOT NULL,
    queue_id int8 NOT NULL,
    forecast_call_volume int4 DEFAULT 0 NOT NULL,
    forecast_aht numeric(10, 2) DEFAULT 0 NOT NULL,
    staff_required numeric(10, 2) DEFAULT 0 NOT NULL,
    staff_scheduled numeric(10, 2) DEFAULT 0 NOT NULL,
    staff_available numeric(10, 2) DEFAULT 0 NOT NULL,
    staff_with_skill numeric(10, 2) DEFAULT 0 NOT NULL,
    coverage numeric(5, 2) DEFAULT 0 NOT NULL,
    gap numeric(10, 2) DEFAULT 0 NOT NULL,
    skill_gap numeric(10, 2) DEFAULT 0 NOT NULL,
    metadata jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT capacity_intervals_pkey PRIMARY KEY (id),
    CONSTRAINT capacity_interval_plan_queue_unique UNIQUE (capacity_plan_id, interval_start, queue_id),
    CONSTRAINT capacity_intervals_capacity_plan_id_foreign FOREIGN KEY (capacity_plan_id) REFERENCES public.capacity_plans(id),
    CONSTRAINT capacity_intervals_queue_id_foreign FOREIGN KEY (queue_id) REFERENCES public.call_queues(id) ON DELETE CASCADE
);
CREATE INDEX capacity_intervals_interval_start_index ON public.capacity_intervals USING btree (interval_start);

CREATE TABLE public.staffing_requirements (
    id bigserial NOT NULL,
    interval_start timestamptz NOT NULL,
    interval_end timestamptz NOT NULL,
    interval_minutes int2 DEFAULT 15 NOT NULL,
    queue_id int8 NOT NULL,
    required_agents numeric(10, 2) DEFAULT 0 NOT NULL,
    scheduled_agents numeric(10, 2) DEFAULT 0 NOT NULL,
    available_agents numeric(10, 2) DEFAULT 0 NOT NULL,
    coverage numeric(5, 2) DEFAULT 0 NOT NULL,
    gap numeric(10, 2) DEFAULT 0 NOT NULL,
    shrinkage_rate numeric(5, 2) DEFAULT 0 NOT NULL,
    forecast_version_id int8 NULL,
    metadata jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT staffing_requirements_pkey PRIMARY KEY (id),
    CONSTRAINT staffing_req_interval_queue_unique UNIQUE (interval_start, queue_id),
    CONSTRAINT staffing_requirements_queue_id_foreign FOREIGN KEY (queue_id) REFERENCES public.call_queues(id) ON DELETE CASCADE,
    CONSTRAINT staffing_requirements_forecast_version_id_foreign FOREIGN KEY (forecast_version_id) REFERENCES public.forecast_versions(id) ON DELETE SET NULL
);
CREATE INDEX staffing_requirements_interval_start_index ON public.staffing_requirements USING btree (interval_start);
CREATE INDEX staffing_requirements_queue_id_index ON public.staffing_requirements USING btree (queue_id);

-- ============================================================================
-- 11. QUALITY
-- ============================================================================

CREATE TABLE public.quality_criteria (
    id bigserial NOT NULL,
    code varchar(50) NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT quality_criteria_pkey PRIMARY KEY (id),
    CONSTRAINT quality_criteria_code_unique UNIQUE (code)
);

CREATE TABLE public.quality_criteria_versions (
    id bigserial NOT NULL,
    criteria_id int8 NOT NULL,
    version int2 NOT NULL,
    criteria_text varchar(255) NOT NULL,
    score int2 NOT NULL,
    description text NULL,
    valid_from date NOT NULL,
    valid_to date NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT quality_criteria_versions_pkey PRIMARY KEY (id),
    CONSTRAINT quality_criteria_versions_criteria_id_version_unique UNIQUE (criteria_id, version),
    CONSTRAINT quality_criteria_versions_criteria_id_foreign FOREIGN KEY (criteria_id) REFERENCES public.quality_criteria(id) ON DELETE CASCADE
);

CREATE TABLE public.quality_red_flag_criteria (
    id bigserial NOT NULL,
    criteria_text varchar(255) NOT NULL,
    penalty int2 NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT quality_red_flag_criteria_pkey PRIMARY KEY (id)
);

CREATE TABLE public.quality_queue_criteria (
    id bigserial NOT NULL,
    queue_id int8 NOT NULL,
    criteria_version_id int8 NOT NULL,
    order int2 NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT quality_queue_criteria_pkey PRIMARY KEY (id),
    CONSTRAINT quality_queue_criteria_queue_id_criteria_version_id_unique UNIQUE (queue_id, criteria_version_id),
    CONSTRAINT quality_queue_criteria_queue_id_foreign FOREIGN KEY (queue_id) REFERENCES public.call_queues(id) ON DELETE CASCADE,
    CONSTRAINT quality_queue_criteria_criteria_version_id_foreign FOREIGN KEY (criteria_version_id) REFERENCES public.quality_criteria_versions(id) ON DELETE CASCADE
);

CREATE TABLE public.quality_evaluations (
    id bigserial NOT NULL,
    queue_id int8 NOT NULL,
    employee_id int8 NOT NULL,
    evaluator_id int8 NOT NULL,
    clip_id int8 NULL,
    call_date date NULL,
    call_time time(0) NULL,
    evaluation_date date NOT NULL,
    evaluation_time time(0) NOT NULL,
    score int2 NULL,
    call_observation text NULL,
    has_redflag bool DEFAULT false NOT NULL,
    status varchar(20) DEFAULT 'active' NOT NULL,
    deleted_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT quality_evaluations_pkey PRIMARY KEY (id),
    CONSTRAINT quality_evaluations_queue_id_foreign FOREIGN KEY (queue_id) REFERENCES public.call_queues(id),
    CONSTRAINT quality_evaluations_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id),
    CONSTRAINT quality_evaluations_evaluator_id_foreign FOREIGN KEY (evaluator_id) REFERENCES public.users(id)
);
CREATE INDEX quality_evaluations_evaluation_date_index ON public.quality_evaluations USING btree (evaluation_date);
CREATE INDEX quality_evaluations_employee_id_index ON public.quality_evaluations USING btree (employee_id);
CREATE INDEX quality_evaluations_evaluator_id_index ON public.quality_evaluations USING btree (evaluator_id);

CREATE TABLE public.quality_evaluation_scores (
    id bigserial NOT NULL,
    evaluation_id int8 NOT NULL,
    criteria_version_id int8 NOT NULL,
    obtained_score int2 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT quality_evaluation_scores_pkey PRIMARY KEY (id),
    CONSTRAINT uq_eval_criteria_version UNIQUE (evaluation_id, criteria_version_id),
    CONSTRAINT quality_evaluation_scores_evaluation_id_foreign FOREIGN KEY (evaluation_id) REFERENCES public.quality_evaluations(id) ON DELETE CASCADE,
    CONSTRAINT quality_evaluation_scores_criteria_version_id_foreign FOREIGN KEY (criteria_version_id) REFERENCES public.quality_criteria_versions(id)
);

CREATE TABLE public.quality_evaluation_red_flags (
    id bigserial NOT NULL,
    evaluation_id int8 NOT NULL,
    red_flag_criteria_id int8 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT quality_evaluation_red_flags_pkey PRIMARY KEY (id),
    CONSTRAINT uq_eval_redflag UNIQUE (evaluation_id, red_flag_criteria_id),
    CONSTRAINT quality_evaluation_red_flags_evaluation_id_foreign FOREIGN KEY (evaluation_id) REFERENCES public.quality_evaluations(id) ON DELETE CASCADE,
    CONSTRAINT quality_evaluation_red_flags_red_flag_criteria_id_foreign FOREIGN KEY (red_flag_criteria_id) REFERENCES public.quality_red_flag_criteria(id)
);

CREATE TABLE public.quality_feedback (
    id bigserial NOT NULL,
    evaluation_id int8 NOT NULL,
    observation text NOT NULL,
    created_by int8 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT quality_feedback_pkey PRIMARY KEY (id),
    CONSTRAINT quality_feedback_evaluation_id_foreign FOREIGN KEY (evaluation_id) REFERENCES public.quality_evaluations(id) ON DELETE CASCADE,
    CONSTRAINT quality_feedback_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id)
);

CREATE TABLE public.quality_calibration_log (
    id bigserial NOT NULL,
    evaluation_id int8 NOT NULL,
    previous_score int2 NOT NULL,
    new_score int2 NOT NULL,
    observation text NULL,
    created_by int8 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT quality_calibration_log_pkey PRIMARY KEY (id),
    CONSTRAINT quality_calibration_log_evaluation_id_foreign FOREIGN KEY (evaluation_id) REFERENCES public.quality_evaluations(id) ON DELETE CASCADE,
    CONSTRAINT quality_calibration_log_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id)
);

-- ============================================================================
-- 12. ANALYTICS: Dimensions & Facts (DW simplificado)
-- ============================================================================

CREATE TABLE public.analytics_calendar_dimension (
    date date NOT NULL,
    day int2 NOT NULL,
    month int2 NOT NULL,
    year int2 NOT NULL,
    quarter int2 NOT NULL,
    day_of_week int2 NOT NULL,
    day_name varchar(10) NOT NULL,
    month_name varchar(10) NOT NULL,
    week_of_year int2 NOT NULL,
    is_weekend bool NOT NULL,
    is_business_day bool DEFAULT true NOT NULL,
    is_holiday bool DEFAULT false NOT NULL,
    holiday_name varchar(100) NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT analytics_calendar_dimension_pkey PRIMARY KEY (date)
);
CREATE INDEX analytics_calendar_dimension_year_index ON public.analytics_calendar_dimension USING btree (year);
CREATE INDEX analytics_calendar_dimension_month_index ON public.analytics_calendar_dimension USING btree (month);
CREATE INDEX analytics_calendar_dimension_is_holiday_index ON public.analytics_calendar_dimension USING btree (is_holiday);

CREATE TABLE public.analytics_time_interval_dimension (
    id smallserial NOT NULL,
    interval_key varchar(5) NOT NULL,
    start_time time(0) NOT NULL,
    end_time time(0) NOT NULL,
    interval_minutes int2 DEFAULT 15 NOT NULL,
    slot_number int2 NOT NULL,
    label varchar(15) NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT analytics_time_interval_dimension_pkey PRIMARY KEY (id),
    CONSTRAINT analytics_time_interval_dimension_interval_key_unique UNIQUE (interval_key),
    CONSTRAINT analytics_time_interval_dimension_slot_number_unique UNIQUE (slot_number)
);

-- SCD Type 2 para histórico de cambios de empleado.
-- Nota: supervisor_id aquí es un SNAPSHOT del estado organizativo del empleado
-- (por eso se mantiene como employees, no users).
CREATE TABLE public.analytics_employee_snapshot (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    valid_from date NOT NULL,
    valid_to date NULL,
    is_current bool DEFAULT true NOT NULL,
    team_id int8 NULL,
    department_id int8 NULL,
    position_id int8 NULL,
    supervisor_id int8 NULL,
    employment_status_id int8 NULL,
    is_active bool NOT NULL,
    metadata jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT analytics_employee_snapshot_pkey PRIMARY KEY (id),
    CONSTRAINT analytics_employee_snapshot_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id),
    CONSTRAINT analytics_employee_snapshot_team_id_foreign FOREIGN KEY (team_id) REFERENCES public.teams(id),
    CONSTRAINT analytics_employee_snapshot_department_id_foreign FOREIGN KEY (department_id) REFERENCES public.departments(id),
    CONSTRAINT analytics_employee_snapshot_position_id_foreign FOREIGN KEY (position_id) REFERENCES public.positions(id),
    CONSTRAINT analytics_employee_snapshot_supervisor_id_foreign FOREIGN KEY (supervisor_id) REFERENCES public.employees(id),
    CONSTRAINT analytics_employee_snapshot_employment_status_id_foreign FOREIGN KEY (employment_status_id) REFERENCES public.employment_statuses(id)
);
CREATE INDEX analytics_employee_snapshot_employee_id_index ON public.analytics_employee_snapshot USING btree (employee_id);
CREATE INDEX analytics_employee_snapshot_is_current_index ON public.analytics_employee_snapshot USING btree (is_current);
CREATE INDEX analytics_employee_snapshot_valid_from_valid_to_index ON public.analytics_employee_snapshot USING btree (valid_from, valid_to);

-- Facts: referencian directamente tablas OLTP (sin dim_ intermediarias).
-- Los "source_*" apuntan a la fila OLTP de origen con FK.

CREATE TABLE public.fact_calls (
    id bigserial NOT NULL,
    date_id date NOT NULL,
    interval_id int8 NULL,
    employee_id int8 NULL,
    queue_id int8 NULL,
    team_id int8 NULL,
    department_id int8 NULL,
    source_call_id int8 NOT NULL,
    talk_seconds int2 DEFAULT 0 NOT NULL,
    hold_seconds int2 DEFAULT 0 NOT NULL,
    wrap_seconds int2 DEFAULT 0 NOT NULL,
    ring_seconds int2 DEFAULT 0 NOT NULL,
    queue_seconds int2 DEFAULT 0 NOT NULL,
    handle_seconds int2 DEFAULT 0 NOT NULL,
    is_abandoned bool DEFAULT false NOT NULL,
    is_handled bool DEFAULT false NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT fact_calls_pkey PRIMARY KEY (id),
    CONSTRAINT fact_calls_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE SET NULL,
    CONSTRAINT fact_calls_queue_id_foreign FOREIGN KEY (queue_id) REFERENCES public.call_queues(id) ON DELETE SET NULL,
    CONSTRAINT fact_calls_team_id_foreign FOREIGN KEY (team_id) REFERENCES public.teams(id) ON DELETE SET NULL,
    CONSTRAINT fact_calls_department_id_foreign FOREIGN KEY (department_id) REFERENCES public.departments(id) ON DELETE SET NULL,
    CONSTRAINT fact_calls_source_call_id_foreign FOREIGN KEY (source_call_id) REFERENCES public.call_records(id) ON DELETE CASCADE
);
CREATE INDEX fact_calls_date_id_index ON public.fact_calls USING btree (date_id);
CREATE INDEX fact_calls_date_id_queue_id_index ON public.fact_calls USING btree (date_id, queue_id);
CREATE INDEX fact_calls_employee_id_index ON public.fact_calls USING btree (employee_id);
CREATE INDEX fact_calls_source_call_id_index ON public.fact_calls USING btree (source_call_id);

CREATE TABLE public.fact_schedule (
    id bigserial NOT NULL,
    date_id date NOT NULL,
    interval_id int8 NOT NULL,
    employee_id int8 NOT NULL,
    team_id int8 NULL,
    department_id int8 NULL,
    scheduled_start time(0) NULL,
    scheduled_end time(0) NULL,
    scheduled_minutes int2 DEFAULT 0 NOT NULL,
    lunch_minutes int2 DEFAULT 0 NOT NULL,
    break_minutes int2 DEFAULT 0 NOT NULL,
    is_off bool DEFAULT false NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT fact_schedule_pkey PRIMARY KEY (id),
    CONSTRAINT fact_schedule_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE,
    CONSTRAINT fact_schedule_team_id_foreign FOREIGN KEY (team_id) REFERENCES public.teams(id) ON DELETE SET NULL,
    CONSTRAINT fact_schedule_department_id_foreign FOREIGN KEY (department_id) REFERENCES public.departments(id) ON DELETE SET NULL
);
CREATE INDEX fact_schedule_date_id_employee_id_index ON public.fact_schedule USING btree (date_id, employee_id);

CREATE TABLE public.fact_agent_interval (
    id bigserial NOT NULL,
    date_id date NOT NULL,
    interval_id int8 NOT NULL,
    employee_id int8 NOT NULL,
    team_id int8 NULL,
    department_id int8 NULL,
    talk_seconds int2 DEFAULT 0 NOT NULL,
    hold_seconds int2 DEFAULT 0 NOT NULL,
    ready_seconds int2 DEFAULT 0 NOT NULL,
    not_ready_seconds int2 DEFAULT 0 NOT NULL,
    wrap_seconds int2 DEFAULT 0 NOT NULL,
    calls_handled int2 DEFAULT 0 NOT NULL,
    aht_seconds numeric(10, 2) DEFAULT 0 NOT NULL,
    occupancy numeric(5, 2) DEFAULT 0 NOT NULL,
    utilization numeric(5, 2) DEFAULT 0 NOT NULL,
    adherence numeric(5, 2) DEFAULT 0 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT fact_agent_interval_pkey PRIMARY KEY (id),
    CONSTRAINT fact_agent_interval_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE,
    CONSTRAINT fact_agent_interval_team_id_foreign FOREIGN KEY (team_id) REFERENCES public.teams(id) ON DELETE SET NULL,
    CONSTRAINT fact_agent_interval_department_id_foreign FOREIGN KEY (department_id) REFERENCES public.departments(id) ON DELETE SET NULL
);
CREATE INDEX fact_agent_interval_date_id_employee_id_index ON public.fact_agent_interval USING btree (date_id, employee_id);

CREATE TABLE public.fact_absence (
    id bigserial NOT NULL,
    date_id date NOT NULL,
    employee_id int8 NOT NULL,
    team_id int8 NULL,
    department_id int8 NULL,
    source_exception_id int8 NULL,
    source_leave_id int8 NULL,
    reason_name varchar(100) NOT NULL,
    duration_minutes int2 DEFAULT 0 NOT NULL,
    is_full_day bool DEFAULT false NOT NULL,
    is_excused bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT fact_absence_pkey PRIMARY KEY (id),
    CONSTRAINT fact_absence_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE,
    CONSTRAINT fact_absence_team_id_foreign FOREIGN KEY (team_id) REFERENCES public.teams(id) ON DELETE SET NULL,
    CONSTRAINT fact_absence_department_id_foreign FOREIGN KEY (department_id) REFERENCES public.departments(id) ON DELETE SET NULL,
    CONSTRAINT fact_absence_source_exception_id_foreign FOREIGN KEY (source_exception_id) REFERENCES public.schedule_exceptions(id) ON DELETE SET NULL,
    CONSTRAINT fact_absence_source_leave_id_foreign FOREIGN KEY (source_leave_id) REFERENCES public.leave_requests(id) ON DELETE SET NULL
);
CREATE INDEX fact_absence_date_id_employee_id_index ON public.fact_absence USING btree (date_id, employee_id);

CREATE TABLE public.fact_quality (
    id bigserial NOT NULL,
    date_id date NOT NULL,
    employee_id int8 NOT NULL,
    queue_id int8 NULL,
    team_id int8 NULL,
    source_evaluation_id int8 NOT NULL,
    score int2 NULL,
    max_score int2 NULL,
    score_pct numeric(5, 2) NULL,
    has_redflag bool DEFAULT false NOT NULL,
    evaluator_id int8 NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT fact_quality_pkey PRIMARY KEY (id),
    CONSTRAINT fact_quality_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE,
    CONSTRAINT fact_quality_queue_id_foreign FOREIGN KEY (queue_id) REFERENCES public.call_queues(id) ON DELETE SET NULL,
    CONSTRAINT fact_quality_team_id_foreign FOREIGN KEY (team_id) REFERENCES public.teams(id) ON DELETE SET NULL,
    CONSTRAINT fact_quality_source_evaluation_id_foreign FOREIGN KEY (source_evaluation_id) REFERENCES public.quality_evaluations(id) ON DELETE CASCADE,
    CONSTRAINT fact_quality_evaluator_id_foreign FOREIGN KEY (evaluator_id) REFERENCES public.users(id) ON DELETE SET NULL
);
CREATE INDEX fact_quality_date_id_employee_id_index ON public.fact_quality USING btree (date_id, employee_id);

CREATE TABLE public.fact_forecast (
    id bigserial NOT NULL,
    date_id date NOT NULL,
    interval_id int8 NOT NULL,
    queue_id int8 NOT NULL,
    forecast_version_id int8 NULL,
    call_volume_forecast int4 DEFAULT 0 NOT NULL,
    talk_seconds_forecast int4 DEFAULT 0 NOT NULL,
    aht_seconds_forecast numeric(10, 2) DEFAULT 0 NOT NULL,
    staff_required_forecast numeric(10, 2) DEFAULT 0 NOT NULL,
    actual_call_volume int4 NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT fact_forecast_pkey PRIMARY KEY (id),
    CONSTRAINT fact_forecast_queue_id_foreign FOREIGN KEY (queue_id) REFERENCES public.call_queues(id) ON DELETE CASCADE,
    CONSTRAINT fact_forecast_forecast_version_id_foreign FOREIGN KEY (forecast_version_id) REFERENCES public.forecast_versions(id) ON DELETE SET NULL
);
CREATE INDEX fact_forecast_date_id_queue_id_index ON public.fact_forecast USING btree (date_id, queue_id);

CREATE TABLE public.fact_staffing (
    id bigserial NOT NULL,
    date_id date NOT NULL,
    interval_id int8 NOT NULL,
    queue_id int8 NOT NULL,
    required_agents numeric(10, 2) DEFAULT 0 NOT NULL,
    scheduled_agents numeric(10, 2) DEFAULT 0 NOT NULL,
    available_agents numeric(10, 2) DEFAULT 0 NOT NULL,
    coverage numeric(5, 2) DEFAULT 0 NOT NULL,
    gap numeric(10, 2) DEFAULT 0 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT fact_staffing_pkey PRIMARY KEY (id),
    CONSTRAINT fact_staffing_queue_id_foreign FOREIGN KEY (queue_id) REFERENCES public.call_queues(id) ON DELETE CASCADE
);
CREATE INDEX fact_staffing_date_id_queue_id_index ON public.fact_staffing USING btree (date_id, queue_id);

-- ============================================================================
-- 13. ALERTS & EVENTS
-- ============================================================================

CREATE TABLE public.alert_events (
    id bigserial NOT NULL,
    alert_rule_id int8 NOT NULL,
    employee_id int8 NULL,
    queue_id varchar(255) NULL,
    source varchar(255) NULL,
    message varchar(255) NOT NULL,
    level varchar(255) DEFAULT 'warning' NOT NULL,
    context jsonb NULL,
    first_triggered_at timestamptz NOT NULL,
    last_triggered_at timestamptz NOT NULL,
    triggered_count int4 DEFAULT 1 NOT NULL,
    is_acknowledged bool DEFAULT false NOT NULL,
    acknowledged_by int8 NULL,
    acknowledged_at timestamptz NULL,
    resolved_at timestamptz NULL,
    expires_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT alert_events_pkey PRIMARY KEY (id),
    CONSTRAINT alert_events_alert_rule_id_foreign FOREIGN KEY (alert_rule_id) REFERENCES public.alert_rules(id) ON DELETE CASCADE,
    CONSTRAINT alert_events_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE SET NULL,
    CONSTRAINT alert_events_acknowledged_by_foreign FOREIGN KEY (acknowledged_by) REFERENCES public.users(id) ON DELETE SET NULL
);
CREATE INDEX alert_events_alert_rule_id_employee_id_resolved_at_index ON public.alert_events USING btree (alert_rule_id, employee_id, resolved_at);
CREATE INDEX alert_events_employee_id_level_resolved_at_index ON public.alert_events USING btree (employee_id, level, resolved_at);

CREATE TABLE public.alert_escalations (
    id bigserial NOT NULL,
    alert_event_id int8 NOT NULL,
    escalation_level int2 NOT NULL,
    escalated_to_role varchar(255) NOT NULL,
    escalated_at timestamptz NOT NULL,
    notified_at timestamptz NULL,
    resolved_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT alert_escalations_pkey PRIMARY KEY (id),
    CONSTRAINT alert_escalations_alert_event_id_foreign FOREIGN KEY (alert_event_id) REFERENCES public.alert_events(id) ON DELETE CASCADE
);
CREATE INDEX alert_escalations_alert_event_id_escalation_level_index ON public.alert_escalations USING btree (alert_event_id, escalation_level);

-- ============================================================================
-- 14. WORKFLOW
-- ============================================================================

CREATE TABLE public.workflow_requests (
    id bigserial NOT NULL,
    requestable_type varchar(255) NOT NULL,
    requestable_id int8 NOT NULL,
    requester_id int8 NOT NULL,      -- actor → users
    type varchar(255) NOT NULL,
    status varchar(255) DEFAULT 'pending' NOT NULL,
    data jsonb NULL,
    reason text NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    deleted_at timestamptz NULL,
    CONSTRAINT workflow_requests_pkey PRIMARY KEY (id),
    CONSTRAINT workflow_requests_requester_id_foreign FOREIGN KEY (requester_id) REFERENCES public.users(id)
);
CREATE INDEX workflow_requests_requestable_type_requestable_id_index ON public.workflow_requests USING btree (requestable_type, requestable_id);
CREATE INDEX workflow_requests_status_created_at_index ON public.workflow_requests USING btree (status, created_at);

CREATE TABLE public.workflow_approvals (
    id bigserial NOT NULL,
    workflow_request_id int8 NOT NULL,
    approver_id int8 NOT NULL,       -- actor → users
    step_order int2 NOT NULL,
    status varchar(255) DEFAULT 'pending' NOT NULL,
    comment text NULL,
    decided_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT workflow_approvals_pkey PRIMARY KEY (id),
    CONSTRAINT workflow_approvals_workflow_request_id_step_order_unique UNIQUE (workflow_request_id, step_order),
    CONSTRAINT workflow_approvals_workflow_request_id_foreign FOREIGN KEY (workflow_request_id) REFERENCES public.workflow_requests(id) ON DELETE CASCADE,
    CONSTRAINT workflow_approvals_approver_id_foreign FOREIGN KEY (approver_id) REFERENCES public.users(id)
);

CREATE TABLE public.workflow_delegations (
    id bigserial NOT NULL,
    original_approver_id int8 NOT NULL,  -- actor → users
    delegate_id int8 NOT NULL,           -- actor → users
    start_date date NOT NULL,
    end_date date NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT workflow_delegations_pkey PRIMARY KEY (id),
    CONSTRAINT workflow_delegations_original_approver_id_foreign FOREIGN KEY (original_approver_id) REFERENCES public.users(id),
    CONSTRAINT workflow_delegations_delegate_id_foreign FOREIGN KEY (delegate_id) REFERENCES public.users(id)
);
CREATE INDEX workflow_delegations_original_approver_id_is_active_index ON public.workflow_delegations USING btree (original_approver_id, is_active);

-- ============================================================================
-- 15. SHRINKAGE
-- ============================================================================

CREATE TABLE public.shrinkage_categories (
    id bigserial NOT NULL,
    code varchar(50) NOT NULL,
    name varchar(255) NOT NULL,
    description text NULL,
    is_paid bool DEFAULT true NOT NULL,
    is_planned bool DEFAULT true NOT NULL,
    color varchar(10) NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT shrinkage_categories_pkey PRIMARY KEY (id),
    CONSTRAINT shrinkage_categories_code_unique UNIQUE (code)
);

CREATE TABLE public.historical_shrinkage (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    shrinkage_category_id int8 NOT NULL,
    date date NOT NULL,
    interval_start timestamptz NULL,
    interval_end timestamptz NULL,
    duration_minutes int2 NOT NULL,
    source_type varchar(50) NOT NULL,
    source_id varchar(255) NULL,
    notes text NULL,
    metadata jsonb NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT historical_shrinkage_pkey PRIMARY KEY (id),
    CONSTRAINT historical_shrinkage_unique_source UNIQUE (employee_id, interval_start, source_type, source_id),
    CONSTRAINT historical_shrinkage_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id),
    CONSTRAINT historical_shrinkage_shrinkage_category_id_foreign FOREIGN KEY (shrinkage_category_id) REFERENCES public.shrinkage_categories(id)
);
CREATE INDEX historical_shrinkage_date_index ON public.historical_shrinkage USING btree (date);
CREATE INDEX historical_shrinkage_employee_id_index ON public.historical_shrinkage USING btree (employee_id);
CREATE INDEX historical_shrinkage_shrinkage_category_id_index ON public.historical_shrinkage USING btree (shrinkage_category_id);

-- ============================================================================
-- 16. COMMUNICATIONS (no-WFM retenido)
-- ============================================================================

CREATE TABLE public.categories (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    slug varchar(255) NOT NULL,
    description text NULL,
    color varchar(7) DEFAULT '#3B82F6' NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    sort_order int4 DEFAULT 0 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT categories_pkey PRIMARY KEY (id),
    CONSTRAINT categories_slug_unique UNIQUE (slug)
);
CREATE INDEX categories_is_active_sort_order_index ON public.categories USING btree (is_active, sort_order);

CREATE TABLE public.categorizables (
    id bigserial NOT NULL,
    category_id int8 NOT NULL,
    categorizable_type varchar(255) NOT NULL,
    categorizable_id int8 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT categorizables_pkey PRIMARY KEY (id),
    CONSTRAINT categorizables_unique UNIQUE (category_id, categorizable_type, categorizable_id),
    CONSTRAINT categorizables_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE CASCADE
);
CREATE INDEX categorizables_categorizable_type_categorizable_id_index ON public.categorizables USING btree (categorizable_type, categorizable_id);

CREATE TABLE public.tags (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    slug varchar(255) NOT NULL,
    color varchar(7) DEFAULT '#6B7280' NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT tags_pkey PRIMARY KEY (id),
    CONSTRAINT tags_slug_unique UNIQUE (slug)
);
CREATE INDEX tags_is_active_index ON public.tags USING btree (is_active);

CREATE TABLE public.taggables (
    id bigserial NOT NULL,
    tag_id int8 NOT NULL,
    taggable_type varchar(255) NOT NULL,
    taggable_id int8 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT taggables_pkey PRIMARY KEY (id),
    CONSTRAINT taggables_unique UNIQUE (tag_id, taggable_type, taggable_id),
    CONSTRAINT taggables_tag_id_foreign FOREIGN KEY (tag_id) REFERENCES public.tags(id) ON DELETE CASCADE
);
CREATE INDEX taggables_taggable_type_taggable_id_index ON public.taggables USING btree (taggable_type, taggable_id);

CREATE TABLE public.news (
    id bigserial NOT NULL,
    title varchar(255) NOT NULL,
    slug varchar(255) NOT NULL,
    excerpt text NULL,
    content text NOT NULL, -- PostgreSQL `text` equivale a `longText` (ambos mapean a `text` sin límite práctico); registrado para consistencia con el Blueprint
    author_id int8 NOT NULL,
    status varchar(255) DEFAULT 'draft' NOT NULL,
    approved_by int8 NULL,
    approved_at timestamptz NULL,
    moderation_notes text NULL,
    version_history jsonb NULL,
    is_active bool DEFAULT true NOT NULL,
    published_at timestamptz NULL,
    scheduled_at timestamptz NULL,
    archive_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT news_pkey PRIMARY KEY (id),
    CONSTRAINT news_slug_unique UNIQUE (slug),
    CONSTRAINT news_status_check CHECK ((status = ANY (ARRAY['draft', 'pending_review', 'published', 'archived']))),
    CONSTRAINT news_author_id_foreign FOREIGN KEY (author_id) REFERENCES public.users(id),
    CONSTRAINT news_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL
);
CREATE INDEX news_status_created_at_index ON public.news USING btree (status, created_at);
CREATE INDEX news_status_scheduled_at_index ON public.news USING btree (status, scheduled_at);
CREATE INDEX news_status_archive_at_index ON public.news USING btree (status, archive_at);

CREATE TABLE public.comments (
    id bigserial NOT NULL,
    news_id int8 NOT NULL,
    user_id int8 NOT NULL,
    content text NOT NULL,
    parent_id int8 NULL,
    is_active bool DEFAULT true NOT NULL,
    published_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT comments_pkey PRIMARY KEY (id),
    CONSTRAINT comments_news_id_foreign FOREIGN KEY (news_id) REFERENCES public.news(id) ON DELETE CASCADE,
    CONSTRAINT comments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE,
    CONSTRAINT comments_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.comments(id) ON DELETE CASCADE
);
CREATE INDEX comments_news_id_is_active_created_at_index ON public.comments USING btree (news_id, is_active, created_at);
CREATE INDEX comments_user_id_created_at_index ON public.comments USING btree (user_id, created_at);

CREATE TABLE public.polls (
    id bigserial NOT NULL,
    question varchar(255) NOT NULL,
    options jsonb NOT NULL,
    status varchar(255) DEFAULT 'draft' NOT NULL,
    approved_by int8 NULL,
    approved_at timestamptz NULL,
    moderation_notes text NULL,
    version_history jsonb NULL,
    is_active bool DEFAULT true NOT NULL,
    expires_at timestamptz NULL,
    scheduled_at timestamptz NULL,
    archive_at timestamptz NULL,
    reminder_sent_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT polls_pkey PRIMARY KEY (id),
    CONSTRAINT polls_status_check CHECK ((status = ANY (ARRAY['draft', 'pending_review', 'published', 'archived']))),
    CONSTRAINT polls_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL
);
CREATE INDEX polls_status_created_at_index ON public.polls USING btree (status, created_at);
CREATE INDEX polls_reminder_sent_at_index ON public.polls USING btree (reminder_sent_at);

CREATE TABLE public.poll_responses (
    id bigserial NOT NULL,
    poll_id int8 NOT NULL,
    user_id int8 NOT NULL,
    answer varchar(255) NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT poll_responses_pkey PRIMARY KEY (id),
    CONSTRAINT poll_responses_poll_id_user_id_unique UNIQUE (poll_id, user_id),
    CONSTRAINT poll_responses_poll_id_foreign FOREIGN KEY (poll_id) REFERENCES public.polls(id) ON DELETE CASCADE,
    CONSTRAINT poll_responses_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id)
);

-- Nota: shoutouts.employee_id es el SUJETO del reconocimiento (a quién se
-- felicita), no un actor; por eso se mantiene como employees.
CREATE TABLE public.shoutouts (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    message text NOT NULL,
    status varchar(255) DEFAULT 'draft' NOT NULL,
    approved_by int8 NULL,
    approved_at timestamptz NULL,
    moderation_notes text NULL,
    version_history jsonb NULL,
    is_active bool DEFAULT true NOT NULL,
    scheduled_at timestamptz NULL,
    archive_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT shoutouts_pkey PRIMARY KEY (id),
    CONSTRAINT shoutouts_status_check CHECK ((status = ANY (ARRAY['draft', 'pending_review', 'published', 'archived']))),
    CONSTRAINT shoutouts_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE,
    CONSTRAINT shoutouts_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL
);
CREATE INDEX shoutouts_status_created_at_index ON public.shoutouts USING btree (status, created_at);
CREATE INDEX shoutouts_status_scheduled_at_index ON public.shoutouts USING btree (status, scheduled_at);

CREATE TABLE public.reactions (
    id bigserial NOT NULL,
    shoutout_id int8 NOT NULL,
    user_id int8 NOT NULL,
    type varchar(255) NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT reactions_pkey PRIMARY KEY (id),
    CONSTRAINT reactions_shoutout_id_user_id_unique UNIQUE (shoutout_id, user_id),
    CONSTRAINT reactions_type_check CHECK ((type = ANY (ARRAY['like', 'love', 'celebrate', 'support', 'insightful']))),
    CONSTRAINT reactions_shoutout_id_foreign FOREIGN KEY (shoutout_id) REFERENCES public.shoutouts(id) ON DELETE CASCADE,
    CONSTRAINT reactions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE
);
CREATE INDEX reactions_shoutout_id_type_index ON public.reactions USING btree (shoutout_id, type);
CREATE INDEX reactions_user_id_created_at_index ON public.reactions USING btree (user_id, created_at);

CREATE TABLE public.mentions (
    id bigserial NOT NULL,
    mentioned_user_id int8 NOT NULL,
    mentioner_user_id int8 NOT NULL,
    mentionable_type varchar(255) NOT NULL,
    mentionable_id int8 NOT NULL,
    context varchar(255) NULL,
    is_read bool DEFAULT false NOT NULL,
    read_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT mentions_pkey PRIMARY KEY (id),
    CONSTRAINT mentions_mentioned_user_id_foreign FOREIGN KEY (mentioned_user_id) REFERENCES public.users(id) ON DELETE CASCADE,
    CONSTRAINT mentions_mentioner_user_id_foreign FOREIGN KEY (mentioner_user_id) REFERENCES public.users(id) ON DELETE CASCADE
);
CREATE INDEX mentions_mentionable_type_mentionable_id_index ON public.mentions USING btree (mentionable_type, mentionable_id);
CREATE INDEX mentions_mentioned_user_id_is_read_index ON public.mentions USING btree (mentioned_user_id, is_read);

-- ============================================================================
-- 17. DIRECTORY (retenido)
-- ============================================================================

CREATE TABLE public.directory_buildings (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    director_name varchar(255) NOT NULL,
    subdirector_name varchar(255) NULL,
    administrator_name varchar(255) NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT directory_buildings_pkey PRIMARY KEY (id),
    CONSTRAINT directory_buildings_name_unique UNIQUE (name)
);

CREATE TABLE public.directory_units (
    id bigserial NOT NULL,
    building_id int8 NOT NULL,
    sector varchar(255) NULL,
    level varchar(255) NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT directory_units_pkey PRIMARY KEY (id),
    CONSTRAINT directory_units_building_id_foreign FOREIGN KEY (building_id) REFERENCES public.directory_buildings(id) ON DELETE CASCADE
);
CREATE INDEX directory_units_building_id_is_active_index ON public.directory_units USING btree (building_id, is_active);

-- door_id fue migrado de directory_units a directory_services por migración 2026_08_17_074912.
-- Actualmente no existe directory_contacts (embedido en directory_services desde 2026_08_17_081103).
CREATE TABLE public.directory_services (
    id bigserial NOT NULL,
    unit_id int8 NOT NULL,
    name varchar(255) NOT NULL,
    attention_hours varchar(255) NOT NULL,
    results_hours varchar(255) NULL,
    door_id varchar(255) NULL,
    contact_role varchar(255) NULL,
    contact_extension varchar(255) NULL,
    contact_email varchar(255) NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT directory_services_pkey PRIMARY KEY (id),
    CONSTRAINT directory_services_unit_id_name_unique UNIQUE (unit_id, name),
    CONSTRAINT directory_services_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.directory_units(id) ON DELETE CASCADE
);

-- ============================================================================
-- 18. KNOWLEDGE (retenido, consolidado)
-- ============================================================================

CREATE TABLE public.knowledge_categories (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    description text NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT knowledge_categories_pkey PRIMARY KEY (id)
);

CREATE TABLE public.knowledge_tags (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT knowledge_tags_pkey PRIMARY KEY (id),
    CONSTRAINT knowledge_tags_name_unique UNIQUE (name)
);

CREATE TABLE public.knowledge_articles (
    id bigserial NOT NULL,
    title varchar(255) NOT NULL,
    slug varchar(255) NOT NULL,
    summary text NULL,
    content text NOT NULL,
    category_id int8 NULL,
    status varchar(255) DEFAULT 'draft' NOT NULL,
    version int4 DEFAULT 1 NOT NULL,
    published_at timestamptz NULL,
    expires_at timestamptz NULL,
    created_by int8 NOT NULL,
    updated_by int8 NULL,
    directory_unit_id int8 NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT knowledge_articles_pkey PRIMARY KEY (id),
    CONSTRAINT knowledge_articles_slug_unique UNIQUE (slug),
    CONSTRAINT knowledge_articles_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.knowledge_categories(id) ON DELETE SET NULL,
    CONSTRAINT knowledge_articles_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE,
    CONSTRAINT knowledge_articles_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL,
    CONSTRAINT knowledge_articles_directory_unit_id_foreign FOREIGN KEY (directory_unit_id) REFERENCES public.directory_units(id) ON DELETE SET NULL
);
CREATE INDEX knowledge_articles_status_published_at_expires_at_index ON public.knowledge_articles USING btree (status, published_at, expires_at);

CREATE TABLE public.knowledge_article_versions (
    id bigserial NOT NULL,
    article_id int8 NOT NULL,
    version int4 NOT NULL,
    content text NOT NULL,
    created_by int8 NOT NULL,
    created_at timestamptz DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT knowledge_article_versions_pkey PRIMARY KEY (id),
    CONSTRAINT knowledge_article_versions_article_id_version_unique UNIQUE (article_id, version),
    CONSTRAINT knowledge_article_versions_article_id_foreign FOREIGN KEY (article_id) REFERENCES public.knowledge_articles(id) ON DELETE CASCADE,
    CONSTRAINT knowledge_article_versions_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE
);

CREATE TABLE public.knowledge_article_tag (
    article_id int8 NOT NULL,
    tag_id int8 NOT NULL,
    CONSTRAINT knowledge_article_tag_pkey PRIMARY KEY (article_id, tag_id),
    CONSTRAINT knowledge_article_tag_article_id_foreign FOREIGN KEY (article_id) REFERENCES public.knowledge_articles(id) ON DELETE CASCADE,
    CONSTRAINT knowledge_article_tag_tag_id_foreign FOREIGN KEY (tag_id) REFERENCES public.knowledge_tags(id) ON DELETE CASCADE
);

-- ============================================================================
-- 19. FILES (retenido)
-- ============================================================================

CREATE TABLE public.media (
    id bigserial NOT NULL,
    model_type varchar(255) NOT NULL,
    model_id int8 NOT NULL,
    uuid uuid NULL,
    collection_name varchar(255) NOT NULL,
    name varchar(255) NOT NULL,
    file_name varchar(255) NOT NULL,
    mime_type varchar(255) NULL,
    disk varchar(255) NOT NULL,
    conversions_disk varchar(255) NULL,
    size int8 NOT NULL,
    manipulations json NOT NULL,
    custom_properties json NOT NULL,
    generated_conversions json NOT NULL,
    responsive_images json NOT NULL,
    order_column int4 NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT media_pkey PRIMARY KEY (id),
    CONSTRAINT media_uuid_unique UNIQUE (uuid)
);
CREATE INDEX media_model_type_model_id_index ON public.media USING btree (model_type, model_id);
CREATE INDEX media_order_column_index ON public.media USING btree (order_column);

CREATE TABLE public.folders (
    id bigserial NOT NULL,
    uuid uuid NOT NULL,
    user_id int8 NOT NULL,
    parent_id int8 NULL,
    name varchar(255) NOT NULL,
    color varchar(7) DEFAULT '#3b82f6' NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT folders_pkey PRIMARY KEY (id),
    CONSTRAINT folders_uuid_unique UNIQUE (uuid),
    CONSTRAINT folders_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE,
    CONSTRAINT folders_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.folders(id) ON DELETE SET NULL
);
CREATE INDEX folders_user_id_parent_id_index ON public.folders USING btree (user_id, parent_id);

CREATE TABLE public.files (
    id bigserial NOT NULL,
    uuid uuid NOT NULL,
    user_id int8 NOT NULL,
    folder_id int8 NULL,
    name varchar(255) NOT NULL,
    original_name varchar(255) NOT NULL,
    path varchar(255) NOT NULL,
    disk varchar(255) DEFAULT 'local' NOT NULL,
    size int8 NOT NULL,
    mime_type varchar(255) NOT NULL,
    extension varchar(10) NOT NULL,
    is_public bool DEFAULT false NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT files_pkey PRIMARY KEY (id),
    CONSTRAINT files_uuid_unique UNIQUE (uuid),
    CONSTRAINT files_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE,
    CONSTRAINT files_folder_id_foreign FOREIGN KEY (folder_id) REFERENCES public.folders(id) ON DELETE CASCADE
);
CREATE INDEX files_user_id_folder_id_index ON public.files USING btree (user_id, folder_id);

CREATE TABLE public.file_shares (
    id bigserial NOT NULL,
    file_id int8 NULL,
    folder_id int8 NULL,
    user_id int8 NOT NULL,
    shared_by_id int8 NOT NULL,
    access_level varchar(255) DEFAULT 'view' NOT NULL,
    expires_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT file_shares_pkey PRIMARY KEY (id),
    CONSTRAINT file_shares_access_level_check CHECK ((access_level = ANY (ARRAY['view', 'edit', 'admin']))),
    CONSTRAINT idx_file_user_share UNIQUE (file_id, user_id),
    CONSTRAINT idx_folder_user_share UNIQUE (folder_id, user_id),
    CONSTRAINT file_shares_file_id_foreign FOREIGN KEY (file_id) REFERENCES public.files(id) ON DELETE CASCADE,
    CONSTRAINT file_shares_folder_id_foreign FOREIGN KEY (folder_id) REFERENCES public.folders(id) ON DELETE CASCADE,
    CONSTRAINT file_shares_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE,
    CONSTRAINT file_shares_shared_by_id_foreign FOREIGN KEY (shared_by_id) REFERENCES public.users(id) ON DELETE CASCADE
);

CREATE TABLE public.storage_quotas (
    id bigserial NOT NULL,
    target_type varchar(255) NOT NULL,
    target_id int8 NOT NULL,
    quota_limit int8 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT storage_quotas_pkey PRIMARY KEY (id),
    CONSTRAINT storage_quotas_target_type_target_id_unique UNIQUE (target_type, target_id)
);

-- ============================================================================
-- 20. HELPDESK (retenido)
-- ============================================================================

CREATE TABLE public.helpdesk_categories (
    id bigserial NOT NULL,
    name varchar(255) NOT NULL,
    description varchar(255) NULL,
    sla_hours int4 DEFAULT 48 NOT NULL,
    color varchar(20) DEFAULT 'zinc' NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT helpdesk_categories_pkey PRIMARY KEY (id),
    CONSTRAINT helpdesk_categories_name_unique UNIQUE (name)
);

-- creator/assigned_agent/author apuntan a users (actores), no a employees.
CREATE TABLE public.helpdesk_tickets (
    id bigserial NOT NULL,
    subject varchar(255) NOT NULL,
    description text NOT NULL,
    category_id int8 NOT NULL,
    creator_id int8 NOT NULL,
    assigned_agent_id int8 NULL,
    status varchar(255) DEFAULT 'new' NOT NULL,
    priority varchar(255) DEFAULT 'medium' NOT NULL,
    resolved_at timestamptz NULL,
    closed_at timestamptz NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT helpdesk_tickets_pkey PRIMARY KEY (id),
    CONSTRAINT helpdesk_tickets_status_check CHECK ((status = ANY (ARRAY['new', 'open', 'in_progress', 'on_hold', 'resolved', 'closed']))),
    CONSTRAINT helpdesk_tickets_priority_check CHECK ((priority = ANY (ARRAY['low', 'medium', 'high', 'urgent']))),
    CONSTRAINT helpdesk_tickets_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.helpdesk_categories(id),
    CONSTRAINT helpdesk_tickets_creator_id_foreign FOREIGN KEY (creator_id) REFERENCES public.users(id),
    CONSTRAINT helpdesk_tickets_assigned_agent_id_foreign FOREIGN KEY (assigned_agent_id) REFERENCES public.users(id)
);
CREATE INDEX helpdesk_tickets_status_priority_index ON public.helpdesk_tickets USING btree (status, priority);
CREATE INDEX helpdesk_tickets_creator_id_index ON public.helpdesk_tickets USING btree (creator_id);
CREATE INDEX helpdesk_tickets_assigned_agent_id_index ON public.helpdesk_tickets USING btree (assigned_agent_id);

CREATE TABLE public.helpdesk_ticket_comments (
    id bigserial NOT NULL,
    ticket_id int8 NOT NULL,
    author_id int8 NOT NULL,          -- actor → users
    content text NOT NULL,
    is_internal bool DEFAULT false NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT helpdesk_ticket_comments_pkey PRIMARY KEY (id),
    CONSTRAINT helpdesk_ticket_comments_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.helpdesk_tickets(id) ON DELETE CASCADE,
    CONSTRAINT helpdesk_ticket_comments_author_id_foreign FOREIGN KEY (author_id) REFERENCES public.users(id)
);

-- ============================================================================
-- 21. DOCUMENTATION (retenido)
-- ============================================================================

CREATE TABLE public.documentation_articles (
    id bigserial NOT NULL,
    title varchar(255) NOT NULL,
    slug varchar(255) NOT NULL,
    content text NOT NULL,
    is_published bool DEFAULT false NOT NULL,
    author_id int8 NOT NULL,
    view_count int4 DEFAULT 0 NOT NULL,
    sort_order int4 DEFAULT 0 NOT NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT documentation_articles_pkey PRIMARY KEY (id),
    CONSTRAINT documentation_articles_slug_unique UNIQUE (slug),
    CONSTRAINT documentation_articles_author_id_foreign FOREIGN KEY (author_id) REFERENCES public.users(id) ON DELETE CASCADE
);
CREATE INDEX documentation_articles_is_published_sort_order_index ON public.documentation_articles USING btree (is_published, sort_order);
CREATE INDEX documentation_articles_author_id_index ON public.documentation_articles USING btree (author_id);

-- ============================================================================
-- 22. AUDIT
-- ============================================================================

CREATE TABLE public.audit_logs (
    id bigserial NOT NULL,
    entity_type varchar(255) NOT NULL,
    entity_id varchar(255) NOT NULL,
    action varchar(255) NOT NULL,
    before jsonb NULL,
    after jsonb NULL,
    ip_address varchar(45) NULL,
    user_id int8 NULL,
    actor_name varchar(255) NULL,
    actor_email varchar(255) NULL,
    created_at timestamptz NULL,
    updated_at timestamptz NULL,
    CONSTRAINT audit_logs_pkey PRIMARY KEY (id),
    CONSTRAINT audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL
);
CREATE INDEX audit_logs_entity_type_entity_id_index ON public.audit_logs USING btree (entity_type, entity_id);
CREATE INDEX audit_logs_created_at_index ON public.audit_logs USING btree (created_at);

-- Trigger para inmutabilidad
CREATE OR REPLACE FUNCTION public.prevent_audit_log_modification()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'audit_logs are immutable';
END;
$$;

CREATE TRIGGER trg_audit_logs_immutable
BEFORE DELETE OR UPDATE ON public.audit_logs
FOR EACH ROW EXECUTE FUNCTION public.prevent_audit_log_modification();
```
