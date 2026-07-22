# Módulo de Reportería WFM — ReportingModule

> Documento de diseño del módulo de generación de reportes descargables (PDF/XLS).
> Versión 1.0 — Julio 2026

---

## 1. Resumen

Módulo **Supporting/Generic** (transaction script) para la generación de reportes operativos descargables en formato PDF y XLS. Consume datos de `ConnectModule`, `WfmModule`, `OperationsModule` y `PersonnelModule` a través de contratos compartidos y repositorios propios. No posee modelos propios ni emite eventos de dominio — es 100% read-only.

---

## 2. Justificación: ¿Módulo nuevo o extensión de OperationsModule?

| Criterio | OperationsModule (existente) | ReportingModule (nuevo) |
|---|---|---|
| Propósito | Dashboards en vivo, KPIs, adherencia en tiempo real | Documentos estáticos descargables (PDF/XLS) |
| Tamaño actual | 19 Livewire + 6 Actions + 3 Services | Comienza limpio, ~1,500 líneas estimadas |
| Consumo cross-module | Ya es grande y con acoplamiento a 5 módulos | Lo hace explícitamente como consumidor read-only |
| Ciclo de vida | Tiempo real, polling cada 5s/10s | Bajo demanda (click → descarga inmediata) |
| Formato de salida | HTML/Blade (pantalla) | PDF (dompdf) + XLS (HTML table) |

**Decisión:** Módulo nuevo. Un módulo de reportería es un dominio transversal diferente a las operaciones en vivo.

---

## 3. Estructura del Módulo

```
app/Modules/ReportingModule/
├── Actions/
│   ├── ExportRawAbsenteeismAction.php
│   ├── ExportExceptionSummaryAction.php
│   ├── ExportAhtDetailAction.php
│   ├── ExportAhtSummaryAction.php
│   ├── ExportVolumeDetailAction.php
│   ├── ExportVolumeSummaryAction.php
│   ├── GeneratePdfReportAction.php
│   └── GenerateXlsReportAction.php
├── DTOs/
│   ├── ReportFilterDTO.php
│   ├── AbsenteeismRowDTO.php
│   ├── ExceptionSummaryRowDTO.php
│   ├── AhtRowDTO.php
│   └── VolumeRowDTO.php
├── Enums/
│   └── ReportFormatEnum.php
├── Livewire/
│   ├── ReportGenerator.php
│   └── Forms/
│       └── ReportGeneratorForm.php
├── Policies/
│   └── ReportPolicy.php
├── Providers/
│   └── ModuleServiceProvider.php
├── Repositories/
│   └── EloquentReportDataRepository.php
├── Resources/Views/
│   ├── livewire/
│   │   └── report-generator.blade.php
│   └── reports/
│       ├── ausentismo-raw.blade.php
│       ├── ausentismo-exceptions.blade.php
│       ├── aht-detail.blade.php
│       └── volume-summary.blade.php
├── Routes/
│   └── web.php
```

---

## 4. Reportes y Mapa de Datos

### 4.1 Ausentismo — Crudo

Detalle línea por línea de cada período de ausencia registrado en el sistema.

**Origen de datos:**
- `ScheduleException` JOIN `AbsenceReasonCode` → ausencias planificadas (permisos, vacaciones, incapacidades)
- `AttendanceIncident` JOIN `IncidentType` → incidencias de asistencia detectadas (tardanzas, faltas)

**Columnas del reporte:**

| Columna | Origen | Transformación |
|---|---|---|
| Empleado | `ScheduleException.employee_id` → `Employee` | JOIN (nombre, número, equipo) |
| Fecha | `ScheduleException.start_at::date` | `DATE(start_at)` |
| Tipo de origen | — | `'schedule_exception'` o `'attendance_incident'` |
| Causa | `AbsenceReasonCode.name` o `IncidentType.name` | JOIN según origen |
| ¿Justificado? | `AbsenceReasonCode.is_excused` | `Bool` → `Sí` / `No` |
| ¿Día completo? | `ScheduleException.is_full_day` o horas cubiertas | `Bool` |
| Inicio | `ScheduleException.start_at` o `AttendanceIncident.start_time` | Timestamp o TIME |
| Fin | `ScheduleException.end_at` o `AttendanceIncident.end_time` | Timestamp o TIME |
| Minutos de ausencia | `EXTRACT(EPOCH FROM end_at - start_at) / 60` | Cálculo |
| Observaciones | `ScheduleException.remarks` o `AttendanceIncident.user_comment` | Texto |

**Filtros:** rango de fechas, equipo, empleado, tipo de causa (justificado/no), origen (planificado/incidencia)

---

### 4.2 Ausentismo — Resumen por Excepción

Agregación de ausencias agrupadas por causa.

**Columnas del reporte:**

| Columna | Origen |
|---|---|
| Causa de ausencia | `AbsenceReasonCode.name` + `short_code` |
| ¿Justificado? | `AbsenceReasonCode.is_excused` |
| Total ocurrencias | `COUNT(schedule_exceptions.id)` |
| Total minutos perdidos | `SUM(EXTRACT(EPOCH FROM end_at - start_at) / 60)` |
| Empleados afectados | `COUNT(DISTINCT employee_id)` |
| % sobre tiempo total programado | `SUM(minutos) / (empleados_del_periodo × días × jornada_base)` |
| Desglose por equipo | — |

---

### 4.3 AHT — Detallado

Average Handle Time desglosado por agente, cola y día.

**Origen de datos:**
- `AgentCallPerformance` (talk_time, hold_time, work_time, total_duration)
- `CallQueue` (aht_goal por cola)
- `Employee` (nombre, equipo)

**Columnas del reporte:**

| Columna | Origen |
|---|---|
| Agente | `AgentCallPerformance.employee_id` → `Employee` |
| Cola | `AgentCallPerformance.csq_name` |
| Fecha | `AgentCallPerformance.start_time::date` |
| Llamadas atendidas | `COUNT(*)` |
| Talk Time promedio | `AVG(talk_time)` |
| Work Time promedio | `AVG(work_time)` |
| Hold Time promedio | `AVG(hold_time)` |
| **AHT** | `AVG(talk_time + work_time)` |
| Objetivo AHT | `CallQueue.aht_goal` por cola |
| Desviación vs objetivo | `AHT - aht_goal` |
| AHT mínimo del período | `MIN(talk_time + work_time)` |
| AHT máximo del período | `MAX(talk_time + work_time)` |

**Filtros:** rango de fechas, cola, equipo, agente

---

### 4.4 Volumen — Métricas de Cola

Volumen de llamadas con métricas de servicio.

**Origen de datos:**
- `CallRecord` (ivr_started_at, queue_id, employee_id, contact_disposition, talk_time, work_time, queue_time)
- `CallQueue` (name, aht_goal)

**Columnas del reporte:**

| Columna | Origen / Fórmula |
|---|---|
| Cola | `CallQueue.name` |
| Fecha | `CallRecord.ivr_started_at::date` |
| Recibidos | `COUNT(*)` total de registros en el período |
| Atendidos | `COUNT(*)` WHERE `contact_disposition = Handled (2)` |
| Abandonados | `COUNT(*)` WHERE `contact_disposition IN (1, 4, 13)` |
| % Abandono | `(abandonados / recibidos) × 100` |
| **AHT** | `AVG(talk_time + work_time)` de los atendidos |
| ASA (Tiempo espera prom.) | `AVG(queue_time)` de los atendidos |
| Tiempo espera máx. | `MAX(queue_time)` |
| Tiempo espera mín. | `MIN(queue_time)` |
| Tiempo abandono prom. | `AVG(queue_time)` de los abandonados |
| SLA % (umbral configurable) | `COUNT(*) WHERE queue_time <= umbral / recibidos × 100` |

**Filtros:** rango de fechas, cola, intervalo de agregación (diario/semanal/mensual)

---

## 5. Flujo de Generación

```
Usuario
  │
  ├─ GET /reportes → ReportGenerator (Livewire)
  │   ├─ Selecciona tipo (ausentismo-crudo / ausentismo-excepciones /
  │   │                     aht-detallado / aht-resumen / volumen)
  │   ├─ Selecciona formato (PDF / XLS)
  │   ├─ Aplica filtros comunes (fechas) + específicos (equipo, cola, empleado)
  │   └─ Click "Generar Reporte"
  │
  ├─ ReportGenerator → $this->authorize('export', Report::class)
  │
  ├─ ReportGenerator → app(ExportXxxAction::class)->execute($filtros, $formato)
  │   │
  │   ├─ ExportXxxAction → EloquentReportDataRepository::queryXxx($filtros)
  │   │
  │   ├─ Si PDF: GeneratePdfReportAction::execute($data, $view, $title, $orientation)
  │   │               └─ Extiende BaseReport → stream() / download()
  │   │               └─ Renderiza vista Blade → dompdf
  │   │
  │   └─ Si XLS: GenerateXlsReportAction::execute($data, $headers, $filename)
  │                   └─ Response con Content-Type: application/vnd.ms-excel
  │                   └─ Renderiza vista Blade con tabla HTML
  │
  └─ Usuario descarga el archivo
```

### 5.1 PDF (vía dompdf)

Reutiliza `app/Reports/BaseReport` (ya existente en el proyecto):

```php
$report = new class($data, $view, $title) extends BaseReport {
    public function data(): array { return $this->data; }
    public function view(): View { return view($this->view, $this->data); }
};
return $report->download('reporte-ausentismo-202607.pdf');
```

### 5.2 XLS (vía HTML table)

Sigue el patrón existente en `EmployeeExportController`:

```php
$html = view('reporting::reports.xls-table', [
    'headers' => $headers,
    'rows' => $rows,
    'title' => $title,
])->render();

return response($html, 200, [
    'Content-Type' => 'application/vnd.ms-excel',
    'Content-Disposition' => 'attachment; filename="reporte.xls"',
]);
```

---

## 6. Contratos que Consume (Shared/Contracts)

| Interfaz | Métodos útiles para reportería |
|---|---|
| `TelemetryRealtimeRepositoryInterface` | `getCallVolumeByDateRange()`, `getQueuePerformanceReport()`, `getCallStatsForDate()` |
| `AgentPerformanceRepositoryInterface` | `getCallRecords()`, `getDailyMetric()` |
| `DashboardScheduleQueriesInterface` | `getExceptionsForRange()` |
| `EmployeeLookupRepositoryInterface` | `findActive()`, `findByTeam()` |
| `EmployeeRepositoryInterface` | Consultas de empleados por filtros |

Donde no exista contrato, el `EloquentReportDataRepository` realizará consultas directas a modelos Eloquent. Esto es aceptable para un módulo Supporting/Generic de solo lectura que consume datos aguas abajo en la cadena de dependencias.

---

## 7. Permisos

Un permiso único que se agrega al `RolesAndPermissionsSeeder` existente:

```
reports.export  →  Generar y descargar reportes
```

| Rol | Acceso |
|---|---|
| operator | ❌ |
| supervisor | ✅ Solo su equipo |
| coordinator | ✅ Todos |
| chief | ✅ Todos |
| wfm | ✅ Todos |
| director | ✅ Todos |
| admin | ✅ Todos (super-bypass) |

`ReportPolicy` implementará scoping por jerarquía de rol y equipo.

---

## 8. Orden de Carga en `config/modules.php`

Insertar después de `WfmModule`, antes de los módulos de soporte:

```php
// ─── Módulo WFM (núcleo del negocio) ───
App\Modules\WfmModule\Providers\ModuleServiceProvider::class,

// ─── Módulo de Reportería WFM ───
App\Modules\ReportingModule\Providers\ModuleServiceProvider::class,

// ─── Módulos de Soporte ───
App\Modules\HelpdeskModule\Providers\ModuleServiceProvider::class,
```

---

## 9. Dependencias Externas

| Paquete | Estado | Uso |
|---|---|---|
| `barryvdh/laravel-dompdf` | ✅ Ya instalado (^3.1) | Generación de PDF |
| `PhpOffice/PhpSpreadsheet` | ❌ No instalado | No requerido — XLS vía HTML table |

No se requieren nuevas dependencias. El XLS utiliza el patrón existente de respuesta HTTP con tabla HTML y `Content-Type: application/vnd.ms-excel`.

---

## 10. Estimación de Entregables

| Componente | Archivos | Líneas estimadas |
|---|---|---|
| Actions (8) | 8 | ~460 |
| DTOs (5) | 5 | ~120 |
| Enum (1) | 1 | ~20 |
| Livewire + Form (2) | 2 | ~220 |
| Policy (1) | 1 | ~40 |
| ModuleServiceProvider | 1 | ~60 |
| Repository | 1 | ~180 |
| Templates PDF/XLS (4) | 4 | ~320 |
| Vista Livewire | 1 | ~80 |
| Ruta web.php | 1 | ~15 |
| Migración de permiso (seeder) | 1 | ~15 |
| **Total** | **26** | **~1,530** |

---

## 11. Posibles Extensiones Futuras (Fuera de Alcance v1)

- Reportes programados (daily/weekly) con envío por email automático
- Tabla `generated_reports` con historial de descargas
- Dashboard de reportes (ver históricos, reprogramar)
- Integración con Webex para envío automático a equipos
- Métricas de calidad (QA score por agente) como dimensión adicional
