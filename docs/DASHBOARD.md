# Dashboard Operativo — Estructura y KPIs

> **Versión implementada:** Basada en componentes Livewire lazy-loaded en `OperationsModule`.
> **Layout:** Dashboard definido en `app/Modules/OperationsModule/Resources/Views/livewire/dashboard.blade.php`.
> **Widgets:** 6 componentes independientes en `app/Modules/OperationsModule/Livewire/Widgets/`.
> **Motor de métricas:** `app/Modules/OperationsModule/Services/PerformanceService.php`.

---

## Estructura General

El dashboard se organiza en una sola página con **6 widgets lazy-loaded**. Cada widget se carga de forma independiente (skeleton/placeholder mientras carga), lo que significa que si uno falla, los demás siguen funcionando. Soporta dos modos:

- **Tiempo real (hoy):** Polling cada 15 segundos, datos en vivo desde `AgentRealtimeState` + `csq_realtime_stats`.
- **Histórico (fecha pasada):** Sin polling, datos desde `call_records`, `agent_state_transitions` y tablas históricas.

### Layout

```
┌────────────────────────────────────────────────────────────────┐
│  Cabecera: Título + Selector de fecha + Indicador Live/Hist.   │
├────────────────────────────────────────────────────────────────┤
│  Hero KPIs (6 tarjetas): Cobertura · Adherencia · Ocupación   │
│                  Nivel Servicio · Ausentismo · Shrinkage       │
├───────────────────────────────┬────────────────────────────────┤
│  Estado de Colas (Tabla)      │  Distribución de Estados       │
│  Cada fila = 1 cola ACD      │  (Gráfico donut ApexCharts)    │
│  Columnas: espera, hablando,  │  Ready / Talking / AUX / Off   │
│  recibidas, atendidas,        │                                │
│  abandonadas, SLA, t.max      │                                │
├───────────────────────────────┴────────────────────────────────┤
│  Comparativo de Volumen (Gráfico barras agrupadas ApexCharts)  │
│  Semana actual vs. anterior — Atendidas / Abandonadas por día  │
├───────────────────────────────┬────────────────────────────────┤
│  Alertas Críticas             │  Últimas Incidencias de        │
│  · Solicitudes pendientes     │  Asistencia (Tabla)            │
│  · Colas con SLA crítico      │  Agente, Tipo, Hora           │
└───────────────────────────────┴────────────────────────────────┘
```

### Tema Visual

- Esquema **oscuro (dark mode)** predeterminado — alto contraste para monitoreo prolongado en salas de operaciones.
- Sistema de **tarjetas (cards)** con bordes redondeados y sombras sutiles.
- Indicadores de estado: semáforo (verde/ámbar/rojo) en Hero KPIs, colas y SLA.
- Barra de color inferior en cada Hero KPI: indica estado del indicador sin saturar visualmente.
- Badges y colores diferenciales en tabla de colas (azul=recibidas, verde=atendidas, rojo=abandonadas).

---

## Widget 1: Hero KPIs (6 tarjetas)

**Componente:** `HeroKpiWidget.php` → `PerformanceService::getGlobalHeroKpis()`

| KPI | Qué mide | Umbrales | Cálculo (Realtime) |
|---|---|---|---|
| **Cobertura** | % de agentes programados que están conectados | 🔴 <90% · 🟡 90-95% · 🟢 ≥95% | Conectados / Programados × 100 |
| **Adherencia** | % de tiempo que los agentes cumplen el estado planificado | 🔴 <85% · 🟡 85-92% · 🟢 ≥92% | `AdherenceAction::executeBatch()` |
| **Ocupación** | % de tiempo productivo vs. disponible (Ready) | 🔴 >90% · 🟡 85-90% · 🟢 ≤85% | Talking+Work+Reserved / (Talking+Work+Reserved+Ready) |
| **Nivel de Servicio** | % de llamadas atendidas dentro del SLA | 🔴 <80% · 🟡 80-90% · 🟢 ≥90% | Promedio `csq_realtime_stats.service_level_long_term` |
| **Ausentismo** | % de agentes programados que no se conectaron | 🔴 >5% · 🟢 ≤5% | (Programados - Conectados) / Programados × 100 |
| **Shrinkage** | % de tiempo no productivo sobre el total pagado | Neutro (solo informativo) | `calculateShrinkage()` |

Cada tarjeta incluye:
- **Delta vs. día anterior** — flecha verde (mejoró) / roja (empeoró) con valor porcentual.
- **Ícono** distintivo (users, clock, chart-bar, phone, user-minus, scissors).

### Diferencias con la propuesta original

| Propuesto en DASHBOARD.md | Implementado | Decisión |
|---|---|---|
| Total Calls Handled Today | ❌ No implementado | Se muestra en QueueStats por cola, no como global. Agregar como KPI opcional si hay demanda. |
| Average Call Duration (ACD) | ❌ No implementado | Disponible en `QueuePerformanceReport`. Agregar al dashboard si se requiere visibilidad ejecutiva. |
| Abandoned Call Rate | ❌ No implementado | Se infiere de la tabla QueueStats. Un KPI global de abandono sería útil. |
| Agent Utilization Rate | ✅ Como "Ocupación" con fórmula diferente | Se mantiene con la fórmula actual (productivo / productivo+ready). |
| Customer Experience (Donut) | ❌ No implementado | La dona actual muestra estados de agente. Una dona de satisfacción requiere datos de encuestas post-llamada. |
| Queue (Waiting/LWT) | ⚠️ Parcial | Los datos existen en QueueStats, pero no como widget de alerta visual dedicado. |

---

## Widget 2: Estado de Colas

**Componente:** `QueueStatsWidget.php`

Tabla en tiempo real del rendimiento de todas las colas ACD:

| Columna | Descripción | Origen (Realtime) |
|---|---|---|
| **Cola** | Nombre de la cola Cisco | `csq_realtime_stats.csq_name` |
| **Espera** | Llamadas esperando (con badge rojo/ámbar si > umbral) | `csq_realtime_stats.calls_waiting` |
| **Hablando** | Agentes en TALKING en esa cola | `AgentRealtimeState` agrupado por `queue_name` |
| **Recibidas** | Llamadas entrantes hoy | `csq_realtime_stats.total_calls_since_midnight` |
| **Atendidas** | Llamadas atendidas hoy | `csq_realtime_stats.calls_handled_since_midnight` |
| **Abandonadas** | Llamadas abandonadas hoy | Diferencia: Recibidas - Atendidas (o campo explícito) |
| **T. Máx. Espera** | Tiempo de la llamada más antigua en cola | `csq_realtime_stats.longest_call_in_queue` en mm:ss |
| **SL %** | Nivel de servicio (badge rojo/ámbar/verde) | `csq_realtime_stats.service_level_long_term` |

- En modo **histórico**: los datos provienen de `call_records`, sin métricas en tiempo real (waiting=0, talking=0, lwt=0).
- Agrega fila "LLAMADAS DIRECTAS / SALIENTES" si hay agentes en TALKING sin cola asociada.
- Orden descendente por llamadas en espera (lo más urgente primero).

---

## Widget 3: Distribución de Estados

**Componente:** `StateDistributionWidget.php`

Gráfico de dona (ApexCharts) con la distribución actual de los agentes operativos:

| Estado | Color | Descripción |
|---|---|---|
| **Ready** | 🟢 Verde | Agentes disponibles para recibir llamadas |
| **Talking** | 🔵 Azul | Agentes en llamada activa |
| **AUX** | 🟡 Ámbar | Agentes en pausa (NOT_READY + WORK) |
| **Offline** | ⚪ Gris | Agentes desconectados (LOGOUT + OFFLINE) |

- Contabiliza solo agentes en posiciones operativas (IDs: 1=Operador I, 2=Operador II, 5=Supervisor, 11=Coordinador, 13=Agente Mixto).
- Incluye total de agentes en el centro de la dona.
- En modo histórico: obtiene el último estado conocido de `agent_state_transitions` para cada empleado.

---

## Widget 4: Comparativo de Volumen

**Componente:** `VolumeComparisonWidget.php`

Gráfico de barras agrupadas (ApexCharts) comparando semana actual vs. anterior:

| Serie | Tipo | Descripción |
|---|---|---|
| Atendidas (S1) | 🟢 Verde sólido | Llamadas atendidas por día de la semana actual |
| Abandonadas (S1) | 🔴 Rojo sólido | Llamadas abandonadas por día de la semana actual |
| Atendidas (S2) | 🟢 Verde claro | Llamadas atendidas por día de la semana anterior |
| Abandonadas (S2) | 🔴 Rojo claro | Llamadas abandonadas por día de la semana anterior |

- Eje X: Lun, Mar, Mié, Jue, Vie (días laborales).
- Tooltip compartido (hover sobre una barra muestra las 4 series).
- Incluye leyenda con etiquetas de semana (formato `dd/MMM`).
- Nota: este widget **no usa fecha seleccionada** — siempre muestra la semana actual y anterior.

---

## Widget 5: Alertas Críticas

**Componente:** `CriticalAlertsWidget.php`

Panel de alertas que se activan cuando hay condiciones que requieren atención:

| Alerta | Condición | Visualización |
|---|---|---|
| **Pendientes de Aprobación** | Hay leave requests o shift swaps en estado `pending` | Bloque azul con icono clock + conteo |
| **Colas con SLA Crítico** | Alguna cola tiene `status === 'danger'` (>5 esperando) | Bloque rojo con icono exclamation-triangle |
| **Sin alertas** | No hay pendientes ni colas en peligro | Icono check-circle verde + mensaje |

Este widget recibe `queueStats` desde el padre (Dashboard) para evaluar SLA crítico sin duplicar consultas.

---

## Widget 6: Últimas Incidencias de Asistencia

**Componente:** `RecentIncidentsWidget.php`

Tabla de las 5 incidencias de asistencia más recientes:

| Columna | Descripción |
|---|---|
| **Agente** | Nombre completo del empleado |
| **Tipo** | Badge con el nombre del tipo de incidencia (ej: "Tardanza", "Ausencia") |
| **Hora** | Tiempo relativo (hace X minutos/horas) |

- Datos desde `AttendanceIncident` con eager loading de `employee` y `type`.
- Orden descendente por `created_at`, limitado a 5 registros.
- Si no hay incidencias, muestra mensaje "No hay incidencias recientes".

---

## KPIs de la propuesta original NO implementados — Evaluación

| KPI Propuesto | ¿Agregar? | Justificación |
|---|---|---|
| **Total Calls Handled Today** | ❌ No prioritario | Ya visible por cola en QueueStats. Agregar como total acumulado en tabla si se requiere. |
| **Average Call Duration (ACD)** | ❌ No prioritario | Disponible en `QueuePerformanceReport`. Tiene sentido si se agrega un Hero KPI de "Eficiencia". |
| **Abandoned Call Rate** | ⚠️ Opcional | Fácil de calcular (abandonadas/recibidas). Agregar como séptimo Hero KPI si la operación lo solicita. |
| **Hourly Call Volume** | ⚠️ Opcional | El widget actual es semanal. Un gráfico horario intra-día complementaría al Realtime Monitoring. |
| **Customer Experience (Donut)** | ❌ Requiere encuestas | Necesita datos de encuestas post-llamada (no implementados en el sistema actual). |
| **FCR, Hold Time, Transfer Rate, Resolution Rate** | ❌ Requiere datos CTI | Son métricas de calidad que requieren datos de call records no disponibles actualmente. |
| **Most Calls Resolved (Ranking)** | ⚠️ Futuro | Tabla de productividad individual. Tiene sentido como widget opcional para supervisores. |
| **Agents Availability (en vivo)** | ✅ Parcial | Ya existe en StateDistribution donut y RealtimeMonitoring. Podría reforzarse con lista nominal. |
| **Queue Block (Waiting/LWT)** | ✅ Ya incluido | Datos en QueueStats. Podría tener un widget de alerta dedicado si hay colas con >10 esperando. |

---

## Resumen de decisiones de diseño

| Aspecto | Propuesta original | Implementación actual |
|---|---|---|
| KPIs globales | Volumen, ACD, Abandono, Utilización | Cobertura, Adherencia, Ocupación, SLA, Ausentismo, Shrinkage |
| Gráfico central | Barras horarias + Dona satisfacción | Barras semanales comparativas + Dona estados agente |
| Panel de alertas | Queue (Waiting + LWT) | Pendientes aprobación + Colas SLA crítico |
| Tabla de rendimiento | FCR, Hold, SLA, ASA, Transfer, Resolution | Estado de colas ACD (espera, hablando, atendidas, SLA) |
| Ranking agentes | Most Calls Resolved + Availability | Últimas incidencias de asistencia |

### Por qué los KPIs son diferentes

La propuesta original describe un dashboard genérico de call center orientado a **calidad y experiencia del cliente**. La implementación actual responde a las necesidades específicas de la **CSS Panamá**:

1. **Cobertura y Adherencia** son las métricas críticas para un centro que opera con turnos planificados (WFM). No son métricas genéricas de call center.
2. **Ausentismo y Shrinkage** son prioridad en un entorno con alta rotación y gestión de turnos.
3. Los datos de **satisfacción del cliente** (encuestas, FCR) requieren integraciones adicionales no disponibles.
4. Las **métricas por cola (QueueStats)** reemplazan a las métricas globales porque la operación de CSS tiene múltiples colas con comportamientos muy distintos.
5. El **comparativo semanal** es más útil que el volumen horario para la planificación de personal.

### Recomendaciones futuras

1. Agregar **Abandoned Call Rate** como 7mo Hero KPI cuando los datos de `call_records` estén maduros.
2. Reemplazar/ complementar la dona de estados con una **dona de satisfacción** si se implementan encuestas post-llamada.
3. Agregar un **widget de hourly volume** (intra-day) para complementar el comparativo semanal.
4. Evaluar si el ranking de agentes por llamadas resueltas agrega valor sobre el PerformanceScorecard existente.

---

## 🚀 Optimización e Integración con Antigravity 2.0 (Mejoras Sugeridas)

Para alinear el Dashboard Operativo con los estándares de rendimiento, resiliencia y el stack tecnológico actual de **Antigravity 2.0**, se sugieren las siguientes optimizaciones de arquitectura:

### 1. Resiliencia ante Caídas del CTI (Cisco UCCX)
*   **Problema:** Si la API de Cisco UCCX o la base de datos intermedia se desconecta, las consultas de tiempo real a `csq_realtime_stats` pueden bloquear el hilo de ejecución (I/O Block) y degradar o colapsar el Dashboard.
*   **Solución:** Implementar un indicador visual del estado del CTI (CTI Link Status: `Online` / `Offline`) en la cabecera del Dashboard. El backend debe ejecutar las consultas en tiempo real dentro de un bloque `try/catch` con un timeout máximo de 2 segundos, haciendo fallback inmediato a los últimos datos almacenados en caché si la conexión falla.

### 2. Cacheo Eficiente de KPIs con Redis
*   **Problema:** El refresco por polling de 15 segundos de métricas pesadas (como la adherencia acumulada en `HeroKpiWidget`) genera una carga innecesaria de consultas de agregación en PostgreSQL.
*   **Solución:** Cachear los Hero KPIs históricos y de baja volatilidad (como el *Shrinkage* o *Ausentismo*) en Redis con un TTL de 2 minutos. Solo las métricas críticas del CTI (llamadas en espera, SLA por cola) se deben consultar directo en cada ciclo de 15 segundos.

### 3. Alertas de Inconsistencia de Horarios (ScheduleValidationService)
*   **Problema:** El dashboard avisa de alertas de cola pero no previene desajustes de planificación hasta que el agente ya está en desadherencia.
*   **Solución:** Integrar el nuevo `ScheduleValidationService` en `CriticalAlertsWidget` para escanear en segundo plano si existen solapamientos de turnos o excepciones mal configuradas publicadas para el día de hoy, alertando al supervisor de forma preventiva antes de que afecte a la cobertura.

### 4. Soporte Nativo de ULIDs (Regla GLO-01)
*   **Problema:** El widget `RecentIncidentsWidget` lee datos de `AttendanceIncident`. Tras la migración del modelo a ULID, se debe garantizar que la serialización de datos de Livewire y las referencias en URL utilicen cadenas seguras en lugar de intentar castear a enteros polimórficos.
*   **Solución:** Desactivar explícitamente el cast numérico de `entity_id` en las relaciones de auditoría y asegurar que las rutas utilicen el enlace implícito por modelo (Model Binding) basado en la clave primaria tipo string de `BaseModel`.

### 5. Estética Premium FluxUI
*   **Problema:** Las tablas HTML tradicionales saturan visualmente a los supervisores durante monitoreos continuos.
*   **Solución:** Explotar la directiva `<flux:grid>` y estructurar las colas mediante componentes interactivos con efectos CSS de transición al actualizar la data, reduciendo la fatiga visual mediante gradientes sutiles y micro-interacciones.

