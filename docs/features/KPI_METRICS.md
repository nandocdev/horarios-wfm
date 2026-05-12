# Documento Técnico — KPIs e Indicadores Operativos WFM

## Sistema WFM — Call Center CSS

Basado en la arquitectura y modelo de datos del sistema híbrido WFM definido para el Call Center CSS (WFM Administrativo + Operaciones Realtime).

---

# 1. Objetivo y Jerarquía de Verdad

El objetivo del sistema es controlar la operación a través de múltiples dimensiones. Para evitar ambigüedad semántica y colisiones de datos, se establece la siguiente jerarquía de verdad arquitectónica:

| Dominio               | Source of Truth   |
| --------------------- | ----------------- |
| Horario esperado      | WFM               |
| Login/logout          | Cisco (ACD)       |
| Estados realtime      | Cisco (ACD)       |
| Excepciones           | WFM               |
| Correcciones manuales | Workflow auditado |
| Productividad         | Cisco (ACD)       |
| Forecasting           | WFM               |

---

# 2. Categorización de KPIs

Para evitar inconsistencias conceptuales, los KPIs se dividen estrictamente por su naturaleza y fuente de datos.

---

## 2.1 KPIs Administrativos (Fuente: WFM)

Basados en permisos, incidencias, horarios y aprobaciones. No requieren integración con ACD.

### 2.1.1 Cobertura Operativa
`Coverage Rate = (Available Agents / Scheduled Agents) * 100`
* **Available Agents:** Agentes sin incidencias ni permisos activos.

### 2.1.2 Ausentismo Administrativo
`Absenteeism Rate = (Absent Minutes / Scheduled Productive Minutes) * 100`
*(Nota: Actualmente en el sistema se calcula por conteo de agentes: `Ausentes / (Total - Excepciones) * 100`)*

### 2.1.3 Tasa de Permisos
`Leave Rate = (Approved Leave Minutes / Scheduled Productive Minutes) * 100`

### 2.1.4 Índice de Incidencias
`Incident Index = (Total Incidents / Total Employees) * 100`

### 2.1.5 Tasa de Cambios de Turno
`Shift Swap Rate = (Approved Shift Swaps / Total Scheduled Agents) * 100`

### 2.1.6 Cumplimiento de Publicación de Horarios
`Publication Compliance = (Published Weeks / Planned Weeks) * 100`

### 2.1.7 KPIs de Coordinadores
* **Average Approval Time:** `Sum(Approval Time) / Total Requests`
* **Rejection Rate:** `(Rejected Requests / Total Requests) * 100`

---

## 2.2 KPIs Operacionales Realtime (Fuente: Cisco ACD + WFM)

Métricas basadas en la telemetría real comparada con el horario planificado.

### 2.2.1 Real-Time Adherence
Mide si el agente está en el estado correcto en el momento correcto.
`Real Time Adherence = (Time In Expected State / Total Scheduled Time) * 100`

**Mapeo Estricto de Estados (Expected vs Real):**
| Estado Programado | Estado Esperado ACD |
| ----------------- | ------------------- |
| Shift             | Ready               |
| Lunch             | AUX_LUNCH           |
| Break             | AUX_BREAK           |
| Coaching          | AUX_COACHING        |
| Meeting           | AUX_MEETING         |

### 2.2.2 Conformance
Mide si el agente cumplió la cantidad TOTAL de tiempo planificada (sin importar si fue a la hora exacta).
`Conformance = (Actual Worked Time / Scheduled Worked Time) * 100`

### 2.2.3 Utilization
Tiempo productivo vs tiempo pagado.
`Utilization = (Productive Time / Paid Time) * 100`

### 2.2.4 Occupancy
Carga de trabajo sobre tiempo disponible realtime.
`Occupancy = ((Talk Time + Hold Time + ACW) / (Logged In Time - Available Idle Time)) * 100`

### 2.2.5 KPIs de Contact Center
* **ASA (Average Speed of Answer):** `Total Queue Wait Time / Answered Calls`
* **AHT (Average Handle Time):** `(Talk Time + Hold Time + ACW) / Handled Calls`
* **Service Level:** `(Calls Answered Within Threshold / Total Offered Calls) * 100` (El SLA es el objetivo, ej. 80/20, el Service Level es el resultado medido).
* **AUX Utilization:** `(AUX Time / Logged In Time) * 100`
* **Idle Ratio:** `(Idle Time / Logged In Time) * 100`

---

## 2.3 KPIs WFM Analíticos

Basados en forecasting, staffing y optimización matemática. Requieren un nivel de madurez superior.

### 2.3.1 Shrinkage Real
`Shrinkage = (Unavailable Time / Paid Time) * 100`
Debe incluir:
* **Planificado:** lunch, break, coaching, meetings, training.
* **No planificado:** absentismo, tardanzas, abuso de AUX, desconexiones.

### 2.3.2 Lo que falta para Enterprise WFM
El sistema actualmente es una plataforma híbrida WFM + operaciones realtime. Para alcanzar un nivel enterprise, faltan las siguientes métricas y features analíticas:
* Forecasting y ML Forecasting (❌)
* Erlang C y Multi-skill routing (❌)
* Interval staffing y Queue blending (❌)
* Intraday reforecast y Scenario simulation (❌)
* PTO optimization (❌)

---

# 3. Estado de implementacion de KPIs (Actualizado Mayo 2026)

1. **Adherencia al Horario (Realtime & Histórico)**: **COMPLETADO** (Implementado vía `OperationsModule` cruzando estados ACD vs Programación WFM).
2. **Productividad y Utilización**: **COMPLETADO** (Cálculo exacto de segundos productivos extraídos de la telemetría `ConnectModule`).
3. **Ausentismo (Tasa / Conteo)**: **COMPLETADO** (Operativo a nivel de conteo de empleados en `TeamPerformanceSummary`).
4. **Volumen de Llamadas y AHT**: **COMPLETADO** (Implementado extrayendo datos de `agent_call_performance`).
5. **Cobertura Operativa (Coverage Rate)**: **PENDIENTE**
6. **Tasa de Permisos (Leave Rate)**: **PENDIENTE**
7. **Tasa de Cambios de Turno (Shift Swap Rate)**: **PENDIENTE**
8. **Índice de Incidencias**: **PENDIENTE**
9. **Nivel de Publicación de Horarios**: **PENDIENTE**
10. **Tiempo Promedio de Aprobación**: **PENDIENTE**
11. **Ratio de Rechazo**: **PENDIENTE**
12. **Conformance**: **PENDIENTE**
13. **Occupancy Real**: **PENDIENTE**
14. **Shrinkage Real**: **PENDIENTE**
15. **Service Level & ASA**: **PENDIENTE**
16. **Ocupación Teórica / Erlang / Forecasting**: **PENDIENTE**
