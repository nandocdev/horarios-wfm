# Especificación Técnica: Dashboard de Desempeño Individual del Agente (Antigravity 2.0)

> **Módulo:** `OperationsModule`
> **Layout Principal:** `app/Modules/OperationsModule/Resources/Views/livewire/agent-performance-dashboard.blade.php`
> **Componente Livewire:** `App\Modules\OperationsModule\Livewire\AgentPerformanceDashboard`
> **Ruta Asociada:** `/operations/agent-performance/{employee_id?}` (si el parámetro es omitido, resuelve el empleado del usuario autenticado).

---

## 1. Arquitectura y Estructura General

Este dashboard proporciona una vista analítica y comparativa del rendimiento individual de un operador durante sus **últimos 5 días laborados** (4 días históricos consolidados más el día actual). Sirve como herramienta de autocontrol para el agente ("Mi Espacio") y como consola de feedback para supervisores ("Vista de Equipo").

### Grid de Layout (FluxUI & TailwindCSS)

```
┌────────────────────────────────────────────────────────────────┐
│  Cabecera: Selector de Agente (solo supervisores) + Datos Perfil│
├────────────────────────────────────────────────────────────────┤
│  Hero cards (Rendimiento 5 Días):                              │
│  Adherencia Avg · AHT Global · Ocupación Avg · Llamadas Totales│
├───────────────────────────────┬────────────────────────────────┤
│  Historial de Desempeño       │  Distribución de Estados       │
│  (Gráfico línea/área Apex)    │  (Barras apiladas 100% Apex)   │
│  Adherencia vs Ocupación      │  Ready / Talk / AUX / Offline  │
├───────────────────────────────┴────────────────────────────────┤
│  Rendimiento por Colas ACD (Tabla detallada de llamadas y AHT)  │
├────────────────────────────────────────────────────────────────┤
│  Desviaciones del Turno (Timeline programado vs real diario)   │
└────────────────────────────────────────────────────────────────┘
```

---

## 2. Orígenes y Extracción de Datos

Para construir el análisis histórico comparativo de los últimos 5 días laborados (excluyendo fines de semana o días de descanso sin turnos programados), el controlador recopila información de los siguientes modelos:

1.  **Llamadas y Tiempos de Operación (`call_records` / `call_queues`):**
    *   Volumen de llamadas atendidas y transferidas por el agente.
    *   **AHT (Average Handle Time):** Suma de `talk_time` + `hold_time` + `work_time` dividido entre llamadas atendidas.
2.  **Historial de Estados (`agent_state_transitions` / `AgentRealtimeState`):**
    *   Tiempos de permanencia diarios en los estados clave: `READY`, `TALKING`, `NOT_READY` (AUX), `WORK`, `LOGOUT`.
3.  **Mallas y Planificación (`weekly_schedule_assignments`):**
    *   Jornadas de trabajo programadas (horas de entrada y salida) utilizadas como base para la adherencia.
4.  **Excepciones de Turno (`schedule_exceptions`):**
    *   Exclusiones por permisos, vacaciones o licencias de jornada completa para omitir días no laborados de la secuencia de 5 días.

---

## 3. Especificación de los Widgets

### Widget 1: Tarjetas de Control (Hero KPIs)
*   **Adherencia Promedio:** Porcentaje de tiempo que el agente permaneció en el estado correcto según su malla durante los 5 días.
*   **AHT Global:** Promedio de tiempo de gestión acumulado en segundos de la telefonía.
*   **Ocupación Promedio:** Ratio de productividad del tiempo conectado (`Talk + Work` / `Connected Time`).
*   **Llamadas Totales:** Conteo total de llamadas resueltas.
*   **Total AUX (Minutos):** Minutos acumulados en estados de pausa.

### Widget 2: Historial de Desempeño (Líneas)
*   Gráfico de ApexCharts de doble eje Y que compara la evolución de la **Adherencia** y la **Ocupación** del agente a lo largo de la ventana de 5 días.
*   Permite identificar caídas sistemáticas en adherencia o picos de saturación de ocupación.

### Widget 3: Distribución de Tiempo en Estados (Barras Apiladas 100%)
*   Gráfico de barras verticales apiladas al 100% (ApexCharts) para cada uno de los 5 días.
*   Visualiza la proporción del día dedicada a:
    *   🟢 **Ready:** Disponible para atención.
    *   🔵 **Talking:** Tiempo en conversación activa.
    *   🟡 **AUX:** Tiempo de breaks, almuerzo, capacitación.
    *   ⚪ **Offline:** Desconexión o retrasos de log-in.

### Widget 4: Detalle por Colas ACD (Tabla)
*   Muestra el rendimiento desagregado del agente por cola atendida en el periodo de 5 días:
    *   **Cola:** Nombre del servicio.
    *   **Atendidas:** Número de llamadas.
    *   **AHT:** Tiempo promedio de atención (mm:ss).
    *   **SLA Individual:** % de llamadas atendidas bajo el umbral establecido de la cola (ej: 20s).

### Widget 5: Línea de Desviaciones (Timeline)
*   Visualización tipo listado o barra de progreso horizontal que compara, para cada uno de los 5 días, las desviaciones exactas de conexión:
    *   Retraso en el log-in (entrada).
    *   Exceso de tiempo en AUX (breaks).
    *   Salidas anticipadas.

---

## 4. Estrategia de Rendimiento y Cacheo

Calcular las métricas de un agente individual a lo largo de 5 días laborados requiere procesar miles de registros de transiciones de estados y llamadas. Para garantizar tiempos de respuesta instantáneos en la UI SPA:

1.  **Caché de Históricos en Redis (TTL 24 Horas):**
    *   Los 4 días completamente transcurridos del pasado son inmutables. Sus métricas y distribuciones de estados se calculan una sola vez y se almacenan en Redis con la clave:
        `wfm:agent:{employee_id}:kpis:{date_string}`
    *   Al cargar el dashboard, el backend realiza un pipeline de lectura en Redis de los 4 días históricos.
2.  **Cálculo en Vivo de "Hoy" (TTL 1 Minuto):**
    *   Los datos del día en curso (el 5to día) se calculan en vivo leyendo `AgentRealtimeState` y los registros temporales de hoy, pero se protegen con un micro-caché de 1 minuto para amortiguar refrescos accidentales de la página.

---

## 5. Seguridad y Reglas de Autorización

El acceso al dashboard se valida estrictamente mediante una Policy de Laravel:

```php
// En App\Modules\OperationsModule\Policies\AgentPerformancePolicy
public function view(User $authUser, Employee $targetEmployee): bool
{
    // 1. El propio agente puede ver su dashboard
    if ($authUser->employee_id === $targetEmployee->id) {
        return true;
    }

    // 2. Supervisores, coordinadores y analistas WFM con permisos explícitos
    return $authUser->hasPermissionTo('agent.performance.view');
}
```