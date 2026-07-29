## TL;DR

No crearía un menú nuevo llamado **Analytics** desde el inicio. La navegación debe seguir el flujo natural de un WFM: **Operación → Planificación → Analítica → Administración**. El nuevo módulo aporta vistas a distintos perfiles, no un repositorio aislado de reportes.

---

# Propuesta de MenuHelper

```text
Dashboard

Mi Trabajo

Mi Equipo

Planificación

Operación

Analítica

Calidad

Administración
```

La diferencia importante es la incorporación de **Operación** y **Analítica** como dominios independientes.

---

# Dashboard

No cambia de ubicación, pero evoluciona.

## Dashboard Ejecutivo

```
Dashboard
 └── Inicio
```

Widgets adicionales

* Cobertura del día
* Forecast vs Real
* Occupancy
* Adherencia
* Alertas de Staffing
* SLA por cola
* Tendencias

---

# Mi Trabajo

No requiere cambios.

```
Mi Trabajo
 ├── Mi Horario
 ├── Mi Día
 ├── Mis Métricas
 ├── Solicitar Permiso
 ├── Solicitar Cambio
 ├── Mis Solicitudes
 └── Notificaciones
```

---

# Mi Equipo

Agregar inteligencia operacional.

```
Mi Equipo
 ├── Mi Equipo
 ├── Aprobaciones
 ├── Resumen de Solicitudes
 ├── Adherencia del Equipo
 ├── Estados en Tiempo Real
 └── Productividad
```

## Nuevas vistas

### Team Adherence

```
schedules/team-adherence
```

Supervisor observa

* agentes fuera de horario
* adherencia
* retrasos
* exceso de break

---

### Team Productivity

```
schedules/team-productivity
```

Muestra

* Occupancy
* Utilization
* Calls
* AHT
* ACW

---

### Real Time Team

```
operations/team-live
```

Live View

Similar a Cisco Supervisor Desktop.

---

# Planificación

Aquí aparecen varios módulos nuevos.

```
Planificación

├── Semanas
├── Equipos
├── Empleados
├── Forecast
├── Staffing
├── Capacity Planning
├── Shrinkage
└── Escenarios
```

---

## Forecast

Nueva vista

```
Planning

└── Forecast
```

Funciones

* importar forecast

* editar forecast

* comparar versiones

* publicar forecast

---

## Staffing

```
Planning

└── Staffing
```

Vista tipo matriz

```text
Hora

Forecast

Requeridos

Programados

Gap

Cobertura
```

Muy similar a NICE.

---

## Capacity Planning

```
Planning

└── Capacity
```

Permite responder

¿Cuántos agentes puedo sacar para capacitación?

---

## Shrinkage

```
Planning

└── Shrinkage
```

Dashboard

```
Vacaciones

Permisos

Meeting

Training

Coaching

Break

Lunch
```

---

## Escenarios

```
Planning

└── Escenarios
```

Ejemplo

```
Escenario A

Escenario B

Escenario Navidad

Escenario Black Friday
```

Comparación de Forecast.

---

# Nueva sección

# Operación

Esta sección no existe hoy.

Debe vivir en OperationsModule.

```
Operación

├── Tiempo Real
├── Estados
├── Colas
├── Llamadas
├── Intervalos
├── Alertas
└── Timeline
```

---

## Tiempo Real

```
operations/live
```

Widgets

* agentes conectados

* llamadas esperando

* SLA

* ASA

* abandonadas

---

## Estados

```
operations/states
```

Tabla

```
Agente

Estado

Tiempo

Motivo

Supervisor
```

---

## Colas

```
operations/queues
```

Dashboard por Queue.

---

## Intervalos

```
operations/intervals
```

La vista más importante.

Tabla

```
08:00

Forecast

Real

Occupancy

AHT

ASA

SL

Adherence

Calls
```

---

## Timeline

```
operations/timeline
```

Historial completo.

```
Login

Ready

Call

Hold

ACW

Break

Logout
```

---

# Nueva sección

# Analítica

Aquí vive AnalyticsModule.

```
Analítica

├── KPIs
├── Forecast Accuracy
├── Staffing Analysis
├── Capacity
├── Tendencias
├── Skills
├── Heatmaps
├── Comparativos
└── Data Explorer
```

---

## KPIs

Dashboard Ejecutivo.

```
analytics/kpis
```

Cards

* Occupancy

* Utilization

* Productivity

* Adherence

* Conformance

* Shrinkage

---

## Forecast Accuracy

```
analytics/forecast-accuracy
```

Gráficas

Forecast

↓

Real

↓

MAPE

↓

Bias

---

## Staffing Analysis

```
analytics/staffing
```

Visualiza

```
Sobre dimensionado

Sub dimensionado

Cobertura
```

---

## Tendencias

```
analytics/trends
```

Gráficas

* mensual

* semanal

* anual

---

## Skills

```
analytics/skills
```

Heatmap

```
Skill

Cantidad

Cobertura

Déficit
```

---

## Heatmaps

```
analytics/heatmaps
```

Mapa de calor

```
Horas

×

Días

×

Occupancy
```

---

## Comparativos

```
analytics/comparison
```

Comparar

Supervisor

Equipo

Campaña

Cola

---

## Data Explorer

Vista avanzada.

```
analytics/explorer
```

Power Users.

Permite construir consultas sin SQL.

---

# Calidad

Integración con Analytics.

```
Calidad

├── Evaluaciones
├── Formularios
├── Calibraciones
├── Coaching
└── Correlación Calidad
```

Nueva vista

```
quality/correlation
```

Ejemplo

```
Quality

vs

Occupancy

vs

AHT
```

---

# Administración

Agregar únicamente configuración.

```
Administración

├── Skills
├── Forecast Groups
├── Queues
├── Intervalos
├── Shrinkage
├── Holidays
└── Capacity Rules
```

No colocar reportes aquí.

---

# Evolución del MenuHelper

```text
Dashboard
│
├── Inicio
│
├── Mi Trabajo
│   ├── Mi Horario
│   ├── Mi Día
│   ├── Mis Métricas
│   ├── Permisos
│   ├── Cambios de Turno
│   └── Notificaciones
│
├── Mi Equipo
│   ├── Mi Equipo
│   ├── Aprobaciones
│   ├── Adherencia
│   ├── Productividad
│   └── Tiempo Real
│
├── Planificación
│   ├── Semanas
│   ├── Equipos
│   ├── Empleados
│   ├── Forecast
│   ├── Staffing
│   ├── Capacity
│   ├── Shrinkage
│   └── Escenarios
│
├── Operación
│   ├── Tiempo Real
│   ├── Estados
│   ├── Colas
│   ├── Intervalos
│   ├── Llamadas
│   ├── Alertas
│   └── Timeline
│
├── Analítica
│   ├── KPIs
│   ├── Forecast Accuracy
│   ├── Staffing Analysis
│   ├── Capacity
│   ├── Tendencias
│   ├── Skills
│   ├── Heatmaps
│   ├── Comparativos
│   └── Data Explorer
│
├── Calidad
│   ├── Evaluaciones
│   ├── Coaching
│   ├── Calibraciones
│   └── Correlación
│
└── Administración
    ├── Skills
    ├── Forecast Groups
    ├── Queues
    ├── Holidays
    ├── Shrinkage
    └── Capacity Rules
```

Esta organización respeta los límites de los módulos del proyecto: `OperationsModule` expone la operación en tiempo real, `WfmModule` concentra la planificación, `AnalyticsModule` ofrece inteligencia y análisis, `QualityModule` mantiene la gestión de calidad y `Core/Personnel` alojan únicamente la configuración transversal. El usuario navega siguiendo el flujo natural del negocio (operar → planificar → analizar), en lugar de una clasificación basada en la implementación técnica.

---

# Apéndice A — Estado de implementación vs. propuesta de vistas

Evaluación de cada vista propuesta contra el código existente tras los 12 sprints de Upgrade 0.1→0.2.

## Dashboard

| Widget              | Datos disponibles                                                                                   | Estado                                                     |
| ------------------- | --------------------------------------------------------------------------------------------------- | ---------------------------------------------------------- |
| Cobertura del día   | `staffing_requirements.coverage`                                                                    | Tabla persistida por `CalculateStaffingRequirementsAction` |
| Forecast vs Real    | `forecast_accuracy.accuracy`, `forecast_intervals.call_volume_forecast` vs `agent_call_performance` | Parcial — requiere vista de comparación                    |
| Occupancy           | `daily_kpis.occupancy` (niveles employee, team, global)                                             | Materializado                                              |
| Adherencia          | `daily_kpis.adherence`                                                                              | Materializado                                              |
| Alertas de Staffing | `staffing_requirements.gap > 0`                                                                     | Consulta directa                                           |
| SLA por cola        | `daily_kpis.service_level`                                                                          | Materializado (nuevo)                                      |
| Tendencias          | `daily_kpis` histórico por fecha                                                                    | Agregación directa                                         |

## Operación

| Vista       | Ruta propuesta         | Código existente                         | Gaps                                                                                                             |
| ----------- | ---------------------- | ---------------------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| Tiempo Real | `operations/live`      | ✅ `operations.realtime-monitoring`       | —                                                                                                                |
| Estados     | `operations/states`    | ✅ `operations.agent-timeline`            | —                                                                                                                |
| Colas       | `operations/queues`    | ⚠️ `operations.queue-performance-report`  | No es dashboard por cola, es reporte                                                                             |
| Intervalos  | `operations/intervals` | 🔴 No existe                              | Tabla 96 slots con Forecast/Real/Occupancy/AHT/ASA/SL — datos en `agent_interval_metrics` + `forecast_intervals` |
| Llamadas    | `operations/calls`     | ⚠️ `connect::livewire.create-call-record` | Vista de registro, no de consulta                                                                                |
| Alertas     | `operations/alerts`    | ✅ CriticalAlertsWidget                   | —                                                                                                                |
| Timeline    | `operations/timeline`  | ✅ AgentTimeline                          | —                                                                                                                |

## Planificación

| Vista      | Ruta propuesta       | Código existente | Gaps                                                                                                                             |
| ---------- | -------------------- | ---------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| Forecast   | `planning/forecast`  | 🔴 No existe      | CRUD sobre `forecast_groups/versions/scenarios/intervals`. Actions ya existen (`ImportForecastAction`, `GenerateForecastAction`) |
| Staffing   | `planning/staffing`  | 🔴 No existe      | Matriz hora: Forecast / Requeridos / Programados / Gap / Cobertura. Datos en `staffing_requirements`                             |
| Capacity   | `planning/capacity`  | 🔴 No existe      | Dashboard sobre `capacity_plans/intervals/results`                                                                               |
| Shrinkage  | `planning/shrinkage` | 🔴 No existe      | Dashboard sobre `historical_shrinkage` + `shrinkage_categories`                                                                  |
| Escenarios | `planning/scenarios` | 🔴 No existe      | Comparación de `forecast_scenarios` vía `forecast_intervals`                                                                     |

## Analítica

| Vista             | Ruta propuesta                | Código existente | Gaps                                                                   |
| ----------------- | ----------------------------- | ---------------- | ---------------------------------------------------------------------- |
| KPIs              | `analytics/kpis`              | 🔴 No existe      | Tarjetas desde `daily_kpis` por granularidad employee/team/global      |
| Forecast Accuracy | `analytics/forecast-accuracy` | 🔴 No existe      | Gráficas MAPE + Bias + RMSE desde `forecast_accuracy`                  |
| Staffing Analysis | `analytics/staffing`          | 🔴 No existe      | Visual sobredimensionado/subdimensionado desde `staffing_requirements` |
| Capacity          | `analytics/capacity`          | 🔴 No existe      | Dashboard desde `capacity_results`                                     |
| Tendencias        | `analytics/trends`            | 🔴 No existe      | Series mensual/semanal/anual desde `daily_kpis` y `fact_*`             |
| Skills            | `analytics/skills`            | 🔴 No existe      | Heatmap cobertura desde `employee_skills` + `queue_skills`             |
| Comparativos      | `analytics/comparison`        | 🔴 No existe      | Por supervisor/equipo/cola                                             |
| Data Explorer     | `analytics/explorer`          | 🔴 No existe      | Consultas ad-hoc sobre `fact_*` sin SQL                                |

## Calidad

| Vista               | Ruta propuesta        | Código existente | Gaps                                         |
| ------------------- | --------------------- | ---------------- | -------------------------------------------- |
| Correlación Calidad | `quality/correlation` | 🔴 No existe      | Scatter Quality vs Occupancy, Quality vs AHT |

## Administración (catálogos)

| Vista                | Datos                                     | Estado                       |
| -------------------- | ----------------------------------------- | ---------------------------- |
| Skills CRUD          | `skills` table (PersonnelModule)          | Tabla existe, no hay UI      |
| Forecast Groups CRUD | `forecast_groups`                         | Tabla existe, no hay UI      |
| Holidays             | `analytics_calendar_dimension.is_holiday` | Columna existe, no hay UI    |
| Shrinkage Categories | `shrinkage_categories`                    | Seed data existe, no hay UI  |
| Capacity Rules       | Configuración de parámetros               | No existe                    |
| Intervalos           | `analytics_time_interval_dimension`       | Tabla existe, no hay UI      |
| Queues               | `call_queues`                             | CRUD existe en ConnectModule |

## Resumen cuantitativo

| Tipo                                     | Cantidad |
| ---------------------------------------- | -------- |
| Vistas existentes (sin cambios)          | 6        |
| Vistas existentes con gaps menores       | 3        |
| Vistas nuevas requeridas                 | 18       |
| Catálogos/CRUD sin UI                    | 6        |
| **Total vistas/catálogos a implementar** | **~24**  |

## Preguntas abiertas para decisión

1. **Intervalos** — ¿tabla de 96 filas (un slot por fila) o heatmap? La tabla es inmediata con `agent_interval_metrics`. El heatmap requiere agregación adicional por hora/día.

2. **Staffing Analysis** — ¿calcular en tiempo real sobre `staffing_requirements` + forecast, o consumir datos ya persistidos por `CalculateStaffingRequirementsAction`?

3. **Data Explorer** — ¿generador de consultas ad-hoc sobre `fact_*` tables o interfaz drag-and-drop tipo Power BI embebido? Implicaciones de complejidad muy diferentes.

4. **Correlación Calidad** — ¿dashboard fijo (scatter plots Quality vs Occupancy, Quality vs AHT) o configurable?

5. **Capacity Rules / Holidays** — ¿catálogos CRUD simples o con lógica de validación?


---

Operación (siguiente sección después de Dashboard):
1. Colas ⚠️ — operations.queue-performance-report existe pero es reporte, no dashboard por cola
2. Intervalos 🔴 — "la vista más importante", tabla 96 slots con Forecast/Real/Occupancy/AHT/ASA/SL. Datos en agent_interval_metrics + forecast_intervals
3. Llamadas ⚠️ — existe vista de registro, no de consulta
Planificación (todas 🔴):
4. Forecast — CRUD sobre forecast_groups/versions/scenarios/intervals
5. Staffing — Matriz hora con Forecast/Requeridos/Programados/Gap/Cobertura
6. Capacity — Dashboard sobre capacity_plans/intervals/results
7. Shrinkage — Dashboard sobre historical_shrinkage + shrinkage_categories
8. Escenarios — Comparación de forecast_scenarios
¿Por cuál empezamos?

Analítica (8 vistas 🔴):
Vista	Ruta	Descripción
KPIs	analytics/kpis	Cards: Occupancy, Utilization, Productivity, Adherence, Conformance, Shrinkage desde daily_kpis
Forecast Accuracy	analytics/forecast-accuracy	MAPE + Bias + RMSE desde forecast_accuracy
Staffing Analysis	analytics/staffing	Sobredimensionado/subdimensionado desde staffing_requirements
Capacity	analytics/capacity	Dashboard desde capacity_results
Tendencias	analytics/trends	Series mensual/semanal/anual desde daily_kpis y fact_*
Skills	analytics/skills	Heatmap cobertura desde employee_skills + queue_skills
Comparativos	analytics/comparison	Por supervisor/equipo/cola
Data Explorer	analytics/explorer	Consultas ad-hoc sobre fact_*