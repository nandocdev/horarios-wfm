# Análisis del Módulo de Reportería WFM — ReportingModule

> Análisis del estado actual del módulo ReportingModule: implementación, calidad y brechas.
> Julio 2026

---

## 1. Resumen Ejecutivo

El módulo **ReportingModule** existe, está registrado en `config/modules.php` y tiene **60 archivos** distribuidos en 9 directorios. Cubre **13 tipos de reportes** en 4 categorías con exportación a PDF y XLS. Está funcional pero tiene brechas en testing y pequeños defectos.

| Métrica                | Valor                                            |
| ---------------------- | ------------------------------------------------ |
| Archivos PHP           | 42                                               |
| Vistas Blade           | 18                                               |
| Tests                  | 1 archivo (120 líneas, 8 casos)                  |
| Actions                | 19                                               |
| DTOs                   | 16                                               |
| Reportes disponibles   | 13                                               |
| Formato PDF            | ✅ vía dompdf                                     |
| Formato XLS            | ✅ vía HTML table                                 |
| ¿Cubre ausentismo?     | ✅ Crudo + excepciones                            |
| ¿Cubre AHT?            | ✅ Detallado + resumen                            |
| ¿Cubre volumen?        | ✅ Detalle + intervalo + resumen                  |
| ¿Incluye max/min wait? | ✅ `maxWaitTime`, `minWaitTime`, `avgAbandonTime` |

---

## 2. Estructura Completa

```
app/Modules/ReportingModule/
├── Actions/                              (19 archivos)
│   ├── ExportAgentPerformanceAction.php   → Desempeño individual
│   ├── ExportAhtDetailAction.php          → AHT detallado por agente
│   ├── ExportAhtSummaryAction.php         → AHT resumen por cola
│   ├── ExportAttendanceSummaryAction.php  → Resumen global de asistencia
│   ├── ExportExceptionSummaryAction.php   → Resumen por causa de ausencia
│   ├── ExportIntradayActivitiesAction.php → Actividades intradía
│   ├── ExportLeavesAction.php             → Permisos
│   ├── ExportPeriodActivitiesAction.php   → Actividades por período
│   ├── ExportRankingAction.php            → Ranking de agentes
│   ├── ExportRawAbsenteeismAction.php     → Ausentismo crudo
│   ├── ExportTardinessAction.php          → Tardanzas
│   ├── ExportTeamPerformanceAction.php    → Desempeño por equipo
│   ├── ExportVacationsAction.php          → Vacaciones
│   ├── ExportVolumeByIntervalAction.php   → Volumen por intervalo
│   ├── ExportVolumeDetailAction.php       → Volumen detalle por cola
│   ├── ExportVolumeSummaryAction.php      → Volumen consolidado
│   ├── FetchReportDataAction.php          → Obtiene datos para preview (453 líneas)
│   ├── GeneratePdfReportAction.php        → Genera PDF genérico (anónimo extends BaseReport)
│   └── GenerateXlsReportAction.php        → Genera XLS genérico (HTML table)
├── DTOs/                                 (16 archivos)
│   ├── AbsenteeismRowDTO.php
│   ├── AgentPerformanceRowDTO.php
│   ├── AhtRowDTO.php
│   ├── AttendanceSummaryRowDTO.php
│   ├── ExceptionSummaryRowDTO.php
│   ├── IntradayActivityRowDTO.php
│   ├── LeaveRowDTO.php
│   ├── PeriodActivityRowDTO.php
│   ├── RankingRowDTO.php
│   ├── ReportFilterDTO.php
│   ├── ReportPreviewResult.php
│   ├── TardinessRowDTO.php
│   ├── TeamPerformanceRowDTO.php
│   ├── VacationRowDTO.php
│   ├── VolumeIntervalRowDTO.php
│   └── VolumeRowDTO.php
├── Enums/
│   └── ReportFormatEnum.php              → Pdf, Xls
├── Livewire/
│   ├── ReportGenerator.php                → Componente principal (245 líneas)
│   └── Forms/
│       └── ReportGeneratorForm.php        → Validación de filtros
├── Policies/
│   └── ReportPolicy.php                   → export, viewAll, viewTeam
├── Providers/
│   └── ModuleServiceProvider.php          → 29 líneas
├── Repositories/
│   └── EloquentReportDataRepository.php   → 808 líneas (todas las queries)
├── Resources/Views/
│   ├── livewire/
│   │   └── report-generator.blade.php     → UI principal (166 líneas)
│   └── reports/
│       ├── layout.blade.php               → Layout PDF corporativo (153 líneas)
│       ├── xls-table.blade.php            → Template XLS
│       ├── aht-detail.blade.php
│       ├── ausentismo-exceptions.blade.php
│       ├── ausentismo-raw.blade.php
│       ├── volume-summary.blade.php
│       ├── activities/
│       │   ├── intraday.blade.php
│       │   └── period.blade.php
│       ├── attendance/
│       │   ├── absences.blade.php
│       │   ├── leaves.blade.php
│       │   ├── summary.blade.php
│       │   ├── tardiness.blade.php
│       │   └── vacations.blade.php
│       ├── performance/
│       │   ├── agent.blade.php
│       │   ├── ranking.blade.php
│       │   └── team.blade.php
│       └── volume/
│           └── interval.blade.php
└── Routes/
    └── web.php                            → 2 rutas
```

---

## 3. Cobertura vs. Requerimiento Original

| Reporte solicitado               | Estado             | Detalle                                                                                              |
| -------------------------------- | ------------------ | ---------------------------------------------------------------------------------------------------- |
| Ausentismo — Crudo               | ✅ Implementado     | Union de `ScheduleException` + `AttendanceIncident`, con origen, causa, justificación, minutos       |
| Ausentismo — Excepciones         | ✅ Implementado     | Agrupado por `AbsenceReasonCode` con total ocurrencias, minutos perdidos, empleados afectados        |
| AHT                              | ✅ Implementado     | Detallado por agente/cola (talk_time, work_time, hold_time, aht, goal, deviation) + resumen por cola |
| Volumen — Recibidos              | ✅ `received`       | `COUNT(*)`                                                                                           |
| Volumen — Atendidos              | ✅ `handled`        | `FILTER (WHERE contact_disposition = 2)`                                                             |
| Volumen — Abandonados            | ✅ `abandoned`      | `FILTER (WHERE contact_disposition IN 1,4,13)`                                                       |
| Volumen — AHT                    | ✅ `aht`            | `AVG(talk_time + work_time)` de atendidos                                                            |
| Volumen — Tiempo espera atención | ✅ `asa`            | `AVG(queue_time)` de atendidos                                                                       |
| Volumen — Tiempo abandono        | ✅ `avgAbandonTime` | `AVG(queue_time)` de abandonados                                                                     |
| Volumen — Max                    | ✅ `maxWaitTime`    | `MAX(queue_time)`                                                                                    |
| Volumen — Min                    | ✅ `minWaitTime`    | `MIN(queue_time)`                                                                                    |

**Adicionalmente implementado** (fuera del requerimiento original):

| Reporte extra                 | Categoría   |
| ----------------------------- | ----------- |
| Tardanzas                     | attendance  |
| Permisos                      | attendance  |
| Vacaciones                    | attendance  |
| Resumen Global de Asistencia  | attendance  |
| Actividades Intradía          | activities  |
| Actividades por Período       | activities  |
| Volumen por Intervalo (30min) | volume      |
| Desempeño por Agente          | performance |
| Desempeño por Equipo          | performance |
| Ranking de Agentes            | performance |

---

## 4. Análisis de Calidad del Código

### 4.1 Buenas Prácticas ✅

| Aspecto                      | Estado                                         |
| ---------------------------- | ---------------------------------------------- |
| `declare(strict_types=1)`    | ✅ 100% archivos                                |
| `readonly class` para DTOs   | ✅ 16/16 DTOs                                   |
| `final class` en Actions     | ✅ 19/19 Actions                                |
| Constructor injection        | ✅ Todas las Actions usan DI                    |
| Single `execute()`           | ✅ Todas las Actions                            |
| Form Object para validación  | ✅ `ReportGeneratorForm`                        |
| Uso de `Gate::authorize()`   | ✅ En `generate()` y `export()`                 |
| `#[Computed]` properties     | ✅ En Livewire (categories, subReports, titles) |
| Union queries con PostgreSQL | ✅ `unionAll()` para absenteeism                |
| FILTER clauses (PostgreSQL)  | ✅ `COUNT(*) FILTER (WHERE ...)`                |

### 4.2 Observaciones ⚠️

| Issue                                                                        | Dónde                                                                            | Detalle                                                                                                                                                                                                                       |
| ---------------------------------------------------------------------------- | -------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `$userRole` no definido                                                      | `layout.blade.php:108`                                                           | La vista referencia `{{ $userRole }}` pero ninguna Action lo pasa en `$data`. Causará error silencioso o null.                                                                                                                |
| FetchReportDataAction grande                                                 | 453 líneas                                                                       | 13 métodos privados, cada uno con lógica de transformación. Podría beneficiarse de extracción a Services.                                                                                                                     |
| Repository monolítico                                                        | 808 líneas                                                                       | `EloquentReportDataRepository` concentra todas las queries de los 13 reportes. Alto acoplamiento a modelos de 5 módulos distintos.                                                                                            |
| Cross-module imports directos                                                | Repository importa 6 modelos foráneos                                            | `AgentCallPerformance`, `CallRecord`, `AttendanceIncident`, `Employee`, `AbsenceReasonCode`, `IntradayActivity`, `ScheduleException`, `DailyOperatorReport`. Aceptable para módulo Supporting read-only pero dificulta tests. |
| Sin tests de exportación                                                     | —                                                                                | No hay tests que verifiquen que `ExportXxxAction::execute()` genera un PDF o XLS válido.                                                                                                                                      |
| Sin tests del Repository                                                     | —                                                                                | Las 13 queries no tienen cobertura.                                                                                                                                                                                           |
| `formatSeconds()` duplicado                                                  | `ExportAhtDetailAction`, `ExportVolumeDetailAction`, `ExportVolumeSummaryAction` | El mismo helper `formatSeconds()` está copiado en 3 Actions. Debería estar en un trait o helper compartido.                                                                                                                   |
| `formatDuration()` en FetchReportDataAction vs `BaseReport::formatSeconds()` | Duplicación de utilidad de formateo                                              | Ambos hacen lo mismo con lógica diferente.                                                                                                                                                                                    |
| SLA calculation ruidosa                                                      | `calculateSlaPercentage()`                                                       | Hace una subquery por cada fila (N+1). Podría calcularse con una window function.                                                                                                                                             |
| `env()` en vez de `config()`                                                 | (no presente en este módulo)                                                     | ✅ No se usa `env()` directamente.                                                                                                                                                                                             |

### 4.3 Bugs Potenciales 🐛

| Bug                                                                             | Línea                  | Impacto                                                                                                         |
| ------------------------------------------------------------------------------- | ---------------------- | --------------------------------------------------------------------------------------------------------------- |
| `$userRole` undefined en layout                                                 | `layout.blade.php:108` | La word "Por: usuario ()" mostrará rol vacío                                                                    |
| `baseAbsenceQuery` usa `is_excused` booleano pero filtra por `unexcusedCodes()` | Repo:46-48             | RawAbsenteeism filtra solo ausencias no justificadas, contradice el concepto de "crudo" (debería mostrar todas) |

---

## 5. Cobertura de Tests

1 archivo: `tests/Feature/Modules/ReportingModule/ReportGeneratorTest.php` (120 líneas, 8 casos)

| Test                                     | Tipo             | Resultado |
| ---------------------------------------- | ---------------- | --------- |
| Redirect unauthenticated                 | HTTP             | ✅         |
| 13 rutas render OK para coordinator      | HTTP con dataset | ✅         |
| Mount con category/subReport desde route | Livewire         | ✅         |
| Validación: dateFrom/dateTo requeridos   | Livewire         | ✅         |
| Validación: dateFrom after dateTo        | Livewire         | ✅         |
| Bloqueo a rol operator                   | Livewire         | ✅         |
| Acceso a role coordinator                | Livewire         | ✅         |
| Reporte inválido lanza exception         | Livewire         | ✅         |

**Brechas de testing:**

| Debería testearse                                     | Prioridad |
| ----------------------------------------------------- | --------- |
| Export PDF genera respuesta válida                    | Alta      |
| Export XLS genera respuesta válida                    | Alta      |
| Datos del Repository se mapean correctamente a DTOs   | Alta      |
| Filtros (teamId, employeeId, queueId) afectan queries | Media     |
| Reporte vacío muestra empty state                     | Media     |
| Combinación de filtros produce datos correctos        | Media     |
| El gráfico de volumen por intervalo se renderiza      | Baja      |

---

## 6. Dependencias Cross-Module

| Módulo           | Modelos importados en Repository                                                    | Tipo de acceso                                      |
| ---------------- | ----------------------------------------------------------------------------------- | --------------------------------------------------- |
| ConnectModule    | `AgentCallPerformance`, `CallRecord`                                                | Lectura (reportes volumen/AHT)                      |
| OperationsModule | `AttendanceIncident`                                                                | Lectura (reporte ausentismo)                        |
| WfmModule        | `AbsenceReasonCode`, `ScheduleException`, `IntradayActivity`, `DailyOperatorReport` | Lectura (reportes ausentismo/actividades/desempeño) |
| PersonnelModule  | `Employee`                                                                          | Lectura (JOIN en todas las queries)                 |
| CoreModule       | `User`                                                                              | Solo en Policy                                      |

**NOTA:** El módulo **no usa ningún Shared Contract**. Podría beneficiarse de los existentes (`TelemetryRealtimeRepositoryInterface`, `AgentPerformanceRepositoryInterface`, `DashboardScheduleQueriesInterface`) para desacoplarse, pero para un módulo Supporting read-only el acceso directo es aceptable.

---

## 7. UI/UX del Generador de Reportes

El Livewire `ReportGenerator` tiene una interfaz completa:

1. **Tabs de categoría** (Asistencia, Actividades, Volumen, Rendimiento) con iconos Flux
2. **Pills de subreporte** con selección activa
3. **Título y descripción** dinámicos vía `#[Computed]`
4. **Componente de filtros** compartido via `x-reporting.filters`
5. **KPIs** en grid 4 columnas con iconos
6. **Gráfico ApexCharts** para volumen por intervalo
7. **Tabla de datos** con rendering condicional de booleanos y valores null
8. **Botones de exportación** PDF y XLS (solo si `reports.export`)

---

## 8. Resumen de Hallazgos

### Fortalezas

- Módulo completo y funcional con 13 reportes
- Sigue todas las convenciones del proyecto (`strict_types`, `readonly`, `final`, DI, Form Objects)
- Buena UI con Livewire + Flux
- Cubre exhaustivamente el requerimiento de ausentismo, AHT y volumen
- Más alcance del solicitado originalmente (10 reportes adicionales)
- Exportación dual PDF/XLS
- Test de rutas y autorización

### Debilidades

- **Bug**: `$userRole` no definido en layout PDF
- **Bug**: RawAbsenteeism filtra solo no justificados (debería ser "crudo" = todos)
- **N+1**: `calculateSlaPercentage()` ejecuta subquery por fila
- **Duplicación**: `formatSeconds()` copiado en 3 Actions
- **Sin tests de datos**: Repository y export actions sin cobertura
- **Monolito**: Repository de 808 líneas con acoplamiento a 6 modelos foráneos
- **Split de layouts**: `app/Reports/` coexiste con `app/Modules/ReportingModule/` sin clara separación

---

## 9. Plan de Acción para Mitigación

Prioridades ordenadas por impacto y esfuerzo:

### 🔴 Prioridad Alta (bugs funcionales)

| # | Hallazgo | Acción | Archivos afectados | Esfuerzo |
|---|---|---|---|---|
| 1 | `$userRole` undefined en layout PDF | Agregar `'userRole' => auth()->user()?->roles->first()?->name` en el array `$data` de `GeneratePdfReportAction::execute()` | `Actions/GeneratePdfReportAction.php`, `Resources/Views/reports/layout.blade.php` | 15 min |
| 2 | RawAbsenteeism filtra solo no justificados | Eliminar el `whereIn('absence_reason_codes.short_code', $this->unexcusedCodes())` de `getRawAbsenteeismData()` para que muestre TODAS las ausencias. Para filtrar por justificados usar el filtro `$filters->justified` existente en `ReportFilterDTO`. | `Repositories/EloquentReportDataRepository.php:48-49` | 15 min |
| 3 | N+1 en `calculateSlaPercentage()` | Reemplazar subquery por fila con cálculo en la query principal usando `COUNT(*) FILTER (WHERE queue_time <= :threshold) / COUNT(*) * 100` dentro del mismo `SELECT`. | `Repositories/EloquentReportDataRepository.php` método `getVolumeDetailData()` y `calculateSlaPercentage()` | 30 min |

### 🟡 Prioridad Media (deuda técnica)

| # | Hallazgo | Acción | Archivos afectados | Esfuerzo |
|---|---|---|---|---|
| 4 | `formatSeconds()` duplicado en 3 Actions | Extraer a un trait `App\Modules\ReportingModule\Support\FormatDuration` o a `BaseReport` y reutilizar. | `Actions/ExportAhtDetailAction.php`, `Actions/ExportVolumeDetailAction.php`, `Actions/ExportVolumeSummaryAction.php`, nuevo `Support/FormatDuration.php` | 30 min |
| 5 | Repository monolítico (808 líneas) | Dividir en repositorios por dominio: `AbsenteeismReportRepository`, `VolumeReportRepository`, `AhtReportRepository`, `PerformanceReportRepository`. Cada uno con sus queries específicas. | `Repositories/EloquentReportDataRepository.php` → `Repositories/*.php` (4-5 archivos) | 2h |
| 6 | FetchReportDataAction grande (453 líneas) | Delegar cada método privado a su correspondiente repositorio de dominio (del punto 5). El Action solo orquesta. | `Actions/FetchReportDataAction.php` | 1h |
| 7 | Sin tests de datos del Repository | Agregar tests para cada método público del Repository usando `RefreshDatabase` + factories, verificando que las queries retornen DTOs correctos con datos seed. | `tests/Feature/Modules/ReportingModule/RepositoryDataTest.php` | 3h |
| 8 | Sin tests de exportación PDF/XLS | Agregar tests que llamen `ExportXxxAction::execute()` con filtros mock y verifiquen que la respuesta sea un `StreamedResponse` con Content-Type correcto. | `tests/Feature/Modules/ReportingModule/ExportActionsTest.php` | 2h |

### 🟢 Prioridad Baja (calidad y mantenibilidad)

| # | Hallazgo | Acción | Archivos afectados | Esfuerzo |
|---|---|---|---|---|
| 9 | Split de layouts PDF | Unificar: mover `app/Reports/BaseReport.php` → `app/Modules/ReportingModule/BaseReport.php` (o crear un alias). Centralizar templates PDF en `ReportingModule/Resources/Views/reports/layout.blade.php` y eliminar `resources/views/reports/layout.blade.php`. | `app/Reports/`, `ReportingModule/`, `EmployeePerformanceReport.php` | 1h |
| 10 | No usa Shared Contracts | Evaluar migración gradual de queries del Repository a usar `TelemetryRealtimeRepositoryInterface` y `AgentPerformanceRepositoryInterface` existentes en `app/Shared/Contracts/`. Priorizar las queries de volumen y AHT. | `Repositories/`, `Shared/Contracts/` | 2h |
| 11 | Agregar helper `formatSeconds()` faltante en vista `volume-summary` | La vista usa `gmdate()` directo en lugar de reutilizar `formatSeconds()`. Extraer a formateo consistente. | `Resources/Views/reports/volume-summary.blade.php` | 15 min |
| 12 | Reportes sin datos: mejorar empty state | Las vistas PDF muestran tabla vacía si no hay datos. Agregar mensaje "Sin datos para los filtros seleccionados." en los templates Blade de PDF. | `Resources/Views/reports/*.blade.php` (cada template) | 30 min |

### Orden de Ejecución Recomendado

```
Fase 1 (inmediata, < 1 día)
├── 🔴 1. Fix $userRole en layout PDF
├── 🔴 2. Fix RawAbsenteeism (mostrar todos)
└── 🔴 3. Fix N+1 en calculateSlaPercentage

Fase 2 (corto plazo, 2-3 días)
├── 🟡 4. Extraer formatSeconds() a helper compartido
├── 🟡 7. Tests de datos del Repository
└── 🟡 8. Tests de exportación PDF/XLS

Fase 3 (mediano plazo, 1 semana)
├── 🟡 5. Dividir Repository monolítico
├── 🟡 6. Refactor FetchReportDataAction
├── 🟢 9. Unificar layouts PDF
└── 🟢 11. Formateo consistente en vistas

Fase 4 (largo plazo, backlog)
├── 🟢 10. Migrar a Shared Contracts
└── 🟢 12. Empty states en PDF
```
