
# 🧱 EXTENSIONES

```sql
CREATE EXTENSION IF NOT EXISTS btree_gist;
```

---

# 1. TURNOS BASE

```sql
CREATE TABLE schedules (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,

    start_time TIME NOT NULL,
    end_time TIME NOT NULL,

    total_minutes INT NOT NULL CHECK (total_minutes > 0),

    is_overnight BOOLEAN GENERATED ALWAYS AS (end_time < start_time) STORED,

    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

# 2. PLANIFICACIÓN SEMANAL

```sql
CREATE TABLE weekly_schedules (
    id BIGSERIAL PRIMARY KEY,

    week_start_date DATE NOT NULL UNIQUE,
    week_end_date DATE NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    published_at TIMESTAMP NULL,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    CHECK (week_end_date = week_start_date + INTERVAL '6 days'),
    CHECK (status IN ('draft', 'published'))
);
```

---

# 3. ASIGNACIÓN DIARIA (FIX CRÍTICO)

```sql
CREATE TABLE weekly_schedule_assignments (
    id BIGSERIAL PRIMARY KEY,

    weekly_schedule_id BIGINT NOT NULL REFERENCES weekly_schedules(id) ON DELETE CASCADE,
    employee_id BIGINT NOT NULL REFERENCES employees(id),
    schedule_id BIGINT NOT NULL REFERENCES schedules(id),

    day_of_week SMALLINT NOT NULL CHECK (day_of_week BETWEEN 1 AND 7),

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE (weekly_schedule_id, employee_id, day_of_week)
);
```

---

# 4. OVERRIDES UNIFICADOS (CORE REAL)

```sql
CREATE TABLE schedule_overrides (
    id BIGSERIAL PRIMARY KEY,

    employee_id BIGINT NOT NULL REFERENCES employees(id),

    override_type VARCHAR(50) NOT NULL,
    -- 'base', 'intraday', 'leave', 'swap', 'manual'

    priority SMALLINT NOT NULL,

    time_range TSTZRANGE NOT NULL,

    source_id BIGINT NULL,

    metadata JSONB,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    CHECK (override_type IN ('base', 'intraday', 'leave', 'swap', 'manual'))
);
```

---

# 🔢 PRIORIDADES (hardcoded en DB o app)

| Tipo     | Priority |
| -------- | -------- |
| base     | 1        |
| intraday | 10       |
| manual   | 50       |
| swap     | 80       |
| leave    | 100      |

✔ permite overlaps reales
✔ elimina lógica condicional en PHP

---

# 📌 ÍNDICES CRÍTICOS

```sql
CREATE INDEX overrides_employee_range_idx
ON schedule_overrides USING GIST (employee_id, time_range);

CREATE INDEX overrides_priority_idx
ON schedule_overrides (employee_id, priority);
```

---

# 5. PERMISOS (INPUT → NO FUENTE DE VERDAD)

```sql
CREATE TABLE leave_requests (
    id BIGSERIAL PRIMARY KEY,

    employee_id BIGINT NOT NULL REFERENCES employees(id),

    time_range TSTZRANGE NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'pending',

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    CHECK (status IN ('pending', 'approved', 'rejected'))
);
```

👉 al aprobar → insert en `schedule_overrides (priority=100)`

---

# 6. CAMBIOS DE TURNO

```sql
CREATE TABLE shift_swaps (
    id BIGSERIAL PRIMARY KEY,

    employee_id_from BIGINT NOT NULL REFERENCES employees(id),
    employee_id_to BIGINT NOT NULL REFERENCES employees(id),

    swap_date DATE NOT NULL,

    status VARCHAR(20) DEFAULT 'pending',

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    CHECK (status IN ('pending', 'approved', 'rejected'))
);
```

👉 al aprobar → generar overrides (priority=80)

---

# 7. EJECUCIÓN REAL

```sql
CREATE TABLE attendance_incidents (
    id BIGSERIAL PRIMARY KEY,

    employee_id BIGINT NOT NULL REFERENCES employees(id),

    incident_type_id BIGINT NOT NULL REFERENCES incident_types(id),

    time_range TSTZRANGE NOT NULL,

    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

# 8. AUDITORÍA (OBLIGATORIO)

```sql
CREATE TABLE schedule_audit_log (
    id BIGSERIAL PRIMARY KEY,

    table_name VARCHAR(50) NOT NULL,
    record_id BIGINT NOT NULL,

    action VARCHAR(10) NOT NULL,

    old_value JSONB,
    new_value JSONB,

    changed_by BIGINT REFERENCES users(id),

    changed_at TIMESTAMP DEFAULT NOW()
);
```

---

# 9. VISTA EFECTIVA (REESCRITA CORRECTAMENTE)

```sql
CREATE VIEW employee_effective_schedule AS
WITH base_expanded AS (
    SELECT
        a.employee_id,
        (ws.week_start_date + (a.day_of_week - 1))::date AS work_date,

        tstzrange(
            (ws.week_start_date + (a.day_of_week - 1))::timestamp + s.start_time,
            (ws.week_start_date + (a.day_of_week - 1))::timestamp + s.end_time
        ) AS time_range,

        1 AS priority,
        'base' AS source
    FROM weekly_schedule_assignments a
    JOIN weekly_schedules ws ON ws.id = a.weekly_schedule_id
    JOIN schedules s ON s.id = a.schedule_id
),
all_events AS (
    SELECT employee_id, time_range, priority, source
    FROM base_expanded

    UNION ALL

    SELECT employee_id, time_range, priority, override_type
    FROM schedule_overrides
)
SELECT DISTINCT ON (employee_id, lower(time_range))
    employee_id,
    time_range,
    source,
    priority
FROM all_events
ORDER BY employee_id, lower(time_range), priority DESC;
```

---

# ⚠️ TRADE-OFFS (explícitos)

## ❌ Pierdes constraint de no solape

✔ intencional

* realidad operativa sí tiene conflictos
* la resolución ocurre en la vista, no en escritura

---

## ❌ Más complejidad en queries

✔ correcto

* pero centralizada en una sola vista
* no en toda la app

---

## ❌ Más filas (7x por semana)

✔ irrelevante

* esto es OLTP, no analytics

---

# 💣 DÓNDE VA A FALLAR SI NO HACES ESTO

* tz bugs silenciosos → imposibles de debuggear
* overrides explotando → tablas inconsistentes
* planificación rígida → usuarios metiendo hacks
* joins intradía → performance colapsa

---

# 🚀 SIGUIENTE NIVEL

Si quieres dejarlo realmente sólido:

* `PARTITION BY RANGE (lower(time_range))` en overrides
* materialized view incremental para cobertura
* trigger para generar overrides desde inputs automáticamente

---
---




El flujo correcto es: *configurar → planificar → validar → publicar → mutar → consultar → registrar → analizar*. Si mezclas etapas, introduces inconsistencias.

---

# 🧭 FLUJO GLOBAL (orden real)

```text
[CATÁLOGOS]
    ↓
[PLANIFICACIÓN (draft)]
    ↓
[VALIDACIÓN]
    ↓
[PUBLISH]
    ↓
──────────────
[RUNTIME]
    ↓
[MUTACIONES → overrides]
    ↓
[LECTURA → vista efectiva]
    ↓
[EJECUCIÓN → incidencias]
    ↓
[ANALÍTICA]
```

---

# 1. ⚙️ CONFIGURACIÓN (setup inicial)

### Tablas

* `schedules`

### Proceso

* Crear/editar turnos base

### Reglas

* No modificar turnos en uso
* Validar coherencia (`start < end` o overnight)

### Fallos típicos

* editar turnos históricos → rompe reportes

---

# 2. 📅 PLANIFICACIÓN SEMANAL (WFM only)

### Tablas

* `weekly_schedules`
* `weekly_schedule_assignments`

### Flujo

1. Crear semana (`draft`)
2. Asignar turnos por empleado/día
3. Ajustes manuales

### Reglas

* 1 turno por empleado/día
* semana completa (no gaps)

### Fallos

* permitir edición después de publish

---

# 3. ✅ VALIDACIÓN (pre-publicación)

### Qué validar

* Cobertura mínima (opcional aquí, mejor en analítica)
* Turnos consecutivos inválidos
* Días sin asignación
* Sobrecarga de horas

### Dónde

* DB (constraints) + job de validación

### Fallo crítico

* validar en UI solamente → basura publicada

---

# 4. 🚀 PUBLICACIÓN

### Acción

* `weekly_schedules.status = 'published'`

### Efectos

* Bloquea edición
* Habilita visibilidad para operadores
* Congela planificación base

### Fallo

* permitir overrides antes de publicar → caos

---

# 5. 🔄 MUTACIONES (runtime real)

### Tablas

* `leave_requests`
* `shift_swaps`
* → `schedule_overrides`

---

## 5.1 Permisos

```text
request → approve → INSERT override (priority=100)
```

---

## 5.2 Cambios de turno

```text
request → accept → approve → INSERT 2 overrides (priority=80)
```

---

## 5.3 Intradía / reuniones

```text
crear evento → INSERT override (priority=10)
```

---

### Regla crítica

👉 TODO termina en:

```sql
schedule_overrides
```

---

### Fallos típicos

* lógica directa en `leave_requests` → inconsistencia
* no centralizar → duplicación

---

# 6. 👁️ CONSULTA (lectura única)

### Fuente

```sql
employee_effective_schedule
```

### Qué hace

* Resuelve:

  * turno base
  * overrides
* Aplica prioridad

### Regla

👉 NADIE consulta tablas base

---

### Fallo crítico

* joins manuales en Laravel → bugs + N+1

---

# 7. 📍 EJECUCIÓN (lo que realmente pasó)

### Tabla

* `attendance_incidents`

### Flujo

* registrar tardanza / ausencia
* no modificar planificación

### Regla

👉 separación estricta:

* planificación ≠ ejecución

---

### Fallo

* “corregir” horarios con incidencias → corrupción

---

# 8. 📊 ANALÍTICA (batch)

### Procesos

* cobertura
* snapshots diarios

### Input

* `employee_effective_schedule`
* `attendance_incidents`

### Output

* tablas agregadas

---

### Regla

* async (jobs)
* nunca en request HTTP

---

### Fallo

* calcular cobertura en vivo → latencia

---

# 🔒 9. CONTROL DE CONCURRENCIA

### Estrategia real

* `weekly_schedules.status`
* * transacciones DB

### Opcional

* locks por empleado/día

---

### Fallo

* doble aprobación de permisos
* race conditions en overrides

---

# 🔁 PIPELINE COMPLETO (determinístico)

```text
[CONFIG]
    ↓
[PLANIFICACIÓN]
    ↓
[VALIDACIÓN]
    ↓
[PUBLISH]
    ↓
──────────────
[RUNTIME INPUT]
    ↓
[TRANSFORMACIÓN → overrides]
    ↓
[RESOLUCIÓN → vista]
    ↓
[CONSUMO (UI/API)]
    ↓
[REGISTRO REAL]
    ↓
[ANALÍTICA]
```

---

# 💣 ERRORES QUE DEBES EVITAR

## ❌ Mezclar etapas

* permisos modificando assignments
* incidencias alterando planificación

---

## ❌ Múltiples fuentes de verdad

* intradía separado
* overrides parciales
* inputs usados como realidad

---

## ❌ lógica distribuida

* validaciones en controllers
* reglas en jobs
* hacks en queries

---







---
---

Un solo módulo `Schedules` con 6 submenús: *catálogo → planificación → mutaciones → operación → control → analítica*. Si metes más, estás duplicando lógica.

---

# 🧭 ESTRUCTURA DEL SIDEBAR

```text
Schedules
├── Shifts
├── Weekly Planning
├── Overrides
├── Requests
├── Operations
├── Analytics
```

---

# 1. ⚙️ Shifts (Catálogo)

### Función

Gestión de turnos base (`schedules`)

### Acciones

* Crear turno
* Editar turno (solo si no está en uso)
* Desactivar turno
* Listar turnos

### Restricciones

* ❌ No eliminar si está referenciado
* ❌ No modificar histórico

### Riesgo

* editar turno activo → inconsistencia en planificación

---

# 2. 📅 Weekly Planning

### Función

Construcción de planificación semanal (`weekly_schedules`, `assignments`)

---

## Sub-secciones internas

```text
Weekly Planning
├── Calendar (grid principal)
├── Assignments
├── Validation
```

---

## Acciones

### Calendar

* Crear semana (`draft`)
* Editar asignaciones (por empleado/día)
* Copiar semana anterior
* Bulk assign (por equipo)

---

### Assignments

* Ajuste fino por empleado
* Cambiar turno puntual (sin override)

---

### Validation

* Ejecutar validaciones
* Ver conflictos:

  * días sin turno
  * exceso de horas
  * conflictos de descanso

---

### Restricciones

* Solo WFM edita
* `published` = readonly

---

### Riesgos

* permitir edición post-publicación
* no validar antes de publicar

---

# 3. 🔄 Overrides

### Función

Gestión directa de `schedule_overrides` (runtime controlado)

---

## Acciones

* Crear override manual:

  * tipo (`manual`, `intraday`)
  * rango horario
  * prioridad
* Editar override
* Eliminar override

---

## Casos de uso

* reuniones
* capacitaciones
* bloqueos operativos

---

### Restricciones

* no permitir conflictos absurdos (ej: override fuera de rango laboral extremo sin validación)

---

### Riesgo

* usar esto como workaround para mala planificación → deuda técnica

---

# 4. 📝 Requests (Inputs de negocio)

### Función

Flujos que TERMINAN en overrides

---

## Submenús

```text
Requests
├── Leave Requests
├── Shift Swaps
```

---

## Leave Requests

### Acciones

* aprobar / rechazar
* ver historial

### Acción crítica

```text
approve → INSERT override (priority=100)
```

---

## Shift Swaps

### Acciones

* aprobar / rechazar
* ver estado

### Acción crítica

```text
approve → generar overrides (priority=80)
```

---

### Restricciones

* no modificar directamente horarios
* todo pasa por overrides

---

### Riesgo

* lógica duplicada entre requests y overrides

---

# 5. 📍 Operations (Ejecución real)

### Función

Registrar lo que realmente ocurrió

---

## Submenús

```text
Operations
├── Attendance
├── Incidents
```

---

## Attendance

* ver asistencia diaria
* ver historial

---

## Incidents

* registrar incidencia:

  * tardanza
  * ausencia
* editar (con permisos)

---

### Restricción

* ❌ no modificar planificación
* ❌ no crear overrides

---

### Riesgo

* usar incidencias para “corregir” horarios

---

# 6. 📊 Analytics

### Función

Explotación de datos (NO operación)

---

## Submenús

```text
Analytics
├── Coverage
├── Reports
├── Snapshots
```

---

## Coverage

* cobertura por hora/día
* comparación vs demanda

---

## Reports

* cumplimiento
* ausentismo
* productividad

---

## Snapshots

* estado histórico diario

---

### Restricción

* solo lectura
* async / precomputado

---

### Riesgo

* cálculos en vivo → performance muerto

---

# 🔒 VISIBILIDAD POR ROL

| Sección         | WFM | Coordinador | Supervisor | Operador  |
| --------------- | --- | ----------- | ---------- | --------- |
| Shifts          | ✅   | ❌           | ❌          | ❌         |
| Weekly Planning | ✅   | 👁️           | 👁️          | ❌         |
| Overrides       | ✅   | ⚠️ limitado  | ❌          | ❌         |
| Requests        | 👁️   | ✅           | ❌          | ✅ (crear) |
| Operations      | 👁️   | ✅           | 👁️          | 👁️         |
| Analytics       | ✅   | 👁️           | ❌          | ❌         |

---

# 💣 ERRORES DE DISEÑO A EVITAR

## ❌ Mezclar Requests con Overrides

→ rompe trazabilidad

---

## ❌ Meter intradía como módulo separado

→ ya lo corregiste → no lo reintroduzcas

---

## ❌ Dar acceso a planificación a coordinadores

→ termina en caos operativo

---

## ❌ Analytics dentro de Planning

→ acoplamiento innecesario

---

# 🚀 RESULTADO

* Sidebar limpio
* Sin duplicación de lógica
* Flujo alineado con DB
* Cada menú = una capa del sistema
