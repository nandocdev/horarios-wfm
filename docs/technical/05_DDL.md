# DDL

## Cache

```sql
CREATE TABLE public."cache" (
    "key" varchar(255) NOT NULL,
    value text NOT NULL,
    expiration int4 NOT NULL,
    CONSTRAINT cache_pkey PRIMARY KEY (key)
);

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);

```

## Cache locks

```sql
CREATE TABLE public.cache_locks (
    "key" varchar(255) NOT NULL,
    "owner" varchar(255) NOT NULL,
    expiration int4 NOT NULL,
    CONSTRAINT cache_locks_pkey PRIMARY KEY (key)
);

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);

```

## Directorates: `directorates`

```sql
CREATE TABLE public.directorates (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    description text NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT directorates_name_unique UNIQUE (name),
    CONSTRAINT directorates_pkey PRIMARY KEY (id)
);

```

```sql
CREATE TABLE public.disability_types (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    description text NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT disability_types_name_unique UNIQUE (name),
    CONSTRAINT disability_types_pkey PRIMARY KEY (id)
);

```

```sql
CREATE TABLE public.disease_types (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    description text NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT disease_types_name_unique UNIQUE (name),
    CONSTRAINT disease_types_pkey PRIMARY KEY (id)
);

```

```sql
CREATE TABLE public.employment_statuses (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    description text NULL,
    code varchar(50) NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT employment_statuses_name_unique UNIQUE (name),
    CONSTRAINT employment_statuses_pkey PRIMARY KEY (id)
);

```

```sql
CREATE TABLE public.failed_jobs (
    id bigserial NOT NULL,
    "uuid" varchar(255) NOT NULL,
    "connection" text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    "exception" text NOT NULL,
    failed_at timestamp(0) DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT failed_jobs_pkey PRIMARY KEY (id),
    CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid)
);

```

```sql
CREATE TABLE public.incident_types (
    id bigserial NOT NULL,
    code varchar(20) NOT NULL,
    "name" varchar(255) NOT NULL,
    color varchar(255) DEFAULT 'blue' :: character varying NOT NULL,
    requires_justification bool DEFAULT false NOT NULL,
    affects_availability bool DEFAULT false NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT incident_types_code_unique UNIQUE (code),
    CONSTRAINT incident_types_color_check CHECK (
        (
            (color) :: text = ANY (
                (
                    ARRAY ['red'::character varying, 'blue'::character varying, 'white'::character varying]
                ) :: text []
            )
        )
    ),
    CONSTRAINT incident_types_pkey PRIMARY KEY (id)
);

```

```sql
CREATE TABLE public.job_batches (
    id varchar(255) NOT NULL,
    "name" varchar(255) NOT NULL,
    total_jobs int4 NOT NULL,
    pending_jobs int4 NOT NULL,
    failed_jobs int4 NOT NULL,
    failed_job_ids text NOT NULL,
    "options" text NULL,
    cancelled_at int4 NULL,
    created_at int4 NOT NULL,
    finished_at int4 NULL,
    CONSTRAINT job_batches_pkey PRIMARY KEY (id)
);

```

```sql
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

```

```sql
CREATE TABLE public.migrations (
    id serial4 NOT NULL,
    migration varchar(255) NOT NULL,
    batch int4 NOT NULL,
    CONSTRAINT migrations_pkey PRIMARY KEY (id)
);

```

```sql
CREATE TABLE public.notifications (
    id uuid NOT NULL,
    "type" varchar(255) NOT NULL,
    notifiable_type varchar(255) NOT NULL,
    notifiable_id int8 NOT NULL,
    "data" text NOT NULL,
    read_at timestamp(0) NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT notifications_pkey PRIMARY KEY (id)
);

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);

```

```sql
CREATE TABLE public.password_reset_tokens (
    email varchar(255) NOT NULL,
    "token" varchar(255) NOT NULL,
    created_at timestamp(0) NULL,
    CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email)
);

```

```sql
CREATE TABLE public.permissions (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    guard_name varchar(255) NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name),
    CONSTRAINT permissions_pkey PRIMARY KEY (id)
);

```

```sql
CREATE TABLE public.provinces (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT provinces_name_unique UNIQUE (name),
    CONSTRAINT provinces_pkey PRIMARY KEY (id)
);

```

```sql
CREATE TABLE public.roles (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    guard_name varchar(255) NOT NULL,
    code varchar(50) NULL,
    hierarchy_level int4 DEFAULT 0 NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name),
    CONSTRAINT roles_pkey PRIMARY KEY (id)
);

```

```sql
CREATE TABLE public.schedules (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    start_time time(0) NOT NULL,
    end_time time(0) NOT NULL,
    lunch_minutes int4 DEFAULT 45 NOT NULL,
    break_minutes int4 DEFAULT 15 NOT NULL,
    total_minutes int4 DEFAULT 480 NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT schedules_name_unique UNIQUE (name),
    CONSTRAINT schedules_pkey PRIMARY KEY (id)
);

```

```sql
CREATE TABLE public.sessions (
    id varchar(255) NOT NULL,
    user_id int8 NULL,
    ip_address varchar(45) NULL,
    user_agent text NULL,
    payload text NOT NULL,
    last_activity int4 NOT NULL,
    CONSTRAINT sessions_pkey PRIMARY KEY (id)
);

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);

```

```sql
CREATE TABLE public.teams (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    description text NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT teams_name_unique UNIQUE (name),
    CONSTRAINT teams_pkey PRIMARY KEY (id)
);

```

```sql
CREATE TABLE public.users (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    email_verified_at timestamp(0) NULL,
    last_login_at timestamp(0) NULL,
    "password" varchar(255) NOT NULL,
    remember_token varchar(100) NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    deleted_at timestamp(0) NULL,
    force_password_change bool DEFAULT false NOT NULL,
    CONSTRAINT users_email_unique UNIQUE (email),
    CONSTRAINT users_pkey PRIMARY KEY (id)
);

```

```sql
CREATE TABLE public.audit_logs (
    id bigserial NOT NULL,
    user_id int8 NULL,
    entity_type varchar(100) NOT NULL,
    entity_id int8 NOT NULL,
    "action" varchar(50) NOT NULL,
    "before" jsonb NULL,
    "after" jsonb NULL,
    ip_address inet NULL,
    created_at timestamp(0) NULL,
    CONSTRAINT audit_logs_pkey PRIMARY KEY (id),
    CONSTRAINT audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE
    SET
        NULL
);

CREATE INDEX audit_logs_created_at_index ON public.audit_logs USING btree (created_at);

CREATE INDEX audit_logs_entity_type_entity_id_index ON public.audit_logs USING btree (entity_type, entity_id);

CREATE INDEX audit_logs_user_id_index ON public.audit_logs USING btree (user_id);

```

```sql
CREATE TABLE public.break_templates (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    team_id int8 NOT NULL,
    lunch_start time(0) NOT NULL,
    lunch_end time(0) NOT NULL,
    break_start time(0) NOT NULL,
    break_end time(0) NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT break_templates_pkey PRIMARY KEY (id),
    CONSTRAINT break_templates_team_id_name_unique UNIQUE (team_id, name),
    CONSTRAINT break_templates_team_id_foreign FOREIGN KEY (team_id) REFERENCES public.teams(id) ON DELETE CASCADE
);

```

```sql
CREATE TABLE public.departments (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    description text NULL,
    directorate_id int8 NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT departments_name_directorate_id_unique UNIQUE (name, directorate_id),
    CONSTRAINT departments_pkey PRIMARY KEY (id),
    CONSTRAINT departments_directorate_id_foreign FOREIGN KEY (directorate_id) REFERENCES public.directorates(id) ON DELETE CASCADE
);

```

```sql
CREATE TABLE public.districts (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    province_id int8 NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT districts_name_province_id_unique UNIQUE (name, province_id),
    CONSTRAINT districts_pkey PRIMARY KEY (id),
    CONSTRAINT districts_province_id_foreign FOREIGN KEY (province_id) REFERENCES public.provinces(id) ON DELETE CASCADE
);

```

```sql
CREATE TABLE public.model_has_permissions (
    permission_id int8 NOT NULL,
    model_type varchar(255) NOT NULL,
    model_id int8 NOT NULL,
    CONSTRAINT model_has_permissions_pkey PRIMARY KEY (permission_id, model_id, model_type),
    CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE
);

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);

```

```sql
CREATE TABLE public.model_has_roles (
    role_id int8 NOT NULL,
    model_type varchar(255) NOT NULL,
    model_id int8 NOT NULL,
    CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type),
    CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE
);

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);

```

```sql
CREATE TABLE public.positions (
    id bigserial NOT NULL,
    title varchar(255) NOT NULL,
    description text NULL,
    position_code varchar(255) NOT NULL,
    department_id int8 NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT positions_pkey PRIMARY KEY (id),
    CONSTRAINT positions_position_code_unique UNIQUE (position_code),
    CONSTRAINT positions_department_id_foreign FOREIGN KEY (department_id) REFERENCES public.departments(id) ON DELETE CASCADE
);

```

```sql
CREATE TABLE public.role_has_permissions (
    permission_id int8 NOT NULL,
    role_id int8 NOT NULL,
    CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id),
    CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE,
    CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE
);

```

```sql
CREATE TABLE public.townships (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    district_id int8 NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT townships_name_district_id_unique UNIQUE (name, district_id),
    CONSTRAINT townships_pkey PRIMARY KEY (id),
    CONSTRAINT townships_district_id_foreign FOREIGN KEY (district_id) REFERENCES public.districts(id) ON DELETE CASCADE
);

```

```sql
CREATE TABLE public.weekly_schedules (
    id bigserial NOT NULL,
    week_start_date date NOT NULL,
    week_end_date date NOT NULL,
    status varchar(255) DEFAULT 'draft' :: character varying NOT NULL,
    published_at timestamp(0) NULL,
    published_by int8 NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT weekly_schedules_pkey PRIMARY KEY (id),
    CONSTRAINT weekly_schedules_status_check CHECK (
        (
            (status) :: text = ANY (
                (
                    ARRAY ['draft'::character varying, 'published'::character varying]
                ) :: text []
            )
        )
    ),
    CONSTRAINT weekly_schedules_week_start_date_unique UNIQUE (week_start_date),
    CONSTRAINT weekly_schedules_published_by_foreign FOREIGN KEY (published_by) REFERENCES public.users(id) ON DELETE
    SET
        NULL
);

```

```sql
CREATE TABLE public.intraday_activities (
    id bigserial NOT NULL,
    weekly_schedule_id int8 NOT NULL,
    "name" varchar(255) NOT NULL,
    activity_date date NOT NULL,
    start_time time(0) NOT NULL,
    end_time time(0) NOT NULL,
    max_participants int4 NULL,
    notes text NULL,
    created_by int8 NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT intraday_activities_pkey PRIMARY KEY (id),
    CONSTRAINT intraday_activities_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE RESTRICT,
    CONSTRAINT intraday_activities_weekly_schedule_id_foreign FOREIGN KEY (weekly_schedule_id) REFERENCES public.weekly_schedules(id) ON DELETE CASCADE
);

CREATE INDEX intraday_activities_activity_date_index ON public.intraday_activities USING btree (activity_date);

CREATE INDEX intraday_activities_weekly_schedule_id_index ON public.intraday_activities USING btree (weekly_schedule_id);

```

```sql
CREATE TABLE public.employees (
    id bigserial NOT NULL,
    employee_number varchar(255) NULL,
    user_id int8 NOT NULL,
    username varchar(255) NOT NULL,
    first_name varchar(255) NOT NULL,
    last_name varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    birth_date date NOT NULL,
    gender varchar(255) NULL,
    blood_type varchar(255) NULL,
    phone varchar(255) NULL,
    mobile_phone varchar(255) NULL,
    address text NULL,
    township_id int8 NOT NULL,
    department_id int8 NULL,
    parent_id int8 NULL,
    position_id int8 NOT NULL,
    employment_status_id int8 NOT NULL,
    hire_date date NOT NULL,
    salary numeric(10, 2) NULL,
    is_active bool DEFAULT true NOT NULL,
    is_manager bool DEFAULT false NOT NULL,
    metadata json NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT employees_email_unique UNIQUE (email),
    CONSTRAINT employees_employee_number_unique UNIQUE (employee_number),
    CONSTRAINT employees_gender_check CHECK (
        (
            (gender) :: text = ANY (
                (
                    ARRAY ['male'::character varying, 'female'::character varying, 'other'::character varying]
                ) :: text []
            )
        )
    ),
    CONSTRAINT employees_parent_not_self CHECK (
        (
            (parent_id IS NULL)
            OR (parent_id <> id)
        )
    ),
    CONSTRAINT employees_pkey PRIMARY KEY (id),
    CONSTRAINT employees_username_unique UNIQUE (username),
    CONSTRAINT employees_department_id_foreign FOREIGN KEY (department_id) REFERENCES public.departments(id) ON DELETE
    SET
        NULL,
        CONSTRAINT employees_employment_status_id_foreign FOREIGN KEY (employment_status_id) REFERENCES public.employment_statuses(id) ON DELETE RESTRICT,
        CONSTRAINT employees_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.employees(id) ON DELETE
    SET
        NULL,
        CONSTRAINT employees_position_id_foreign FOREIGN KEY (position_id) REFERENCES public.positions(id) ON DELETE RESTRICT,
        CONSTRAINT employees_township_id_foreign FOREIGN KEY (township_id) REFERENCES public.townships(id) ON DELETE RESTRICT,
        CONSTRAINT employees_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE
);

CREATE INDEX employees_parent_id_index ON public.employees USING btree (parent_id);

```

```sql
CREATE TABLE public.intraday_activity_assignments (
    id bigserial NOT NULL,
    intraday_activity_id int8 NOT NULL,
    employee_id int8 NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT intraday_activity_assignments_intraday_activity_id_employee_id_ UNIQUE (intraday_activity_id, employee_id),
    CONSTRAINT intraday_activity_assignments_pkey PRIMARY KEY (id),
    CONSTRAINT intraday_activity_assignments_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE RESTRICT,
    CONSTRAINT intraday_activity_assignments_intraday_activity_id_foreign FOREIGN KEY (intraday_activity_id) REFERENCES public.intraday_activities(id) ON DELETE CASCADE
);

```

```sql
CREATE TABLE public.leave_requests (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    incident_type_id int8 NOT NULL,
    "type" varchar(255) DEFAULT 'full' :: character varying NOT NULL,
    start_datetime timestamp(0) NOT NULL,
    end_datetime timestamp(0) NOT NULL,
    justification text NULL,
    status varchar(255) DEFAULT 'pending' :: character varying NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT leave_requests_pkey PRIMARY KEY (id),
    CONSTRAINT leave_requests_status_check CHECK (
        (
            (status) :: text = ANY (
                (
                    ARRAY ['pending'::character varying, 'approved'::character varying, 'rejected'::character varying, 'cancelled'::character varying]
                ) :: text []
            )
        )
    ),
    CONSTRAINT leave_requests_type_check CHECK (
        (
            (type) :: text = ANY (
                (
                    ARRAY ['partial'::character varying, 'full'::character varying]
                ) :: text []
            )
        )
    ),
    CONSTRAINT leave_requests_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE RESTRICT,
    CONSTRAINT leave_requests_incident_type_id_foreign FOREIGN KEY (incident_type_id) REFERENCES public.incident_types(id) ON DELETE RESTRICT
);

CREATE INDEX leave_requests_employee_id_index ON public.leave_requests USING btree (employee_id);

CREATE INDEX leave_requests_status_index ON public.leave_requests USING btree (status);

```

```sql
CREATE TABLE public.team_members (
    id bigserial NOT NULL,
    team_id int8 NOT NULL,
    employee_id int8 NOT NULL,
    start_date date NOT NULL,
    end_date date NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT team_members_dates_valid CHECK (
        (
            (end_date IS NULL)
            OR (end_date >= start_date)
        )
    ),
    CONSTRAINT team_members_pkey PRIMARY KEY (id),
    CONSTRAINT team_members_team_id_employee_id_start_date_unique UNIQUE (team_id, employee_id, start_date),
    CONSTRAINT team_members_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE,
    CONSTRAINT team_members_team_id_foreign FOREIGN KEY (team_id) REFERENCES public.teams(id) ON DELETE CASCADE
);

CREATE INDEX team_members_employee_id_index ON public.team_members USING btree (employee_id);

CREATE INDEX team_members_team_id_index ON public.team_members USING btree (team_id);

```

```sql
CREATE TABLE public.weekly_schedule_assignments (
    id bigserial NOT NULL,
    weekly_schedule_id int8 NOT NULL,
    employee_id int8 NOT NULL,
    schedule_id int8 NOT NULL,
    break_template_id int8 NULL,
    is_custom_break bool DEFAULT false NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT weekly_schedule_assignments_pkey PRIMARY KEY (id),
    CONSTRAINT weekly_schedule_assignments_weekly_schedule_id_employee_id_uniq UNIQUE (weekly_schedule_id, employee_id),
    CONSTRAINT weekly_schedule_assignments_break_template_id_foreign FOREIGN KEY (break_template_id) REFERENCES public.break_templates(id) ON DELETE
    SET
        NULL,
        CONSTRAINT weekly_schedule_assignments_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE RESTRICT,
        CONSTRAINT weekly_schedule_assignments_schedule_id_foreign FOREIGN KEY (schedule_id) REFERENCES public.schedules(id) ON DELETE RESTRICT,
        CONSTRAINT weekly_schedule_assignments_weekly_schedule_id_foreign FOREIGN KEY (weekly_schedule_id) REFERENCES public.weekly_schedules(id) ON DELETE CASCADE
);

CREATE INDEX weekly_schedule_assignments_employee_id_index ON public.weekly_schedule_assignments USING btree (employee_id);

CREATE INDEX weekly_schedule_assignments_weekly_schedule_id_index ON public.weekly_schedule_assignments USING btree (weekly_schedule_id);

```

```sql
CREATE TABLE public.attendance_incidents (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    incident_type_id int8 NOT NULL,
    incident_date date NOT NULL,
    start_time time(0) NULL,
    end_time time(0) NULL,
    justification text NULL,
    recorded_by int8 NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT attendance_incidents_pkey PRIMARY KEY (id),
    CONSTRAINT attendance_incidents_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE RESTRICT,
    CONSTRAINT attendance_incidents_incident_type_id_foreign FOREIGN KEY (incident_type_id) REFERENCES public.incident_types(id) ON DELETE RESTRICT,
    CONSTRAINT attendance_incidents_recorded_by_foreign FOREIGN KEY (recorded_by) REFERENCES public.employees(id) ON DELETE RESTRICT
);

CREATE INDEX attendance_incidents_employee_id_incident_date_index ON public.attendance_incidents USING btree (employee_id, incident_date);

```

```sql
CREATE TABLE public.employee_break_overrides (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    lunch_start time(0) NOT NULL,
    lunch_end time(0) NOT NULL,
    break_start time(0) NOT NULL,
    break_end time(0) NOT NULL,
    reason text NOT NULL,
    effective_from date NOT NULL,
    effective_to date NULL,
    created_by int8 NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT employee_break_overrides_pkey PRIMARY KEY (id),
    CONSTRAINT employee_break_overrides_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE RESTRICT,
    CONSTRAINT employee_break_overrides_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE
);

CREATE INDEX employee_break_overrides_employee_id_index ON public.employee_break_overrides USING btree (employee_id);

```

```sql
CREATE TABLE public.employee_dependents (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    first_name varchar(255) NOT NULL,
    last_name varchar(255) NOT NULL,
    relationship varchar(255) NOT NULL,
    birth_date date NOT NULL,
    is_dependent bool DEFAULT true NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT employee_dependents_pkey PRIMARY KEY (id),
    CONSTRAINT employee_dependents_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE
);

```

```sql
CREATE TABLE public.employee_disabilities (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    disability_type_id int8 NOT NULL,
    description text NULL,
    diagnosis_date date NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT employee_disabilities_employee_id_disability_type_id_unique UNIQUE (employee_id, disability_type_id),
    CONSTRAINT employee_disabilities_pkey PRIMARY KEY (id),
    CONSTRAINT employee_disabilities_disability_type_id_foreign FOREIGN KEY (disability_type_id) REFERENCES public.disability_types(id) ON DELETE CASCADE,
    CONSTRAINT employee_disabilities_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE
);

```

```sql
CREATE TABLE public.employee_diseases (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    disease_type_id int8 NOT NULL,
    description text NULL,
    diagnosis_date date NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT employee_diseases_employee_id_disease_type_id_unique UNIQUE (employee_id, disease_type_id),
    CONSTRAINT employee_diseases_pkey PRIMARY KEY (id),
    CONSTRAINT employee_diseases_disease_type_id_foreign FOREIGN KEY (disease_type_id) REFERENCES public.disease_types(id) ON DELETE CASCADE,
    CONSTRAINT employee_diseases_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE
);

```

```sql
CREATE TABLE public.employee_positions (
    id bigserial NOT NULL,
    employee_id int8 NOT NULL,
    position_id int8 NOT NULL,
    start_date date NOT NULL,
    end_date date NULL,
    is_primary bool DEFAULT false NOT NULL,
    fte_percentage numeric(5, 2) DEFAULT '100' :: numeric NOT NULL,
    is_active bool DEFAULT true NOT NULL,
    notes text NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT employee_positions_dates_valid CHECK (
        (
            (end_date IS NULL)
            OR (end_date >= start_date)
        )
    ),
    CONSTRAINT employee_positions_employee_id_position_id_start_date_unique UNIQUE (employee_id, position_id, start_date),
    CONSTRAINT employee_positions_pkey PRIMARY KEY (id),
    CONSTRAINT employee_positions_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE,
    CONSTRAINT employee_positions_position_id_foreign FOREIGN KEY (position_id) REFERENCES public.positions(id) ON DELETE CASCADE
);

CREATE INDEX employee_positions_employee_id_is_primary_index ON public.employee_positions USING btree (employee_id, is_primary);

CREATE INDEX employee_positions_position_id_index ON public.employee_positions USING btree (position_id);

CREATE UNIQUE INDEX employee_positions_unique_primary_active_idx ON public.employee_positions USING btree (employee_id)
WHERE
    (
        (is_primary = true)
        AND (is_active = true)
        AND (end_date IS NULL)
    );

```

```sql
CREATE TABLE public.leave_request_approvals (
    id bigserial NOT NULL,
    leave_request_id int8 NOT NULL,
    approver_id int8 NOT NULL,
    step int4 NOT NULL,
    "action" varchar(255) NOT NULL,
    "comments" text NULL,
    acted_at timestamp(0) NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT leave_request_approvals_action_check CHECK (
        (
            (action) :: text = ANY (
                (
                    ARRAY ['approved'::character varying, 'rejected'::character varying]
                ) :: text []
            )
        )
    ),
    CONSTRAINT leave_request_approvals_pkey PRIMARY KEY (id),
    CONSTRAINT leave_request_approvals_approver_id_foreign FOREIGN KEY (approver_id) REFERENCES public.employees(id) ON DELETE RESTRICT,
    CONSTRAINT leave_request_approvals_leave_request_id_foreign FOREIGN KEY (leave_request_id) REFERENCES public.leave_requests(id) ON DELETE CASCADE
);

CREATE INDEX leave_request_approvals_leave_request_id_index ON public.leave_request_approvals USING btree (leave_request_id);

```

```sql
CREATE TABLE public.shift_swap_requests (
    id bigserial NOT NULL,
    requester_id int8 NOT NULL,
    target_id int8 NOT NULL,
    weekly_schedule_id int8 NOT NULL,
    swap_date date NOT NULL,
    requester_assignment_id int8 NOT NULL,
    target_assignment_id int8 NOT NULL,
    status varchar(255) DEFAULT 'pending' :: character varying NOT NULL,
    target_response_at timestamp(0) NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT shift_swap_requests_pkey PRIMARY KEY (id),
    CONSTRAINT shift_swap_requests_status_check CHECK (
        (
            (status) :: text = ANY (
                (
                    ARRAY ['pending'::character varying, 'accepted'::character varying, 'rejected'::character varying, 'approved'::character varying, 'cancelled'::character varying]
                ) :: text []
            )
        )
    ),
    CONSTRAINT shift_swap_requests_requester_assignment_id_foreign FOREIGN KEY (requester_assignment_id) REFERENCES public.weekly_schedule_assignments(id) ON DELETE RESTRICT,
    CONSTRAINT shift_swap_requests_requester_id_foreign FOREIGN KEY (requester_id) REFERENCES public.employees(id) ON DELETE RESTRICT,
    CONSTRAINT shift_swap_requests_target_assignment_id_foreign FOREIGN KEY (target_assignment_id) REFERENCES public.weekly_schedule_assignments(id) ON DELETE RESTRICT,
    CONSTRAINT shift_swap_requests_target_id_foreign FOREIGN KEY (target_id) REFERENCES public.employees(id) ON DELETE RESTRICT,
    CONSTRAINT shift_swap_requests_weekly_schedule_id_foreign FOREIGN KEY (weekly_schedule_id) REFERENCES public.weekly_schedules(id) ON DELETE RESTRICT
);

CREATE INDEX shift_swap_requests_requester_id_index ON public.shift_swap_requests USING btree (requester_id);

CREATE INDEX shift_swap_requests_status_index ON public.shift_swap_requests USING btree (status);

CREATE INDEX shift_swap_requests_swap_date_index ON public.shift_swap_requests USING btree (swap_date);

CREATE INDEX shift_swap_requests_target_id_index ON public.shift_swap_requests USING btree (target_id);

```

```sql
CREATE TABLE public.shift_swap_approvals (
    id bigserial NOT NULL,
    shift_swap_request_id int8 NOT NULL,
    approver_id int8 NOT NULL,
    step int4 NOT NULL,
    "action" varchar(255) NOT NULL,
    "comments" text NULL,
    acted_at timestamp(0) NOT NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT shift_swap_approvals_action_check CHECK (
        (
            (action) :: text = ANY (
                (
                    ARRAY ['approved'::character varying, 'rejected'::character varying]
                ) :: text []
            )
        )
    ),
    CONSTRAINT shift_swap_approvals_pkey PRIMARY KEY (id),
    CONSTRAINT shift_swap_approvals_approver_id_foreign FOREIGN KEY (approver_id) REFERENCES public.employees(id) ON DELETE RESTRICT,
    CONSTRAINT shift_swap_approvals_shift_swap_request_id_foreign FOREIGN KEY (shift_swap_request_id) REFERENCES public.shift_swap_requests(id) ON DELETE CASCADE
);

CREATE INDEX shift_swap_approvals_shift_swap_request_id_index ON public.shift_swap_approvals USING btree (shift_swap_request_id);

-- Migración recomendada (sin ruptura) desde jerarquía fija a adjacency list
-- 1) Agregar parent_id y su FK
-- 2) Poblar parent_id usando columnas legadas (si existen)
-- 3) Validar jerarquía
-- 4) Eliminar columnas legadas
-- Paso 1
ALTER TABLE
    public.employees
ADD
    COLUMN IF NOT EXISTS parent_id int8 NULL;

DO $ $ BEGIN IF NOT EXISTS (
    SELECT
        1
    FROM
        pg_constraint
    WHERE
        conname = 'employees_parent_id_foreign'
) THEN
ALTER TABLE
    public.employees
ADD
    CONSTRAINT employees_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.employees(id) ON DELETE
SET
    NULL;

END IF;

END $ $;

CREATE INDEX IF NOT EXISTS employees_parent_id_index ON public.employees USING btree (parent_id);

-- Paso 2 (compatibilidad con esquemas que tengan una o varias columnas legadas)
DO $ $ BEGIN IF EXISTS (
    SELECT
        1
    FROM
        information_schema.columns
    WHERE
        table_schema = 'public'
        AND table_name = 'employees'
        AND column_name = 'supervisor_id'
) THEN EXECUTE 'UPDATE public.employees SET parent_id = supervisor_id WHERE parent_id IS NULL';

END IF;

IF EXISTS (
    SELECT
        1
    FROM
        information_schema.columns
    WHERE
        table_schema = 'public'
        AND table_name = 'employees'
        AND column_name = 'coordinator_id'
) THEN EXECUTE 'UPDATE public.employees SET parent_id = coordinator_id WHERE parent_id IS NULL';

END IF;

IF EXISTS (
    SELECT
        1
    FROM
        information_schema.columns
    WHERE
        table_schema = 'public'
        AND table_name = 'employees'
        AND column_name = 'manager_id'
) THEN EXECUTE 'UPDATE public.employees SET parent_id = manager_id WHERE parent_id IS NULL';

END IF;

END $ $;

-- Paso 3 (validación básica anti auto-referencia)
ALTER TABLE
    public.employees DROP CONSTRAINT IF EXISTS employees_manager_not_self,
    DROP CONSTRAINT IF EXISTS employees_parent_not_self;

ALTER TABLE
    public.employees
ADD
    CONSTRAINT employees_parent_not_self CHECK (
        parent_id IS NULL
        OR parent_id <> id
    );

-- Paso 4 (solo cuando toda la aplicación ya usa parent_id)
-- ALTER TABLE public.employees DROP COLUMN IF EXISTS supervisor_id;
-- ALTER TABLE public.employees DROP COLUMN IF EXISTS coordinator_id;
-- ALTER TABLE public.employees DROP COLUMN IF EXISTS manager_id;

```
