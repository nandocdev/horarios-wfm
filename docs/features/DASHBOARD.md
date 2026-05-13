# Dashboard Inicial — Sistema WFM + Operaciones Realtime

## Objetivo

Construir un dashboard operacional útil desde día 1.

No un cementerio de cards inútiles.

El dashboard debe responder 3 preguntas:

```text
1. ¿Tenemos cobertura?
2. ¿La operación está estable?
3. ¿Dónde está el problema ahora mismo?
```

Todo lo demás es ruido visual.

---

# 1. Arquitectura del Dashboard

Debe dividirse en 4 capas:

| Capa           | Objetivo                  |
| -------------- | ------------------------- |
| Executive      | Estado global             |
| Operational    | Estado realtime           |
| WFM            | Planificación y capacidad |
| Administrative | Workflow y ausencias      |

---

# 2. Dashboard Principal (Landing)

## Objetivo

Vista inmediata de salud operacional.

---

# 2.1 Hero KPIs (Top Row) (Completado ✅)

6 métricas máximas.

No más.

---

## Cards Principales

| KPI                 | Tipo           | Prioridad |
| ------------------- | -------------- | --------- |
| Coverage Rate       | realtime       | crítica   |
| Real Time Adherence | realtime       | crítica   |
| Occupancy           | realtime       | crítica   |
| Service Level       | ACD            | crítica   |
| Absenteeism         | administrativo | alta      |
| Shrinkage           | híbrido        | alta      |

---

## Diseño recomendado

```text
[Coverage]
[Adherence]
[Occupancy]
[SL]
[Absenteeism]
[Shrinkage]
```

---

## Comportamiento

Cada card debe incluir:

| Elemento           | Obligatorio |
| ------------------ | ----------- |
| valor actual       | sí          |
| delta vs ayer      | sí          |
| color semaforizado | sí          |
| mini trend         | sí          |
| tooltip fórmula    | sí          |

---

# 3. Sección Realtime Operacional (Completado ✅)

## Objetivo

Detectar degradación operacional inmediata.

---

# 3.1 Estado Global Operación

## Widget

Gauge o barra horizontal.

---

## Métricas

| Métrica        | Fuente |
| -------------- | ------ |
| Calls Waiting  | ACD    |
| Agents Ready   | Cisco  |
| Agents Talking | Cisco  |
| Agents AUX     | Cisco  |
| Agents Offline | Cisco  |
| Queue SLA      | Cisco  |

---

## Problema detectado

Aquí identificas:

```text
- understaff
- aux abuse
- desconexiones
- colapso de cola
```

---

# 3.2 Distribución de Estados ACD

## Tipo

Donut chart.

---

## Estados

| Estado  | Color    |
| ------- | -------- |
| Ready   | verde    |
| Talking | azul     |
| ACW     | naranja  |
| AUX     | amarillo |
| Offline | rojo     |

---

## Error común

No metas 14 estados Cisco.

Agrupa.

O nadie entenderá el dashboard.

---

# 3.3 Top Equipos Críticos

## Tabla

| Team | Coverage | SL | Occupancy | Alerts |
| ---- | -------- | -- | --------- | ------ |

---

## Orden

Peor primero.

Siempre.

---

# 4. Dashboard WFM

---

# 4.1 Cobertura por Intervalo

## Tipo

Heatmap.

---

## Ejes

| Eje | Valor             |
| --- | ----------------- |
| X   | intervalos 30 min |
| Y   | equipos           |

---

## Valores

| Valor    | Significado        |
| -------- | ------------------ |
| verde    | cobertura correcta |
| amarillo | riesgo             |
| rojo     | understaff         |

---

## Esto vale oro

Aquí realmente ves el WFM.

No en cards bonitas.

---

# 4.2 Staffing vs Requerido

## Tipo

Line chart doble.

---

## Series

| Serie           | Fuente          |
| --------------- | --------------- |
| Required Staff  | forecast/manual |
| Scheduled Staff | WFM             |
| Available Staff | realtime        |

---

# 4.3 Shrinkage Breakdown

## Tipo

Stacked bars.

---

## Categorías

| Tipo      | Fuente      |
| --------- | ----------- |
| Lunch     | schedule    |
| Break     | schedule    |
| Leave     | permisos    |
| Absence   | incidencias |
| Coaching  | intraday    |
| AUX Abuse | Cisco       |

---

# 4.4 Adherence Timeline

## Tipo

Timeline por equipo.

---

## Muestra

| Hora  | Adherence |
| ----- | --------- |
| 08:00 | 96%       |
| 09:00 | 82%       |
| 10:00 | 71%       |

---

## Esto permite detectar

```text
- desconexiones masivas
- lunch descontrolado
- fallos ACD
```

---

# 5. Dashboard Administrativo

---

# 5.1 Incidencias del Día

## Tabla

| Agente | Tipo | Inicio | Duración | Estado |
| ------ | ---- | ------ | -------- | ------ |

---

# 5.2 Solicitudes Pendientes

## Cards

| Tipo          | Cantidad |
| ------------- | -------- |
| Permisos      | X        |
| Cambios turno | X        |
| Vacaciones    | X        |

---

# 5.3 Coordinadores con Más Retrasos

## Tabla

| Coordinador | Avg Approval | Pending |
| ----------- | ------------ | ------- |

---

# 6. Dashboard Ejecutivo

Minimalista.

Los directores NO quieren 40 métricas.

---

# 6.1 KPIs Ejecutivos

| KPI         | Objetivo      |
| ----------- | ------------- |
| SLA Global  | estabilidad   |
| Coverage    | capacidad     |
| Shrinkage   | eficiencia    |
| AHT         | productividad |
| Absenteeism | disciplina    |
| Occupancy   | presión       |

---

# 6.2 Tendencias 30 días

## Tipo

Line chart.

---

## Series

| Serie     |
| --------- |
| SLA       |
| Adherence |
| Shrinkage |
| Occupancy |

---

# 6.3 Riesgos Operativos

## Lista priorizada

| Riesgo         | Severidad |
| -------------- | --------- |
| Queue overload | alta      |
| Understaff     | crítica   |
| AUX abuse      | media     |
| High absence   | alta      |

---

# 7. Dashboard por Supervisor

## Objetivo

Gestión piso realtime.

---

# Widgets

| Widget                | Prioridad |
| --------------------- | --------- |
| agentes desconectados | crítica   |
| adherence equipo      | crítica   |
| agentes en AUX        | alta      |
| cola esperando        | crítica   |
| incidentes abiertos   | media     |

---

# 8. Dashboard por Agente

Simple.

No sobrecargar.

---

# Widgets

| Widget               |
| -------------------- |
| horario actual       |
| adherence hoy        |
| productividad        |
| tiempo AUX           |
| actividades intradía |
| permisos pendientes  |

---

# 9. Alertas Inteligentes

Esto es MÁS importante que el dashboard.

---

# 9.1 Alertas críticas

| Evento            | Trigger            |
| ----------------- | ------------------ |
| SLA crítico       | < 60%              |
| Occupancy extrema | > 95%              |
| Adherence baja    | < 80%              |
| Coverage baja     | < 85%              |
| AUX abuse         | > umbral           |
| Queue overload    | llamadas esperando |

---

# 9.2 Canales

| Canal              |
| ------------------ |
| dashboard          |
| toast              |
| email              |
| websocket          |
| Teams/Slack futuro |

---

# 10. Arquitectura Técnica Recomendada

---

# Backend

```text
Laravel
PostgreSQL
Redis
Laravel Reverb/WebSockets
```

---

# Estrategia de Datos

## Separar

| Tipo        | Frecuencia |
| ----------- | ---------- |
| realtime    | segundos   |
| operacional | minutos    |
| ejecutivo   | horas      |
| histórico   | diario     |

---

# Error clásico

NO recalcular KPIs complejos en cada request.

---

# Debes tener

| Componente         | Obligatorio |
| ------------------ | ----------- |
| materialized views | sí          |
| aggregation tables | sí          |
| cached snapshots   | sí          |
| realtime streams   | sí          |

---

# 11. Arquitectura Visual Recomendada

---

# Tecnología

Ya estás en:

* Laravel
* Livewire
* FluxUI
* Tailwind

Correcto.

No metas React innecesariamente.

---

# Librerías recomendadas

| Uso       | Librería             |
| --------- | -------------------- |
| charts    | ApexCharts           |
| realtime  | Echo/Reverb          |
| tables    | Tanstack-like simple |
| heatmaps  | ApexCharts           |
| timelines | custom               |

---

# 12. Roadmap Correcto

---

# Fase 1

Operación visible.

```text
coverage
adherence
occupancy
SL
```

---

# Fase 2

Análisis WFM.

```text
shrinkage
forecast
interval staffing
```

---

# Fase 3

Optimización.

```text
forecasting ML
simulation
auto staffing
```

---

# 13. Lo Que NO Debes Hacer

---

## Error 1

```text
100 cards pequeñas
```

Basura visual.

---

## Error 2

```text
charts sin acciones
```

Si no genera decisión operacional:

```text
elimínalo.
```

---

## Error 3

```text
realtime falso cada 30 segundos
```

Usa websocket.

---

# 14. Dashboard Inicial REALISTA para MVP

## Día 1

Construye SOLO:

---

## Row 1
* Coverage ✅
* Adherence ✅
* Occupancy ✅
* SLA ✅

---

## Row 2
* Queue state ✅
* Team criticality (Pendiente)
* Agents realtime states ✅

---

## Row 3
* Absences ✅
* Pending approvals ✅
* Intraday activities (Pendiente)
