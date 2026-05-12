# 🧱 PROPUESTA REESCRITA — MÓDULO DE HORARIOS

## 0. Principios (no negociables)

- **Fuente de verdad**:  
    `weekly_schedule_assignments` (plan base)    
- **Nada se sobreescribe**:  
    todo cambio es un _override_
- **Plan ≠ Realidad**
    - Plan → WFM
    - Realidad → incidencias
- **Vista efectiva se calcula**, no se persiste

---

# 1. CAPAS DEL SISTEMA

## 1.1 Planificación (BASE)

## 1. `schedules` (turnos base)

```sql
CREATE TABLE public.schedules (
    id BIGSERIAL PRIMARY KEY,

    name VARCHAR(255) NOT NULL,
    
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,

    lunch_minutes INT NOT NULL DEFAULT 45,
    break_minutes INT NOT NULL DEFAULT 15,

    total_minutes INT NOT NULL,

    is_active BOOLEAN NOT NULL DEFAULT true,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    CONSTRAINT schedules_name_unique UNIQUE (name),

    -- sanity checks
    CONSTRAINT schedules_time_valid CHECK (start_time <> end_time),
    CONSTRAINT schedules_minutes_positive CHECK (total_minutes > 0)
);
```

### 🔴 Notas críticas

- NO validas overnight (`22:00 → 06:00`) → lo aceptas implícitamente
    
- `total_minutes` debe calcularse en app (no trigger → menos complejidad)
    

---

## 2. `weekly_schedules` (contenedor semanal)

```sql
CREATE TABLE public.weekly_schedules (
    id BIGSERIAL PRIMARY KEY,

    week_start_date DATE NOT NULL,
    week_end_date DATE NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'draft',

    published_at TIMESTAMP NULL,
    published_by BIGINT NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    CONSTRAINT weekly_schedules_week_unique UNIQUE (week_start_date),

    CONSTRAINT weekly_schedules_status_check 
        CHECK (status IN ('draft', 'published')),

    CONSTRAINT weekly_schedules_dates_valid 
        CHECK (week_end_date >= week_start_date),

    CONSTRAINT weekly_schedules_published_by_fk
        FOREIGN KEY (published_by)
        REFERENCES public.users(id)
        ON DELETE SET NULL
);
```

### 🔴 Notas críticas

- No soporta versionado → si editas publicado, rompes historia
    
- `week_start_date` único → evita duplicados (bien)
    

---

## 3. `break_templates` (plantillas de descanso)

```sql
CREATE TABLE public.break_templates (
    id BIGSERIAL PRIMARY KEY,

    team_id BIGINT NOT NULL,

    name VARCHAR(255) NOT NULL,

    lunch_start TIME NOT NULL,
    lunch_end TIME NOT NULL,

    break_start TIME NOT NULL,
    break_end TIME NOT NULL,

    is_active BOOLEAN NOT NULL DEFAULT true,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    CONSTRAINT break_templates_team_name_unique 
        UNIQUE (team_id, name),

    CONSTRAINT break_templates_team_fk
        FOREIGN KEY (team_id)
        REFERENCES public.teams(id)
        ON DELETE CASCADE,

    -- sanity checks
    CONSTRAINT break_templates_lunch_valid 
        CHECK (lunch_end > lunch_start),

    CONSTRAINT break_templates_break_valid 
        CHECK (break_end > break_start)
);
```

### 🔴 Problema no resuelto

- No validas que el break esté dentro del turno → eso es lógica de app
    

---

## 4. `weekly_schedule_assignments` (asignación de turnos)

```sql
CREATE TABLE public.weekly_schedule_assignments (
    id BIGSERIAL PRIMARY KEY,

    weekly_schedule_id BIGINT NOT NULL,
    employee_id BIGINT NOT NULL,
    schedule_id BIGINT NOT NULL,

    break_template_id BIGINT NULL,

    is_custom_break BOOLEAN NOT NULL DEFAULT false,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    -- unicidad real
    CONSTRAINT weekly_schedule_employee_unique 
        UNIQUE (weekly_schedule_id, employee_id),

    -- FKs
    CONSTRAINT wsa_weekly_schedule_fk
        FOREIGN KEY (weekly_schedule_id)
        REFERENCES public.weekly_schedules(id)
        ON DELETE CASCADE,

    CONSTRAINT wsa_employee_fk
        FOREIGN KEY (employee_id)
        REFERENCES public.employees(id)
        ON DELETE RESTRICT,

    CONSTRAINT wsa_schedule_fk
        FOREIGN KEY (schedule_id)
        REFERENCES public.schedules(id)
        ON DELETE RESTRICT,

    CONSTRAINT wsa_break_template_fk
        FOREIGN KEY (break_template_id)
        REFERENCES public.break_templates(id)
        ON DELETE SET NULL
);
```

---

# ⚠️ Índices mínimos (no opcionales)

```sql
CREATE INDEX idx_wsa_employee 
ON public.weekly_schedule_assignments(employee_id);

CREATE INDEX idx_wsa_week 
ON public.weekly_schedule_assignments(weekly_schedule_id);

CREATE INDEX idx_weekly_schedules_dates 
ON public.weekly_schedules(week_start_date, week_end_date);
```


## 🔥 NUEVO: versionado de planificación

```sql
CREATE TABLE schedule_versions (
    id BIGSERIAL PRIMARY KEY,
    weekly_schedule_id BIGINT NOT NULL,
    version INT NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP,
    
    UNIQUE (weekly_schedule_id, version)
);
```

👉 Problema que resuelve:

- edición después de publicado
    
- auditoría real
    

---

# 1.2 Overrides (CAMBIOS AL PLAN)

## 1.2.1 Permisos (ya tienes)

```sql
leave_requests
leave_request_approvals
```

---

## 🔥 NUEVO: normalizar “aplicación efectiva”

```sql
CREATE TABLE schedule_overrides (
    id BIGSERIAL PRIMARY KEY,
    employee_id BIGINT NOT NULL,
    override_type VARCHAR(50) NOT NULL,
    reference_id BIGINT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    affects_availability BOOLEAN DEFAULT true,
    created_at TIMESTAMP
);
```

### Tipos:

- `leave`
- `swap`
- `intraday`
- `manual`
- `break_override`

👉 Esto elimina lógica duplicada.

---

## 1.2.2 Cambios de turno

```sql
shift_swap_requests
shift_swap_approvals
```

---

## 🔥 NUEVO: resultado aplicado

```sql
CREATE TABLE applied_shift_swaps (
    id BIGSERIAL PRIMARY KEY,
    shift_swap_request_id BIGINT NOT NULL,
    employee_a_id BIGINT NOT NULL,
    employee_b_id BIGINT NOT NULL,
    swap_date DATE NOT NULL,
    created_at TIMESTAMP
);
```

👉 Sin esto no sabes qué realmente cambió.

---

## 1.2.3 Intradía (ya tienes)

```sql
intraday_activities
intraday_activity_assignments
```

---

## 🔥 NUEVO: control de conflictos intradía

```sql
CREATE TABLE intraday_conflicts (
    id BIGSERIAL PRIMARY KEY,
    employee_id BIGINT,
    activity_id BIGINT,
    conflict_type VARCHAR(50),
    detected_at TIMESTAMP
);
```

---

## 1.2.4 Overrides manuales

```sql
employee_break_overrides
```

---

# 1.3 EJECUCIÓN (REALIDAD)

```sql
attendance_incidents
incident_types
```

---

## 🔥 NUEVO: snapshot de asistencia (opcional pero útil)

```sql
CREATE TABLE attendance_daily_snapshots (
    id BIGSERIAL PRIMARY KEY,
    employee_id BIGINT NOT NULL,
    date DATE NOT NULL,
    planned_minutes INT,
    worked_minutes INT,
    incident_minutes INT,
    created_at TIMESTAMP,

    UNIQUE (employee_id, date)
);
```

👉 evita recalcular reportes pesados.

---

# 1.4 COBERTURA (LO QUE TE FALTA)

## 🔥 CRÍTICO: demanda operativa

```sql
CREATE TABLE coverage_requirements (
    id BIGSERIAL PRIMARY KEY,
    team_id BIGINT NOT NULL,
    date DATE NOT NULL,
    hour TIME NOT NULL,
    required_agents INT NOT NULL
);
```

---

## 🔥 cobertura real calculada

```sql
CREATE TABLE coverage_snapshots (
    id BIGSERIAL PRIMARY KEY,
    team_id BIGINT,
    date DATE,
    hour TIME,
    planned_agents INT,
    available_agents INT,
    deficit INT,
    created_at TIMESTAMP
);
```

👉 Esto convierte tu sistema en WFM real.

---

# 1.5 CONTROL DE CONCURRENCIA

## 🔥 locks operativos

```sql
CREATE TABLE schedule_locks (
    id BIGSERIAL PRIMARY KEY,
    employee_id BIGINT NOT NULL,
    lock_date DATE NOT NULL,
    reason VARCHAR(100),
    expires_at TIMESTAMP
);
```

👉 evita:

- doble aprobación
- swaps inconsistentes

---

# 1.6 VALIDACIONES Y ERRORES

## 🔥 registro de violaciones

```sql
CREATE TABLE schedule_violations (
    id BIGSERIAL PRIMARY KEY,
    employee_id BIGINT,
    date DATE,
    violation_type VARCHAR(50),
    severity VARCHAR(20),
    details JSONB,
    created_at TIMESTAMP
);
```

Tipos:

- overlap
- coverage_gap
- invalid_swap
- intraday_conflict

---

# 2. VISTA CLAVE (NO TABLA)

## 🔥 CORE DEL SISTEMA

```sql
employee_effective_schedule_view
```

Debe resolver:

```sql
base_schedule
+ overrides
- permisos
+ intraday
- incidencias
```

👉 TODA la UI depende de esto.

---

# 3. FLUJO REDEFINIDO

## 3.1 Planificación

1. Crear `weekly_schedule`
2. Crear `schedule_version`
3. Asignar turnos
4. Aplicar descansos
5. Validar:
    - solapes
    - cobertura
6. Publicar

---

## 3.2 Operación

1. Usuario crea solicitud
2. Se valida contra:
    - base
    - overrides
3. Se aprueba
4. Se inserta en:
    - `schedule_overrides`
5. Se recalcula vista efectiva

---

## 3.3 Ejecución

1. Coordinador registra incidencia
2. Se afecta disponibilidad
3. Se refleja en cobertura

---

# 4. REGLAS DURAS (NO OPCIONALES)

## Unicidad real

```sql
(employee_id, date)
```

👉 un solo estado efectivo por día

---

## No solapamientos
- permisos vs intradía
- intradía vs intradía
- swaps vs permisos

---

## Orden de prioridad

1. Incidencia real
2. Permiso aprobado
3. Swap aplicado
4. Intradía
5. Turno base

---

# 5. TRADE-OFFS

## ✔ Ganancias
- consistencia
- trazabilidad real
- escalabilidad funcional

## ❌ Costos
- más joins
- queries complejas
- necesidad de índices serios

---

# 6. ERRORES QUE EVITAS

- doble asignación silenciosa
- swaps inconsistentes
- permisos que no afectan cobertura
- reportes incorrectos
- “fantasmas” en planificación    

---
