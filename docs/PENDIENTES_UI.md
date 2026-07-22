# Plan de Implementación — Unificaciones de Vista

> Documento de trabajo para la estandarización visual y funcional del frontend.
> Basado en el análisis de `manage-content.blade.php` y secciones detectadas sin unificación.

---

## 1. GLOBAL

### 1.1 Estandarizar ApexCharts

**Estado actual:** ApexCharts `^5.16.0` instalado y componente compartido `<x-apex-chart>` disponible. Ya usado en AgentPerformanceDashboard, Widgets (VolumeComparison, StateDistribution), ReportGenerator y MyDay. Sin embargo, el Dashboard principal de OperationsModule usa SVG inline artesanal para gráficos de cobertura, donut y sparklines.

**Objetivo:** Reemplazar todo SVG inline por el componente `<x-apex-chart>`.

| Vista actual | SVG inline | Reemplazo |
|---|---|---|
| `dashboard.blade.php` — Cobertura (líneas) | `<path>`, `<line>`, `<text>` | `<x-apex-chart type="area">` |
| `dashboard.blade.php` — Distribución (donut) | `<circle>` con `stroke-dasharray` | `<x-apex-chart type="donut">` |
| `dashboard.blade.php` — Sparkline semanal | `<polyline>` | `<x-apex-chart type="line">` con sparkline |

**Archivos afectados:**
- `app/Modules/OperationsModule/Resources/Views/livewire/dashboard.blade.php`
- `app/Modules/OperationsModule/Livewire/Dashboard.php` (pasar config desde PHP)

**Criterios de aceptación:**
- Dashboard renderiza sin SVG inline
- Tooltips, leyendas y animaciones funcionan
- Responsive mantiene legibilidad

### 1.2 Webex — Notificaciones a involucrados

**Estado actual:** Infraestructura completa (`WebexService`, `WebexChannel`, `BaseNotification` con canal webex condicional, `NotificationDTO`, Toast). No está cableada a los flujos de negocio.

**Objetivo:** Emitir notificaciones Webex + broadcast (toast) en los eventos clave del sistema.

| Evento | Involucrados | Canal |
|---|---|---|
| Leave request creado | Supervisor del solicitante | Webex + broadcast |
| Leave request aprobado/rechazado | Solicitante | Webex + broadcast |
| Shift swap solicitado | Supervisor + par | Webex + broadcast |
| Shift swap aprobado/rechazado | Solicitante | Webex + broadcast |
| Incidencia de asistencia registrada | Empleado afectado | broadcast |
| Alerta de adherencia (< umbral) | Supervisor del agente | Webex + broadcast |

**Arquitectura:** Cada evento de dominio (`LeaveRequestCreated`, `ShiftSwapRequested`, etc.) debe tener un Listener que:
1. Construya un `NotificationDTO`
2. Dispare una notificación Laravel usando `BaseNotification`
3. El canal `broadcast` enviará al frontend vía Laravel Echo + Reverb (Toast)

**Archivos a crear:**
- `app/Modules/WfmModule/Listeners/SendLeaveRequestNotification.php`
- `app/Modules/WfmModule/Listeners/SendShiftSwapNotification.php`
- `app/Modules/OperationsModule/Listeners/SendAdherenceAlertNotification.php`

**Archivos a modificar:**
- `app/Modules/WfmModule/Providers/ModuleServiceProvider.php` (registrar listeners)

---

## 2. Dashboard (OperationsModule)

### 2.1 Asistencia y ausencia por Operador I / II

**Clasificación:** `Employee.position_id` → `Position` tabla:

| ID | Nombre |
|---|---|
| 1 | Operador Asist. Serv. Aseg. I |
| 2 | Operador Asist. Serv. Aseg. II |
| 3+ | Otros roles (analistas, jefes, coordinadores) |

**Objetivo:** Calcular asistencia/ausencia agrupando empleados por `position_id IN (1, 2)` y cruzando con `agent_realtime_states` (Cisco).

**Lógica:**
- `Operador I`: `Employee.where(position_id: 1).where(is_active: true)`
- `Operador II`: `Employee.where(position_id: 2).where(is_active: true)`
- Asistencia: conteo de empleados con estado `TALKING` o `WORK_READY` en `agent_realtime_states`
- Ausencia: empleados activos sin estado activo en `agent_realtime_states`

**Archivos afectados:**
- `app/Modules/OperationsModule/Livewire/Dashboard.php`
- `app/Modules/OperationsModule/Resources/Views/livewire/dashboard.blade.php`

### 2.2 Cobertura por equipo

**Objetivo:** Mostrar cobertura por `Team`, cruzando:
- `Team` → `Employee` (miembros activos, `position_id IN (1,2)`)
- `Employee` → `agent_realtime_states` (estado actual desde Cisco)

Indicador: `(agentes en estado productivo / total agentes del equipo) * 100`

### 2.3 Colas desde Cisco

**Objetivo:** Mostrar solo CSQs con actividad en las últimas 24h.

**Data source:** `call_records` (CUIC) agrupado por `queue_id` → `CallQueue.name`. Si un CSQ no tiene registros en el día, no se muestra.

**Estado actual:** `QueueStatsWidget` ya muestra tabla de CSQs pero no filtra por actividad.

### 2.4 Indicadores del día

**Objetivo:** Acumular métricas del día actual desde `call_records`, `agent_call_performance` y `agent_state_transitions`.

**Similitud con:** `HeroKpiWidget` — extender para incluir datos acumulados diarios de CUIC.

---

## 3. Mi Trabajo — Leave Request (WfmModule)

### 3.1 Periodo cuatrimestral

**Cambio:** Pasar de cálculo trimestral (3 meses) a **cuatrimestral (4 meses)**.

**Periodos sugeridos:**
| Cuatrimestre | Meses |
|---|---|
| 1 | Enero — Abril |
| 2 | Mayo — Agosto |
| 3 | Septiembre — Diciembre |

**Archivos afectados:**
- `app/Modules/WfmModule/Actions/LeaveRequestAction.php` (o servicio donde se calcule el periodo)
- Verificar que el cálculo de `leave_balances` (minutos disponibles) use 4 meses.

### 3.2 Saldo de horas disponible

**Objetivo:** Mostrar al solicitante, durante la creación de la solicitud, el saldo restante:
- Horas disponibles en el periodo cuatrimestral
- Horas ya tomadas (suma de leave requests aprobadas en el periodo)
- Saldo restante = disponibles - tomadas

**Implementación:**
- Propiedad computada en el Livewire `LeaveRequestForm`
- Consulta: `leave_requests.where(employee_id, auth)->where(status, approved)->whereBetween(created_at, periodo_actual)->sum(minutos)`

### 3.3 Alerta visual al superar disponibilidad

**Objetivo:** Si la solicitud supera el saldo restante, mostrar alerta en rojo antes del submit.

**Implementación:**
- Validación en `$this->validate()` del formulario Livewire
- Mensaje: "La solicitud excede el saldo disponible de X horas."
- Opcional: prevenir el submit si excede.

### 3.4 Notificaciones toast vía socket

**Objetivo:** El solicitante recibe un toast en tiempo real cuando su solicitud es aprobada/rechazada.

**Estado actual:** `Toast.php` componente Livewire + evento `broadcastNotification` ya existen. La infraestructura Echo + Reverb está configurada.

**Implementación:**
- `LeaveRequestApproved` / `LeaveRequestRejected` disparan `broadcastNotification`
- El Toast lo captura en el frontend sin recargar

---

## 4. Mi Equipo — Exportar Excel (WfmModule)

### 4.1 Exportar horario semanal e incidencias

**Objetivo:** Botón "Exportar Excel" en la vista `my-team.blade.php` que descargue:
- Horario planificado del equipo (empleado, día, hora entrada, hora salida, almuerzo)
- Incidencias de asistencia del equipo en el periodo visible

**Referencia:** Seguir el patrón de `app/Modules/OperationsModule/Exports/` (Laravel Excel / `maatwebsite/laravel-excel` verificar si está instalado).

**Archivos a crear:**
- `app/Modules/WfmModule/Exports/TeamScheduleExport.php`
- `app/Modules/WfmModule/Exports/TeamIncidentsExport.php`

**Archivos a modificar:**
- `app/Modules/WfmModule/Livewire/MyTeam.php` (método `exportExcel()`)

---

## 5. Planificación Semanal (WfmModule)

### 5.1 Copiar día a toda la semana

**UX:** Botón "Copiar [día] a [días destino]" al lado de cada fila. Al hacer clic, abre un selector de días destino (checkboxes: Lun, Mar, Mié, Jue, Vie).

**Implementación:** Acción Livewire que copia los valores de `entrada`, `salida`, `inicio_almuerzo`, `fin_almuerzo`, `inicio_descanso` del día origen a los días seleccionados.

### 5.2 Barra de totales semanales

**Objetivo:** Barra sticky en la parte inferior de la tabla que muestre:
- Total de horas planificadas en la semana
- Promedio diario
Actualizarse en tiempo real al cambiar selectores.

**Implementación:** Propiedad computada en Livewire `weekly-planning` que suma horas de todos los días.

### 5.3 Diseño compacto / responsive

**Objetivo:** En pantallas < 1024px, transformar la tabla horizontal en tarjetas por día.

**Archivo:** `app/Modules/WfmModule/Resources/Views/livewire/weekly-planning.blade.php`

### 5.4 Validación de solapes y almuerzos

**Reglas de negocio:**
- Almuerzo debe estar al menos 2h después del inicio del turno
- Descanso debe estar dentro de la ventana laboral (entre inicio y salida)
- Almuerzo y descanso no deben solaparse
- Duración mínima de almuerzo: 30 min

**Implementación:** Regla de validación personalizada en el Action de guardado y validación visual en frontend (Alpine.js o Livewire `$rules`).

### 5.5 Sticky footer — botón guardar

**Objetivo:** El botón "Guardar Horario Semanal" permanece visible al hacer scroll.

**Implementación:** Clase `sticky bottom-0 bg-white dark:bg-zinc-900 p-4 border-t` en el footer del formulario.

---

## 6. Operaciones — Limpieza de menús

### 6.1 Post-módulo reportes

**Dependencia:** Este ítem depende de la finalización del módulo de Reportes (`ReportingModule`).

**Objetivo:** Una vez que ReportingModule esté operativo, revisar qué rutas/menús de `operations` se solapan con los reportes y consolidarlos.

**Criterio:** No eliminar nada hasta tener ReportingModule en producción.

---

## Apéndice A: Confirmación de clasificación Operador I / II

La clasificación está definida en `Employee.position_id` → `Position`:

| ID | Nombre (Operador) |
|---|---|
| 1 | Operador Asist. Serv. Aseg. I |
| 2 | Operador Asist. Serv. Aseg. II |

**Uso en dashboard:**
```php
// Ej: empleados operativos
$operatorIds = [1, 2]; // position_id
Employee::whereIn('position_id', $operatorIds)->where('is_active', true)->count();
```

---

## Apéndice B: Priorización sugerida

| Prioridad | Ítem | Esfuerzo | Dependencia |
|---|---|---|---|
| P0 | 3.1 Periodo cuatrimestral | Bajo | Ninguna |
| P0 | 3.2 Saldo de horas | Medio | 3.1 |
| P0 | 3.3 Alerta visual | Bajo | 3.2 |
| P1 | 5 — Planificación semanal UX | Medio | Ninguna |
| P1 | 2.1 — Dashboard Operador I/II | Medio | Ninguna |
| P1 | 4 — Exportar Excel Mi Equipo | Bajo | Ninguna |
| P2 | 1.2 — Webex notificaciones | Medio | Ninguna |
| P2 | 1.1 — ApexCharts dashboard | Bajo | Ninguna |
| P2 | 2.2/2.3/2.4 — Dashboard indicadores | Alto | 2.1 |
| P2 | 3.4 — Toast vía socket | Bajo | 1.2 |
| P3 | 6 — Limpieza menús Operaciones | Bajo | ReportingModule |

---

## Apéndice C: Análisis técnico por ítem

> Validación contra estado real del códigobase al 2026-07-22.

### 1.1 — ApexCharts en Dashboard

| Atributo | Detalle |
|---|---|
| **Factibilidad** | ✅ Alta — `<x-apex-chart>` ya existe y está integrado. |
| **Qué reemplazar** | SVG inline en `dashboard.blade.php`: cobertura (`<path>`, `<line>`, `<text>`), donut (`<circle>` con `stroke-dasharray`), sparkline semanal (`<polyline>`). |
| **Config desde PHP** | Dashboard.php ya tiene `$coverageSeries`, `$distribution`, `$trends` — mapear a opciones de ApexCharts. |
| **Esfuerzo** | Bajo. |
| **Riesgo** | Tooltips, animaciones y responsive de ApexCharts son superiores a SVG inline — riesgo bajo. |

### 1.2 — Webex Notificaciones

| Atributo | Detalle |
|---|---|
| **Factibilidad** | ✅ Alta — infraestructura completa: `WebexService`, `WebexChannel`, `BaseNotification` (con canal webex condicional vía `shouldSendWebex`), `NotificationDTO`, `Toast` Echo+Reverb. |
| **Qué falta** | Listeners para eventos de dominio (`LeaveRequestCreated`, `LeaveRequestApproved`, `ShiftSwapRequested`, `AdherenceAlert`). |
| **Archivos a crear** | `app/Modules/WfmModule/Listeners/SendLeaveRequestNotification.php`, `SendShiftSwapNotification.php`, `app/Modules/OperationsModule/Listeners/SendAdherenceAlertNotification.php`. |
| **Esfuerzo** | Medio (3 listeners + registro en ModuleServiceProvider). |

### 2.1 — Dashboard Operador I/II

| Atributo | Detalle |
|---|---|
| **Factibilidad** | ✅ Alta — `Employee.position_id` → `Position` con IDs 1 (Operador Asist. Serv. Aseg. I) y 2 (Operador Asist. Serv. Aseg. II). |
| **Data source** | `agent_realtime_states` consultado vía `TelemetryRealtimeRepositoryInterface::getRealtimeStates()`. |
| **Lógica** | `Employee::whereIn('position_id', [1,2])->where('is_active', true)` y cruzar con estados `TALKING`/`WORK_READY` para asistencia. |
| **Esfuerzo** | Medio. |

### 2.2 — Cobertura por equipo

| Atributo | Detalle |
|---|---|
| **Factibilidad** | ⚠️ Parcial — Dashboard.php ya calcula `$teams` con `score` (basado en asignaciones vs total), pero no cruza con `agent_realtime_states`. |
| **Qué falta** | Consultar `agent_realtime_states` por `team_id` para calcular cobertura real conectados/activos. |
| **Esfuerzo** | Medio. |

### 2.3 — Colas desde Cisco (activas)

| Atributo | Detalle |
|---|---|
| **Factibilidad** | ℹ️ `QueueStatsWidget` muestra CSQs pero sin filtro de actividad. |
| **Data source** | `call_records` (CUIC) agrupado por `queue_id` → `CallQueue.name`. |
| **Filtro** | `WHERE created_at >= now() - interval '24 hours'`. |
| **Esfuerzo** | Bajo. |

### 2.4 — Indicadores del día

| Atributo | Detalle |
|---|---|
| **Factibilidad** | ⚠️ Depende de `HeroKpiWidget` — verificar si existe como componente real o es conceptual. |
| **Data source** | `call_records`, `agent_call_performance`, `agent_state_transitions`. |
| **Esfuerzo** | Alto (depende de 2.1). |

### 3.1 — Periodo cuatrimestral

| Atributo | Detalle |
|---|---|
| **Factibilidad** | ✅ Baja complejidad — cambiar constante de 3→4 meses en el cálculo de periodo. |
| **Periodos** | Ene–Abr, May–Ago, Sep–Dic. |
| **Ubicación** | Buscar en `app/Modules/WfmModule/` donde se calcule `leave_balances`. Posibles archivos: `LeaveRequestAction`, servicios de balance. |
| **Esfuerzo** | Bajo. |

### 3.2 — Saldo de horas disponible

| Atributo | Detalle |
|---|---|
| **Factibilidad** | ⚠️ Medio — depende de 3.1. |
| **Dónde** | `LeaveRequestForm` Livewire existe en `WfmModule/Livewire/Forms/`. Añadir propiedad computada `$availableBalance`. |
| **Consulta** | `leave_requests.where(employee_id, auth)->where(status, approved)->whereBetween(start_time, periodo)->sum(minutos)`. |
| **Esfuerzo** | Medio. |

### 3.3 — Alerta visual

| Atributo | Detalle |
|---|---|
| **Factibilidad** | ✅ Bajo — regla de validación personalizada en `LeaveRequestForm`. |
| **Mensaje** | "La solicitud excede el saldo disponible de X horas." |
| **Esfuerzo** | Bajo. |

### 3.4 — Toast vía socket

| Atributo | Detalle |
|---|---|
| **Factibilidad** | ✅ Infraestructura Echo+Reverb+Toast funcionando. Solo disparar evento `broadcastNotification` desde los listeners de 1.2. |
| **Esfuerzo** | Bajo (acoplado a 1.2). |

### 4 — Exportar Excel Mi Equipo

| Atributo | Detalle |
|---|---|
| **⚠️ Riesgo** | **`maatwebsite/laravel-excel` NO está instalado.** El directorio `app/Modules/OperationsModule/Exports/` (referenciado en el documento) **no existe.** |
| **Patrón actual** | El proyecto exporta CSV vía Actions + `response()->streamDownload()`. Ejemplos: `ExportEmployeesAction::toCsv()`, `ExportAuditLogsAction` con `fputcsv`. |
| **Alternativas** | 1) Instalar `openspout/openspout` (liviano, sin dependencias pesadas) y crear wrapper. 2) Instalar `spatie/simple-excel`. 3) Seguir con CSV nativo como el resto del proyecto. |
| **Archivos a crear** | `app/Modules/WfmModule/Exports/TeamScheduleExport.php`, `TeamIncidentsExport.php`. |
| **Esfuerzo** | Bajo–Medio (depende de la alternativa elegida). |

### 5 — Planificación Semanal UX

| Atributo | Detalle |
|---|---|
| **Factibilidad** | ✅ `WeeklyPlanningTeams` Livewire y `WeeklyScheduleAssignment` model existen. |
| **Sub-ítems** | Copiar día (5.1), barra totales sticky (5.2), responsive (5.3), validaciones (5.4), sticky footer guardar (5.5). Independientes entre sí. |
| **Esfuerzo** | Medio (suma de 5 sub-ítems, cada uno bajo). |

### 6 — Limpieza menús Operaciones

| Atributo | Detalle |
|---|---|
| **Factibilidad** | ⏳ Depende de ReportingModule en producción. |
| **⚠️ Duplicación** | `ModuleServiceProvider` de OperationsModule registra `Livewire::component('operations.reporting-index', ReportingFrameworkIndex::class)` — compite con el nuevo `ReportingModule`. |
| **Criterio** | No eliminar hasta tener ReportingModule estable en producción. |
| **Esfuerzo** | Bajo (una vez lista la dependencia). |

---

## Apéndice D: Observaciones generales

| # | Observación |
|---|---|
| 1 | **Sin cobertura de tests** para Dashboard, MyTeam, LeaveRequestForm, WeeklyPlanningTeams. Riesgo de regresión en cualquier cambio. |
| 2 | El documento referencias `app/Modules/OperationsModule/Exports/` que **no existe**. Corregir ruta o eliminar referencia. |
| 3 | Item 4 no puede seguir el patrón indicado porque `maatwebsite/laravel-excel` no está instalado ni el directorio de Exports existe. |
| 4 | Ya existe duplicación entre `operations.reporting-index` (OperationsModule legacy) y el nuevo `ReportingModule`. Considerar deprecación temprana. |
| 5 | Distribución de esfuerzo consistente con la priorización sugerida (P0 = bajo/medio, P2/P3 = medio/alto). |
