# Catálogo Consolidado de KPIs — Sistema WFM Call Center CSS

## Objetivo

Centralizar todos los KPIs operativos, administrativos, realtime y analíticos discutidos para el sistema WFM + ACD del Call Center CSS.

El documento incluye:

* nombre del KPI
* definición breve
* variables utilizadas
* fórmula de cálculo

Incluye métricas:

* administrativas
* operacionales realtime
* ACD
* planificación WFM
* productividad
* performance de contact center

Independientemente de si actualmente están implementadas o no.

---

# 1. KPIs Administrativos

---

# 1.1 Coverage Rate (Cobertura Operativa)

## Definición

Mide el porcentaje de agentes disponibles respecto a los agentes programados.

## Variables

| Variable         | Definición          |
| ---------------- | ------------------- |
| Available Agents | Agentes disponibles |
| Scheduled Agents | Agentes programados |

## Fórmula

Coverage\ Rate = \frac{Available\ Agents}{Scheduled\ Agents} \times 100

---

# 1.2 Absenteeism Rate (Ausentismo)

## Definición

Porcentaje de tiempo perdido por ausencias e incidencias.

## Variables

| Variable                     | Definición                      |
| ---------------------------- | ------------------------------- |
| Absent Minutes               | Minutos ausentes                |
| Scheduled Productive Minutes | Minutos productivos programados |

## Fórmula

Absenteeism\ Rate = \frac{Absent\ Minutes}{Scheduled\ Productive\ Minutes} \times 100

---

# 1.3 Leave Rate (Tasa de Permisos)

## Definición

Porcentaje de tiempo programado consumido por permisos aprobados.

## Variables

| Variable                     | Definición          |
| ---------------------------- | ------------------- |
| Approved Leave Minutes       | Minutos aprobados   |
| Scheduled Productive Minutes | Minutos programados |

## Fórmula

Leave\ Rate = \frac{Approved\ Leave\ Minutes}{Scheduled\ Productive\ Minutes} \times 100

---

# 1.4 Administrative Compliance

## Definición

Mide el cumplimiento administrativo del horario planificado considerando incidencias registradas.

## Variables

| Variable           | Definición                      |
| ------------------ | ------------------------------- |
| Productive Minutes | Tiempo productivo esperado      |
| Incident Minutes   | Tiempo afectado por incidencias |

## Fórmula

Administrative\ Compliance = \frac{Productive\ Minutes - Incident\ Minutes}{Productive\ Minutes} \times 100

---

# 1.5 Shift Swap Rate

## Definición

Porcentaje de cambios de turno aprobados respecto al total de agentes programados.

## Variables

| Variable               | Definición          |
| ---------------------- | ------------------- |
| Approved Shift Swaps   | Cambios aprobados   |
| Total Scheduled Agents | Agentes programados |

## Fórmula

Shift\ Swap\ Rate = \frac{Approved\ Shift\ Swaps}{Total\ Scheduled\ Agents} \times 100

---

# 1.6 Incident Index

## Definición

Cantidad de incidencias registradas respecto a la población operativa.

## Variables

| Variable        | Definición        |
| --------------- | ----------------- |
| Total Incidents | Total incidencias |
| Total Employees | Total empleados   |

## Fórmula

Incident\ Index = \frac{Total\ Incidents}{Total\ Employees} \times 100

---

# 1.7 Publication Compliance

## Definición

Mide disciplina del área WFM en la publicación de horarios.

## Variables

| Variable        | Definición           |
| --------------- | -------------------- |
| Published Weeks | Semanas publicadas   |
| Planned Weeks   | Semanas planificadas |

## Fórmula

Publication\ Compliance = \frac{Published\ Weeks}{Planned\ Weeks} \times 100

---

# 1.8 Average Approval Time

## Definición

Tiempo promedio requerido para aprobar solicitudes.

## Variables

| Variable       | Definición        |
| -------------- | ----------------- |
| Approval Time  | Tiempo individual |
| Total Requests | Solicitudes       |

## Fórmula

Average\ Approval\ Time = \frac{\sum Approval\ Time}{Total\ Requests}

---

# 1.9 Rejection Rate

## Definición

Porcentaje de solicitudes rechazadas.

## Variables

| Variable          | Definición             |
| ----------------- | ---------------------- |
| Rejected Requests | Solicitudes rechazadas |
| Total Requests    | Solicitudes totales    |

## Fórmula

Rejection\ Rate = \frac{Rejected\ Requests}{Total\ Requests} \times 100

---

# 1.10 Assignment Completion

## Definición

Porcentaje de empleados con horario asignado.

## Variables

| Variable           | Definición          |
| ------------------ | ------------------- |
| Assigned Employees | Empleados asignados |
| Active Employees   | Empleados activos   |

## Fórmula

Assignment\ Completion = \frac{Assigned\ Employees}{Active\ Employees} \times 100

---

# 2. KPIs Operacionales Realtime

---

# 2.1 Real Time Adherence

## Definición

Mide qué porcentaje del tiempo el agente estuvo en el estado esperado según el horario.

## Variables

| Variable               | Definición                |
| ---------------------- | ------------------------- |
| Time In Expected State | Tiempo en estado esperado |
| Total Scheduled Time   | Tiempo programado         |

## Fórmula

Real\ Time\ Adherence = \frac{Time\ In\ Expected\ State}{Total\ Scheduled\ Time} \times 100

---

# 2.2 Conformance

## Definición

Mide si el agente trabajó la cantidad total de tiempo programada.

## Variables

| Variable              | Definición       |
| --------------------- | ---------------- |
| Actual Worked Time    | Tiempo trabajado |
| Scheduled Worked Time | Tiempo esperado  |

## Fórmula

Conformance = \frac{Actual\ Worked\ Time}{Scheduled\ Worked\ Time} \times 100

---

# 2.3 Occupancy

## Definición

Mide presión operativa sobre tiempo disponible realtime.

## Variables

| Variable            | Definición         |
| ------------------- | ------------------ |
| Talk Time           | Tiempo conversando |
| Hold Time           | Tiempo hold        |
| ACW                 | After Call Work    |
| Logged In Time      | Tiempo logueado    |
| Available Idle Time | Tiempo idle        |

## Fórmula

Occupancy = \frac{Talk\ Time + Hold\ Time + ACW}{Logged\ In\ Time - Available\ Idle\ Time} \times 100

---

# 2.4 Utilization

## Definición

Mide tiempo productivo respecto al tiempo pagado.

## Variables

| Variable        | Definición        |
| --------------- | ----------------- |
| Productive Time | Tiempo productivo |
| Paid Time       | Tiempo pagado     |

## Fórmula

Utilization = \frac{Productive\ Time}{Paid\ Time} \times 100

---

# 2.5 Intraday Utilization

## Definición

Mide impacto operativo de actividades intradía.

## Variables

| Variable                  | Definición        |
| ------------------------- | ----------------- |
| Intraday Activity Minutes | Tiempo intradía   |
| Available Minutes         | Tiempo disponible |

## Fórmula

Intraday\ Utilization = \frac{Intraday\ Activity\ Minutes}{Available\ Minutes} \times 100

---

# 2.6 AUX Utilization

## Definición

Porcentaje del tiempo logueado consumido en estados AUX.

## Variables

| Variable       | Definición      |
| -------------- | --------------- |
| AUX Time       | Tiempo AUX      |
| Logged In Time | Tiempo logueado |

## Fórmula

AUX\ Utilization = \frac{AUX\ Time}{Logged\ In\ Time} \times 100

---

# 2.7 Idle Ratio

## Definición

Porcentaje de tiempo idle sobre tiempo logueado.

## Variables

| Variable       | Definición      |
| -------------- | --------------- |
| Idle Time      | Tiempo idle     |
| Logged In Time | Tiempo logueado |

## Fórmula

Idle\ Ratio = \frac{Idle\ Time}{Logged\ In\ Time} \times 100

---

# 2.8 Net Productive Capacity

## Definición

Capacidad operativa neta explotable.

## Variables

| Variable          | Definición          |
| ----------------- | ------------------- |
| Available Minutes | Minutos disponibles |
| Intraday Minutes  | Minutos intradía    |

## Fórmula

Net\ Productive\ Capacity = Available\ Minutes - Intraday\ Minutes

---

# 2.9 Shrinkage

## Definición

Porcentaje de tiempo pagado no disponible para atención operativa.

## Variables

| Variable         | Definición           |
| ---------------- | -------------------- |
| Unavailable Time | Tiempo no disponible |
| Paid Time        | Tiempo pagado        |

## Fórmula

Shrinkage = \frac{Unavailable\ Time}{Paid\ Time} \times 100

---

# 3. KPIs de Contact Center / ACD

---

# 3.1 Service Level

## Definición

Porcentaje de llamadas atendidas dentro del umbral SLA.

## Variables

| Variable                        | Definición          |
| ------------------------------- | ------------------- |
| Calls Answered Within Threshold | Llamadas dentro SLA |
| Total Offered Calls             | Llamadas ofrecidas  |

## Fórmula

Service\ Level = \frac{Calls\ Answered\ Within\ Threshold}{Total\ Offered\ Calls} \times 100

---

# 3.2 SLA

## Definición

Objetivo contractual de atención.

## Variables

| Variable          | Definición |
| ----------------- | ---------- |
| Threshold Seconds | Umbral     |
| Target Percentage | Objetivo   |

## Fórmula

```text
Ejemplo:
80/20
=
80% de llamadas respondidas en 20 segundos
```

---

# 3.3 ASA (Average Speed of Answer)

## Definición

Tiempo promedio que espera una llamada antes de ser atendida.

## Variables

| Variable              | Definición           |
| --------------------- | -------------------- |
| Total Queue Wait Time | Tiempo total espera  |
| Answered Calls        | Llamadas contestadas |

## Fórmula

ASA = \frac{Total\ Queue\ Wait\ Time}{Answered\ Calls}

---

# 3.4 AHT (Average Handle Time)

## Definición

Tiempo promedio requerido para manejar una interacción.

## Variables

| Variable      | Definición         |
| ------------- | ------------------ |
| Talk Time     | Conversación       |
| Hold Time     | Hold               |
| ACW           | After Call Work    |
| Handled Calls | Llamadas manejadas |

## Fórmula

AHT = \frac{Talk\ Time + Hold\ Time + ACW}{Handled\ Calls}

---

# 4. KPIs WFM Analíticos

---

# 4.1 Staffing Pressure Ratio

## Definición

Mide presión teórica entre carga requerida y capacidad disponible.

## Variables

| Variable          | Definición          |
| ----------------- | ------------------- |
| Required Minutes  | Minutos requeridos  |
| Available Minutes | Minutos disponibles |

## Fórmula

Staffing\ Pressure\ Ratio = \frac{Required\ Minutes}{Available\ Minutes} \times 100

---

# 4.2 Forecast Accuracy

## Definición

Mide precisión entre forecast y demanda real.

## Variables

| Variable        | Definición   |
| --------------- | ------------ |
| Forecast Volume | Forecast     |
| Actual Volume   | Volumen real |

## Fórmula

Forecast\ Accuracy = \left(1 - \frac{|Forecast - Actual|}{Actual}\right) \times 100

---

# 4.3 Interval Staffing Accuracy

## Definición

Precisión de staffing por intervalos.

## Variables

| Variable       | Definición      |
| -------------- | --------------- |
| Required Staff | Staff requerido |
| Actual Staff   | Staff real      |

## Fórmula

Interval\ Staffing\ Accuracy = \left(1 - \frac{|Required\ Staff - Actual\ Staff|}{Required\ Staff}\right) \times 100

---

# 4.4 Overstaff Ratio

## Definición

Porcentaje de exceso de staffing.

## Variables

| Variable            | Definición |
| ------------------- | ---------- |
| Extra Staff Minutes | Exceso     |
| Scheduled Minutes   | Programado |

## Fórmula

Overstaff\ Ratio = \frac{Extra\ Staff\ Minutes}{Scheduled\ Minutes} \times 100

---

# 4.5 Understaff Ratio

## Definición

Porcentaje de déficit operativo.

## Variables

| Variable              | Definición |
| --------------------- | ---------- |
| Missing Staff Minutes | Déficit    |
| Required Minutes      | Requerido  |

## Fórmula

Understaff\ Ratio = \frac{Missing\ Staff\ Minutes}{Required\ Minutes} \times 100
