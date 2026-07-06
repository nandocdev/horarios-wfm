# Especificación Técnica: Dashboard Operativo — Estructura y KPIs (Antigravity 2.0)

> **Módulo:** `OperationsModule`
> **Layout Principal:** `app/Modules/OperationsModule/Resources/Views/livewire/dashboard.blade.php`
> **Componente Livewire:** `App\Modules\OperationsModule\Livewire\Dashboard`
> **Widgets:** 6 componentes independientes lazy-loaded en `app/Modules/OperationsModule/Livewire/Widgets/`
> **Motor de Métricas:** `App\Modules\OperationsModule\Services\PerformanceService`
> **Servicio de Validación:** `App\Modules\WfmModule\Services\ScheduleValidationService`

---

## 1. Arquitectura y Estructura General

El dashboard se organiza en una sola página impulsada por **6 widgets lazy-loaded** que cargan de forma asíncrona. Cada widget se gestiona de manera independiente (renderizando un skeleton/placeholder durante la carga de datos), de modo que una degradación o fallo en un módulo CTI o de base de datos no bloquea la visualización del resto de la interfaz.

Soporta dos modos de visualización:
-   **Tiempo Real (Hoy):** Polling cada 15 segundos. Datos consolidados desde `AgentRealtimeState` y `csq_realtime_stats`.
-   **Histórico (Fechas Pasadas):** Sin polling. Datos agregados directamente desde `call_records`, `agent_state_transitions` y excepciones.

### Grid de Layout (FluxUI & TailwindCSS)

```
┌────────────────────────────────────────────────────────────────┐
│  Cabecera: Título + Selector Fecha + CTI Link Status (Online)  │
├────────────────────────────────────────────────────────────────┤
│  Hero KPIs (6 tarjetas cacheadas):                             │
│  Cobertura · Adherencia · Ocupación · SL % · Ausentismo · Shrink│
├───────────────────────────────┬────────────────────────────────┤
│  Estado de Colas (Tabla CTI)  │  Distribución de Estados       │
│  Cada fila = 1 cola ACD       │  (Gráfico donut ApexCharts)    │
│  Waiting, Talking, SLA, LWT   │  Ready / Talking / AUX / Off   │
├───────────────────────────────┴────────────────────────────────┤
│  Comparativo de Volumen (Gráfico barras agrupadas ApexCharts)  │
│  Semana actual vs. anterior — Atendidas / Abandonadas por día  │
├───────────────────────────────┬────────────────────────────────┤
│  Alertas Críticas (Pre-check) │  Últimas Incidencias (Tabla)   │
│  Pendientes / SLA / Overlaps  │  Agente, Tipo, Hora (ULIDs)    │
└───────────────────────────────┴────────────────────────────────┘
```

### Tema Visual y Experiencia de Usuario (Aesthetics)
*   **Modo Oscuro Predeterminado (Dark Mode):** Optimizado para salas de monitoreo continuo (reducción de fatiga visual).
*   **Tarjetas de Alta Definición:** Bordes redondeados sutiles, sombras y bordes semitransparentes (efecto glassmorphism).
*   **Semáforo de Alertas:** Colores funcionales (verde = óptimo, ámbar = precaución, rojo = crítico) aplicados en bordes inferiores de KPIs, badges de SLA y llamadas en espera.

---

## 2. Cabecera y Resiliencia CTI (Cisco UCCX)

*   **Indicador de Conectividad CTI (CTI Link Status):** Muestra el estado del socket o API intermedia de Cisco UCCX (`Online` / `Offline`) con la marca de tiempo de la última sincronización.
*   **Mecanismo de Fallback (Resiliencia):** Las consultas hacia la base de datos o API de Cisco se ejecutan bajo un bloque `try/catch` con un timeout máximo de 2 segundos. Si ocurre una desconexión, el dashboard levanta una alerta visual de pérdida de enlace y hace fallback inmediato a los últimos datos guardados en la base de datos o caché de Redis, garantizando que la UI nunca se congele.

---

## 3. Especificación de los Widgets

### Widget 1: Hero KPIs (Tarjetas de Control)
*   **Componente:** `HeroKpiWidget.php` → `PerformanceService::getGlobalHeroKpis()`
*   **Caché (Redis):** Para evitar que el polling de 15 segundos sobrecargue PostgreSQL, los cálculos históricos pesados (*Ausentismo* y *Shrinkage*) se almacenan en Redis con un TTL de 2 minutos. Las métricas volátiles de telefonía se calculan en vivo.

| KPI                   | Qué mide                                                  | Umbrales                   | Cálculo (Realtime)                                    |
| --------------------- | --------------------------------------------------------- | -------------------------- | ----------------------------------------------------- |
| **Cobertura**         | % de agentes programados que están conectados             | 🔴 <90% · 🟡 90-95% · 🟢 ≥95% | Conectados / Programados × 100                        |
| **Adherencia**        | % de tiempo que los agentes cumplen el estado planificado | 🔴 <85% · 🟡 85-92% · 🟢 ≥92% | `AdherenceAction::executeBatch()`                     |
| **Ocupación**         | % de tiempo productivo vs. disponible (Ready)             | 🔴 >90% · 🟡 85-90% · 🟢 ≤85% | Talking+Work+Reserved / (Talking+Work+Reserved+Ready) |
| **Nivel de Servicio** | % de llamadas atendidas dentro del SLA                    | 🔴 <80% · 🟡 80-90% · 🟢 ≥90% | Promedio `csq_realtime_stats.service_level_long_term` |
| **Ausentismo**        | % de agentes programados que no se conectaron             | 🔴 >5% · 🟢 ≤5%              | (Programados - Conectados) / Programados × 100        |
| **Shrinkage**         | % de tiempo no productivo sobre el total pagado           | Neutro (Informativo)       | Calculado en base a excepciones y micro-actividades   |

---

### Widget 2: Estado de Colas ACD
*   **Componente:** `QueueStatsWidget.php`
*   Muestra el rendimiento en tiempo real de todas las colas ACD activas de Cisco UCCX.

| Columna            | Descripción                                | Origen / Comportamiento                                       |
| ------------------ | ------------------------------------------ | ------------------------------------------------------------- |
| **Cola**           | Nombre de la cola Cisco                    | `csq_realtime_stats.csq_name`                                 |
| **Espera**         | Llamadas esperando en cola                 | `csq_realtime_stats.calls_waiting` (Saca alerta visual si >5) |
| **Hablando**       | Agentes en estado TALKING en esa cola      | `AgentRealtimeState` agrupado por cola CTI                    |
| **Recibidas**      | Llamadas entrantes hoy                     | `csq_realtime_stats.total_calls_since_midnight`               |
| **Atendidas**      | Llamadas atendidas hoy                     | `csq_realtime_stats.calls_handled_since_midnight`             |
| **Abandonadas**    | Llamadas abandonadas hoy                   | Diferencia: Recibidas - Atendidas                             |
| **T. Máx. Espera** | Tiempo de la llamada más antigua en espera | `csq_realtime_stats.longest_call_in_queue`                    |
| **SL %**           | Porcentaje de nivel de servicio            | `csq_realtime_stats.service_level_long_term` (Badge dinámico) |

---

### Widget 3: Distribución de Estados
*   **Componente:** `StateDistributionWidget.php`
*   Gráfico de dona (ApexCharts) que representa el estado en caliente de la fuerza de trabajo en posiciones operativas (Operadores, Supervisores, Coordinadores).
    *   🟢 **Ready:** Agentes en espera de llamadas.
    *   🔵 **Talking:** Agentes en conversación activa.
    *   🟡 **AUX:** Agentes en pausas justificadas (Breaks, Baño, Almuerzo, Backoffice).
    *   ⚪ **Offline:** Agentes desconectados de Finesse (Logout).

---

### Widget 4: Comparativo de Volumen
*   **Componente:** `VolumeComparisonWidget.php`
*   Gráfico de barras agrupadas (ApexCharts) que compara el volumen de llamadas de la semana actual contra el de la semana anterior.
*   Presenta series de llamadas **Atendidas** y **Abandonadas** por cada día de la semana laboral (Lunes a Viernes) para proyectar tendencias y ajustar la planificación de personal de WFM.

---

### Widget 5: Alertas Críticas (Pre-check Preventivo)
*   **Componente:** `CriticalAlertsWidget.php`
*   **Integración con ScheduleValidationService:** El widget no solo evalúa solicitudes pendientes y colas en peligro, sino que invoca en segundo plano al `ScheduleValidationService` para realizar un escaneo de "Colisiones en Mallas Horarias". Si detecta que algún operador tiene solapamiento de turnos, colisión con actividades intradía o excepciones cruzadas para el día de hoy, levanta una alerta visual de tipo advertencia para que el supervisor o analista de WFM corrija la malla antes de que impacte a la operación.

---

### Widget 6: Últimas Incidencias de Asistencia
*   **Componente:** `RecentIncidentsWidget.php`
*   Muestra las 5 incidencias de asistencia (tardanzas, ausencias) más recientes detectadas por el motor de reconciliación nocturna.
*   **Uso de ULIDs:** Los registros de `AttendanceIncident` utilizan claves primarias tipo ULID (heredando de `BaseModel` compartida). Las llamadas de enlace del frontend (Livewire) procesan y serializan los IDs como cadenas de caracteres de forma nativa, asegurando total compatibilidad con las trazas del log de auditoría global (`AuditLog`) sin conversiones o truncamientos numéricos.

---

## 4. Estrategia de Implementación y Rendimiento

1.  **Lazy Loading Exhaustivo:** Los widgets se inyectan en Blade utilizando la directiva de Livewire: `<livewire:widget-name lazy />`. Esto asegura que el esqueleto principal del dashboard responda de forma instantánea al usuario final.
2.  **Caché Redis:** Toda consulta histórica pesada que no requiera actualización en tiempo real se resuelve mediante la clave de caché para no penalizar la base de datos PostgreSQL de transacciones.
3.  **Transacciones y Seguridad:** Toda visualización del dashboard está regulada por políticas de Spatie (`operations.reports.view` y `incidents.manage`).
