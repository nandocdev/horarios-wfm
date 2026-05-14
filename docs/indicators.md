# Diccionario de Indicadores y Métricas WFM

Este documento define los eventos fuente, métricas operativas, KPIs y estructuras analíticas utilizadas dentro del sistema WFM.
El objetivo es mantener consistencia operacional, auditabilidad histórica y una base sólida para reporting y forecasting.

---

# 1. Eventos Fuente (Source Events)

Los eventos fuente representan la telemetría cruda del sistema.
No deben modificarse ni recalcularse manualmente.

---

## 1.1 Eventos de Asistencia (`clock_events`)

Registro cronológico de eventos reales de asistencia y conexión.

### Eventos soportados

| Evento        | Descripción             |
| ------------- | ----------------------- |
| `clock_in`    | Inicio de jornada       |
| `clock_out`   | Fin de jornada          |
| `break_start` | Inicio de descanso      |
| `break_end`   | Fin de descanso         |
| `lunch_start` | Inicio de almuerzo      |
| `lunch_end`   | Fin de almuerzo         |
| `login`       | Conexión al sistema     |
| `logout`      | Desconexión del sistema |

### Fuentes posibles

| Fuente      | Descripción         |
| ----------- | ------------------- |
| `manual`    | Registro manual     |
| `biometric` | Biométrico          |
| `finesse`   | Cisco Finesse       |
| `genesys`   | Genesys Cloud       |
| `api`       | Integración externa |
| `import`    | Importación batch   |

---

## 1.2 Estados del Agente (`agent_states`)

Estados operativos reportados por plataformas de atención.

### Estados normalizados

| Estado               | Descripción                           |
| -------------------- | ------------------------------------- |
| `ready`              | Disponible para recibir interacciones |
| `talk`               | Conversación activa                   |
| `hold`               | Cliente en espera                     |
| `acw`                | After Call Work                       |
| `not_ready_break`    | Descanso                              |
| `not_ready_lunch`    | Almuerzo                              |
| `not_ready_training` | Capacitación                          |
| `offline`            | Desconectado                          |

---

## 1.3 Eventos de Interacción (`interaction_events`)

Eventos asociados a llamadas, chats y contactos digitales.

### Eventos soportados

| Evento        | Descripción            |
| ------------- | ---------------------- |
| `offered`     | Interacción recibida   |
| `queued`      | Interacción en cola    |
| `answered`    | Interacción atendida   |
| `abandoned`   | Cliente abandonó       |
| `transferred` | Transferencia          |
| `completed`   | Interacción finalizada |

---

# 2. Estructura Operativa y Workforce

---

## 2.1 Headcount y Estructura Organizacional

### Métricas de Personal

| Métrica                | Descripción                         |
| ---------------------- | ----------------------------------- |
| Headcount Activo       | Empleados activos en RRHH           |
| Headcount Planificable | Empleados elegibles para scheduling |
| Headcount Conectado    | Empleados actualmente logueados     |
| Headcount Productivo   | Empleados generando interacción     |

---

## 2.2 Jerarquía Operativa

Estructura organizacional utilizada para reporting y supervisión.

### Relaciones soportadas

```text
Operador
→ Supervisor
→ Coordinador
→ Gerencia
```

---

# 3. Planificación y Scheduling

---

## 3.1 Turnos Base (`base_shifts`)

Definición reusable de jornadas estándar.

### Ejemplos

| Nombre         | Horario       |
| -------------- | ------------- |
| Administrativo | 08:00 - 17:00 |
| Matutino       | 06:00 - 15:00 |
| Vespertino     | 13:00 - 22:00 |

---

## 3.2 Planes de Horario (`schedule_plans`)

Representan el ciclo de planificación.

### Estados

| Estado      | Descripción           |
| ----------- | --------------------- |
| `draft`     | Editable              |
| `published` | Publicado y congelado |
| `archived`  | Histórico             |

---

## 3.3 Bloques de Horario (`schedule_blocks`)

Unidad mínima de planificación operativa.

### Campos conceptuales

| Campo         | Descripción          |
| ------------- | -------------------- |
| employee_id   | Empleado             |
| work_date     | Fecha                |
| start_time    | Inicio               |
| end_time      | Fin                  |
| activity_type | Actividad programada |
| version_id    | Snapshot publicado   |

---

## 3.4 Tipos de Actividad

| Actividad     | Descripción        |
| ------------- | ------------------ |
| Production    | Atención operativa |
| Break         | Descanso           |
| Lunch         | Almuerzo           |
| Training      | Capacitación       |
| Meeting       | Reunión            |
| Coaching      | Coaching           |
| Vacation      | Vacaciones         |
| Medical Leave | Permiso médico     |
| Overtime      | Tiempo extra       |

---

## 3.5 Excepciones Operativas

Cambios puntuales al plan original.

### Ejemplos

* permisos médicos
* capacitaciones
* suspensiones
* reuniones extraordinarias
* cambios intradía

---

## 3.6 Shift Swaps

Intercambios de turno entre empleados.

### Reglas recomendadas

* aprobación obligatoria
* auditoría completa
* validación de cobertura
* bloqueo sobre horarios publicados

---

# 4. Control de Asistencia

Los incidentes de asistencia son métricas derivadas calculadas a partir de eventos fuente.

---

## 4.1 Ausentismo

Tiempo programado no laborado.

### Fórmula

Absenteeism = \frac{Scheduled\ Time - Worked\ Time}{Scheduled\ Time}

---

## 4.2 Tardanzas

Tiempo transcurrido entre el inicio programado y el evento real de ingreso.

### Fórmula

Late\ Minutes = ClockIn - Scheduled\ Start - Tolerance

---

## 4.3 Salidas Tempranas

Tiempo no laborado al finalizar antes del horario programado.

---

## 4.4 Justificaciones

Documentación asociada a incidentes.

### Ejemplos

* incapacidad médica
* permisos
* incidencias técnicas
* fuerza mayor

---

# 5. Monitoreo y Adherencia

---

## 5.1 Adherencia Operativa

Mide el nivel de cumplimiento entre el estado programado y el estado real del agente.

### Fórmula

Adherence = \frac{Time\ In\ Correct\ State}{Scheduled\ Time}

---

## 5.2 Estados de Adherencia

| Estado          | Descripción                   |
| --------------- | ----------------------------- |
| In Adherence    | Cumple estado esperado        |
| Out Missing     | No existe actividad detectada |
| Out Unscheduled | Actividad fuera de horario    |
| Out Wrong State | Estado incorrecto             |

---

## 5.3 Matriz de Adherencia

| Actividad Programada | Estados Permitidos     |
| -------------------- | ---------------------- |
| Production           | Ready, Talk, Hold, ACW |
| Break                | Not Ready Break        |
| Lunch                | Not Ready Lunch        |
| Training             | Not Ready Training     |

---

## 5.4 Intervalos Operativos

Toda adherencia y cobertura debe calcularse por intervalos.

### Intervalos soportados

* 15 minutos
* 30 minutos

---

# 6. Métricas Operativas Base

---

## 6.1 Logged Time

Tiempo total conectado.

---

## 6.2 Ready Time

Tiempo disponible esperando interacción.

---

## 6.3 Talk Time

Tiempo de conversación activa.

---

## 6.4 Hold Time

Tiempo de cliente en espera.

---

## 6.5 ACW (After Call Work)

Tiempo administrativo posterior a interacción.

---

## 6.6 Not Ready Time

Tiempo en estados auxiliares no productivos.

---

# 7. Métricas Derivadas

---

## 7.1 Occupancy

Nivel de saturación productiva durante tiempo disponible.

### Fórmula

Occupancy = \frac{Talk + Hold + ACW}{Logged\ Time - Not\ Ready\ Time}

### Consideraciones

* proteger divisiones entre cero
* occupancy alta puede indicar understaffing

---

## 7.2 Utilization

Nivel de aprovechamiento del tiempo conectado.

### Fórmula

Utilization = \frac{Talk + Hold + ACW + Ready}{Logged\ Time}

---

## 7.3 AHT (Average Handle Time)

Tiempo promedio de gestión de interacción.

### Fórmula

AHT = \frac{Talk + Hold + ACW}{Handled\ Contacts}

### Componentes considerados

| Componente | Incluido     |
| ---------- | ------------ |
| Talk       | Sí           |
| Hold       | Sí           |
| ACW        | Sí           |
| Ring       | Configurable |
| Transfer   | Configurable |

---

## 7.4 ASA (Average Speed of Answer)

Tiempo promedio de espera antes de atención.

### Fórmula

ASA = \frac{Total\ Queue\ Wait\ Time}{Answered\ Contacts}

---

## 7.5 SLA (Service Level)

Porcentaje de interacciones atendidas dentro del umbral definido.

### Fórmula

SLA = \frac{Answered\ Within\ Threshold}{Offered - Short\ Abandons}

### Consideraciones

* excluir short abandons
* configurable por canal
* configurable por cola

---

## 7.6 Shrinkage

Tiempo pagado no productivo.

### Fórmula

Shrinkage = \frac{Paid\ Time - Productive\ Time}{Paid\ Time}

### Incluye

* vacaciones
* almuerzo
* reuniones
* capacitación
* ausencias
* incidencias técnicas

---

# 8. Producción Operativa

---

## 8.1 Llamadas Atendidas

Cantidad de llamadas completadas.

---

## 8.2 Chats Atendidos

Cantidad de interacciones digitales completadas.

---

## 8.3 Llamadas Ofrecidas

Total de llamadas recibidas.

---

## 8.4 Llamadas en Cola

Interacciones que esperaron agente disponible.

---

## 8.5 Abandono

Interacciones desconectadas antes de atención.

### Métricas asociadas

| Métrica           | Descripción                        |
| ----------------- | ---------------------------------- |
| Abandoned Calls   | Total abandonadas                  |
| Abandon Wait Time | Tiempo previo al abandono          |
| Short Abandons    | Abandonos cortos excluidos del SLA |

---

## 8.6 Rellamadores

Clientes que intentan nuevamente contactar dentro de una ventana temporal configurable.

### Ejemplo recomendado

```text
Mismo ANI dentro de 24 horas
```

---

# 9. Reporting y Analytics

---

## 9.1 Métricas Agregadas (`daily_employee_metrics`)

Tabla consolidada para reporting rápido y dashboards.

### Métricas sugeridas

| Campo             | Descripción       |
| ----------------- | ----------------- |
| scheduled_minutes | Tiempo programado |
| worked_minutes    | Tiempo trabajado  |
| adherence         | Adherencia        |
| occupancy         | Occupancy         |
| utilization       | Utilization       |
| handled_contacts  | Producción        |
| late_minutes      | Tardanzas         |

---

## 9.2 Snapshots Publicados

Los horarios publicados deben ser inmutables.

### Objetivo

Permitir reconstruir:

* qué se programó
* qué se modificó
* quién modificó
* cuándo ocurrió

---

## 9.3 Auditoría

Toda operación crítica debe registrar:

| Campo     | Descripción         |
| --------- | ------------------- |
| actor     | Usuario responsable |
| entity    | Entidad afectada    |
| action    | Acción ejecutada    |
| before    | Estado previo       |
| after     | Estado nuevo        |
| timestamp | Fecha/hora          |

---

# 10. KPIs y Scorecards

KPIs derivados utilizados para rankings y desempeño.

---

## 10.1 KPIs Soportados

| KPI              | Descripción            |
| ---------------- | ---------------------- |
| Adherence        | Cumplimiento operativo |
| Attendance       | Asistencia             |
| QA Score         | Calidad                |
| AHT              | Tiempo promedio        |
| Occupancy        | Saturación             |
| SLA Contribution | Impacto operacional    |

---

## 10.2 Scorecards

Modelo ponderado de evaluación.

### Componentes

| KPI          | Peso         |
| ------------ | ------------ |
| Attendance   | Configurable |
| Adherence    | Configurable |
| QA           | Configurable |
| AHT          | Configurable |
| Productivity | Configurable |

---

# 11. Lo Que Nos Falta

La plataforma actualmente cubre:

* scheduling
* attendance
* adherence
* reporting operacional

Todavía faltan capacidades WFM enterprise reales.

---

## 11.1 Forecasting

No existe motor de pronóstico.

### Faltante

* forecast de volumen
* demanda por intervalo
* forecast por canal
* tendencias históricas
* estacionalidad

---

## 11.2 Staffing Requirements

No existe cálculo automático de necesidad operativa.

### Faltante

* Erlang C
* staffing requerido
* staffing gap
* occupancy target
* shrinkage planning

---

## 11.3 Cobertura Intradía

Falta análisis dinámico de cobertura.

### Necesario

* cobertura por intervalo
* impacto de ausencias
* impacto de overtime
* simulaciones operativas

---

## 11.4 Skill-Based Routing

No existe modelo de habilidades.

### Faltante

* skills
* proficiency
* multi-skill staffing
* queue affinity

---

## 11.5 Forecast Accuracy

No existe comparación entre forecast y realidad.

### Necesario

* MAPE
* forecast bias
* variance analysis

---

## 11.6 Calidad y Experiencia

Faltan indicadores CX/QA.

### Faltante

* CSAT
* NPS
* FCR
* QA evaluations
* sentiment analysis

---

## 11.7 Motor Analítico Intervalado

Gran parte del sistema sigue orientado a agregados diarios.

### Necesario

* analytics por 15m
* snapshots intervalados
* series temporales reales
* métricas intradía

---

## 11.8 Arquitectura Histórica Inmutable

Aún falta consolidar:

* snapshots completos
* versionado operativo
* reconstrucción temporal exacta
* auditoría analítica consistente

---

## 11.9 Optimización Operacional

Todavía no existe:

* sugerencia automática de horarios
* detección de understaffing
* detección de overstaffing
* recomendaciones operativas
* alertas predictivas

---

## 11.10 Workforce Intelligence

Falta capa analítica avanzada.

### Futuro posible

* predicción de ausencias
* riesgo de burnout
* anomalías operativas
* scoring automático
* optimización de staffing mediante IA
