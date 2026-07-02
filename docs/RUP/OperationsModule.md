# Especificación Técnica Detallada: OperationsModule (Módulo de Operaciones y Analítica)

> Documento RUP Centrado en Arquitectura
> **Módulo:** OperationsModule
> **Ruta:** `app/Modules/OperationsModule`

## 1. Resumen Ejecutivo y Propósito del Módulo

El **OperationsModule** es el "Cerebro Analítico" y el nivel superior jerárquico del negocio en la plataforma. Su propósito es ingerir, cruzar y reconciliar la data cruda proveniente de recursos humanos (`PersonnelModule`), las planillas de turnos (`WfmModule`) y la realidad transaccional del CTI telefónico (`ConnectModule`).

Al realizar estos cruces, el módulo genera **Métricas de Desempeño (KPIs)**, determina la **Adherencia Real** (si el empleado trabajó lo que se planeó) y automatiza la detección de incidencias de nómina (`AttendanceIncident` como tardanzas o ausencias). Está fuertemente orientado a Proveer Dashboards Gerenciales (`Livewire\Widgets`) y soportar la toma de decisiones intra-día de los Supervisores.

---

## 2. Casos de Uso Detallados

A continuación, los flujos principales de gestión operativa:

### CU-OP-01: Reconciliación Automática de Asistencia y Adherencia

- **Actor:** Sistema (Scheduler / Job Batching).
- **Descripción:** Cruce nocturno (o intra-día) entre lo planificado y lo ejecutado.
- **Flujo Principal:**
  1. El cron job ejecuta `ReconcileAttendanceCommand`, el cual invoca `ReconcileEmployeeAttendanceAction`.
  2. El Action consulta el horario del día en `WfmModule\Schedule`.
  3. Compara ese horario con el primer *log-in* telefónico registrado en `ConnectModule\AgentStateTransition`.
  4. Si el agente inició sesión 10 minutos más tarde que su turno programado (superando la tolerancia global en `AppSetting`), el sistema genera un `AttendanceIncident` de tipo "Tardanza".
  5. Ejecuta en paralelo el `CalculateRealAdherenceAction` para determinar el porcentaje de adherencia del día (Tiempo real trabajado / Tiempo programado).

### CU-OP-02: Monitoreo en Tiempo Real (Realtime Monitoring)

- **Actor:** Supervisor de Operaciones.
- **Descripción:** Visión de helicóptero sobre el piso de atención (Call Center).
- **Flujo Principal:**
  1. El Supervisor ingresa al `RealtimeMonitoring` Dashboard.
  2. El sistema renderiza múltiples Widgets (`QueueStatsWidget`, `StateDistributionWidget`).
  3. Mediante *Livewire Polling* o *WebSockets*, el dashboard muestra cuántos agentes están en llamada, cuántos en *Break*, y cuántas llamadas en cola hay, fusionando datos en caliente del `ConnectModule`.
  4. Si un agente excede su tiempo de baño/break, el `CriticalAlertsWidget` destella en rojo notificando al supervisor.

### CU-OP-03: Consulta de Tablero de Rendimiento (Scorecard)

- **Actor:** Agente / Supervisor.
- **Descripción:** Visualización de KPIs históricos para pago de bonos o *feedback*.
- **Flujo Principal:**
  1. El usuario ingresa a `PerformanceScorecard`.
  2. El `GetStandardizedPerformanceAction` consulta los `AgentDailyMetric` del mes.
  3. Retorna un `StandardizedPerformanceDTO` con métricas pre-calculadas: TMO (Tiempo Medio de Operación), Nivel de Servicio, Calidad y Adherencia, comparando al agente contra el promedio de su equipo (`TeamPerformanceSummary`).

---

## 3. Requerimientos Funcionales (RF)

- **RF-OP-01 (Motor de Cálculo de KPIs):** El módulo debe estandarizar las fórmulas matemáticas de la operación (Ej. $TMO = (TalkTime + HoldTime + ACW) / TotalLlamadas$) dentro de un `PerformanceService` centralizado, evitando lógicas matemáticas dispersas en vistas.
- **RF-OP-02 (Gestión de Tipificaciones de Incidencias):** El sistema debe permitir definir dinámicamente los `IncidentType` (Tardanza, Faltas Injustificadas, Abandono de Turno) y si estos ameritan o no un descuento en nómina.
- **RF-OP-03 (Generador de Reportes Cruzados):** El `ReportingFrameworkIndex` debe permitir exportar cubos de datos a Excel combinando información de `Personnel`, `Wfm` y `Connect`.
- **RF-OP-04 (Línea de Tiempo del Agente):** El `AgentTimeline` debe pintar un gráfico visual (Diagrama de Gantt) contrastando la barra de "Horario Programado" vs la barra fragmentada de "Estados Reales" (Conectado, Break, Baño, Llamada) para justificar discrepancias.

---

## 4. Requerimientos No Funcionales (RNF)

- **RNF-OP-01 (Carga Diferida - Lazy Loading de Widgets):** Los Dashboards pesados (`AdvancedProductivityDashboard`) que componen múltiples sub-consultas SQL deben cargar sus widgets de forma asíncrona (`<livewire:widget lazy />`) para que el cascarón de la página cargue en menos de 1 segundo.
- **RNF-OP-02 (Inmutabilidad de Métricas Cerradas):** Los registros consolidados en `AgentDailyMetric` para días pasados no deben ser recalculados en caliente para evitar lentitud. Deben actuar como un Data Warehouse local (Instantáneas fijas). Si hay una corrección, se debe lanzar un recalculo explícito.
- **RNF-OP-03 (Tolerancia a Datos Incompletos):** El `ReconcileEmployeeAttendanceAction` no debe fallar (Exception) si el sistema telefónico (`ConnectModule`) estuvo caído y no proveyó datos. Debe marcar la adherencia como "Pendiente de Validación Manual".

---

## 5. Modelos de Datos Detallados

El OperationsModule actúa como un almacén de consolidación (Data Mart):

| Atributo | Tipo / Cast | Descripción y Lógica de Negocio |
| :--- | :--- | :--- |
| **Entidad: `AgentDailyMetric`**| | **Métricas Consolidadas (Diarias)** |
| `date` | `date` | Fecha de corte del KPI. |
| `user_id` | `integer` (FK)| Agente evaluado. |
| `total_calls`, `total_sales`| `integer` | Volumen de producción. |
| `adherence_percentage` | `decimal` | % de cumplimiento del horario WFM. |
| `aht_seconds` | `integer` | Average Handle Time (Tiempo medio de atención). |
| **Entidad: `AttendanceIncident`**| | **Incidencias de Nómina (Tardanzas/Faltas)** |
| `user_id` | `integer` (FK)| Empleado infractor. |
| `date` | `date` | Día de la infracción. |
| `incident_type_id` | `integer` (FK)| Relación a la tabla maestra `IncidentType`. |
| `minutes_late` | `integer` | Cuántos minutos se demoró respecto a su turno. |
| `is_justified` | `boolean` | `true` si RRHH subió una constancia médica. |
| **Entidad: `IncidentType`** | | **Maestro de Infracciones** |
| `name` | `string` | "Tardanza leve", "Falta injustificada". |
| `discount_factor` | `decimal` | Factor de penalización salarial. |

---

## 6. Roles y Permisos (Policies)

Este módulo maneja información gerencial sensible:

- **Política de Visibilidad Analítica (`operations.reports.view`):** Solo otorgado a Gerentes, Supervisores y Analistas de WFM. Los agentes solo pueden ver su propio `PerformanceScorecard`, jamás el `TeamPerformanceSummary` global.
- **Gestión de Incidencias (`incidents.manage`):** Solo RRHH y Operaciones pueden cambiar el flag `is_justified` de un `AttendanceIncident` para perdonar una tardanza.

---

## 7. Eventos, Listeners y Notificaciones

- `AttendanceIncidentGenerated`: Disparado por el Job de reconciliación cuando un agente acumula una falta grave. Puede enviar un correo automático al agente y a su supervisor solicitando justificación en 24 horas.
- Escucha pasiva: El módulo no tiene Listeners explícitos para transacciones en vivo de CTI; consolida la data por lotes (Batch) para proteger el rendimiento, salvo en el `RealtimeMonitoring` donde lee directo de DB.

---

## 8. Servicios y Acciones Detallados (Actions & Services)

### `ReconcileEmployeeAttendanceAction`

- **Responsabilidad:** Generar `AttendanceIncident` cruzando planillas vs realidad.
- **Lógica:**
  1. Recibe una `$date` y un `$userId`.
  2. Obtiene el `$shift` (Turno) de ese día desde el `WfmModule`. Si no hay turno planificado y hay conexión, genera incidencia de "Horas Extra No Autorizadas".
  3. Si hay turno, obtiene el primer `LOGIN` del `ConnectModule`.
  4. Calcula: `$diferencia = $loginTime->diffInMinutes($shift->start_time)`.
  5. Si `$diferencia > AppSetting::get('tolerancia_minutos')`, crea el `AttendanceIncident`.

### `PerformanceService`

- Clase estática o Singleton inyectada que contiene pura lógica matemática. Define los estándares de la compañía para calcular el *Shrinkage*, TMO, Adherencia y Ocupación. Evita que `CalculateAdvancedProductivityAction` esté sobrecargado de fórmulas.

---

## 9. Endpoints o Rutas Detalladas (Livewire / Web)

Este módulo es intensivo en Componentes Visuales (Livewire SPA):

- **`GET /operations/dashboard`** -> Componente `Livewire\Dashboard`.
  - Tablero maestro para directores. Instancia componentes hijos tipo *Widget* (`HeroKpiWidget`, `VolumeComparisonWidget`).
- **`GET /operations/realtime`** -> Componente `Livewire\RealtimeMonitoring`.
  - La herramienta de batalla del Supervisor intra-día. Incluye el `AgentRealtimeCard` iterado por cada agente activo y el `CriticalAlertsWidget`. Usa `wire:poll` para mantenerse fresco.
- **`GET /operations/performance/{user?}`** -> Componente `Livewire\PerformanceScorecard`.
  - Tarjeta de puntuación del agente, con gráficos de tendencia (ej. usando ApexCharts integrados con AlpineJS o Livewire).

---

## 10. Dependencias con otros Módulos

El OperationsModule es el máximo consumidor (Downstream absoluto) de la plataforma:

- **Depende de `WfmModule`:** Necesita las mallas horarias (`Schedules`) para saber qué debía hacer el empleado.
- **Depende de `ConnectModule`:** Necesita el histórico telefónico (`CallRecords` y `AgentStateTransitions`) para saber qué hizo realmente el empleado.
- **Depende de `PersonnelModule`:** Para agrupar las métricas y los dashboards por los departamentos, campañas o supervisores a los que pertenecen los agentes.
- **Depende de `CoreModule`:** Para autorización, jerarquías de IAM y variables globales (`AppSetting`).

---

## 11. Estructura de Carpetas

```tree
app/Modules/OperationsModule
├── Actions
│   ├── CalculateAdvancedProductivityAction.php
│   ├── CalculateRealAdherenceAction.php
│   ├── GetEmployeePerformanceAction.php
│   ├── GetStandardizedPerformanceAction.php
│   └── ReconcileEmployeeAttendanceAction.php
├── Console
│   └── Commands
│       └── ReconcileAttendanceCommand.php
├── DTOs
│   ├── EmployeePerformanceDTO.php
│   └── StandardizedPerformanceDTO.php
├── Livewire
│   ├── AdvancedProductivityDashboard.php
│   ├── AgentRealtimeCard.php
│   ├── AgentTimeline.php
│   ├── Dashboard.php
│   ├── IntradayAvailability.php
│   ├── PerformanceScorecard.php
│   ├── QueuePerformanceReport.php
│   ├── RealtimeMonitoring.php
│   ├── ReportingFrameworkIndex.php
│   ├── TeamPerformanceSummary.php
│   └── Widgets
│       ├── CriticalAlertsWidget.php
│       ├── HeroKpiWidget.php
│       ├── QueueStatsWidget.php
│       ├── RecentIncidentsWidget.php
│       ├── StateDistributionWidget.php
│       └── VolumeComparisonWidget.php
├── Models
│   ├── AgentDailyMetric.php
│   ├── AttendanceIncident.php
│   └── IncidentType.php
├── Providers
│   └── ModuleServiceProvider.php
├── Resources
│   └── Views
│       └── livewire
│           ├── advanced-productivity-dashboard.blade.php
│           ├── agent-realtime-card.blade.php
│           ├── agent-timeline.blade.php
│           ├── dashboard.blade.php
│           ├── intraday-availability.blade.php
│           ├── performance-scorecard.blade.php
│           ├── queue-performance-report.blade.php
│           ├── realtime-monitoring.blade.php
│           ├── reporting-framework-index.blade.php
│           ├── team-performance-summary.blade.php
│           └── widgets
│               ├── critical-alerts-widget.blade.php
│               ├── hero-kpi-widget.blade.php
│               ├── queue-stats-widget.blade.php
│               ├── recent-incidents-widget.blade.php
│               ├── state-distribution-widget.blade.php
│               └── volume-comparison-widget.blade.php
├── Routes
│   └── web.php
└── Services
    └── PerformanceService.php

```
