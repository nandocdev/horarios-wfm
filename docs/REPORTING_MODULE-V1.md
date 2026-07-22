Sugiero una modificacion al modulo de Reportes:

---

## 1. Reporte de Asistencia

**Objetivo**: medir cumplimiento de horario programado vs asistencia real.

Propongo que en realidad sean **4 sub-reportes** (o 1 reporte con 4 pestañas/vistas), porque mezclarlos en una sola tabla los hace ilegibles:

| Sub-reporte | Columnas propuestas |
|---|---|
| **Ausencias** | Agente, Fecha, Turno programado, Tipo de ausencia (justificada/no justificada), Horas perdidas |
| **Tardanzas** | Agente, Fecha, Hora programada, Hora real de login, Minutos de tardanza, Tolerancia aplicada |
| **Permisos** | Agente, Fecha/rango, Tipo de permiso, Estado (aprobado/pendiente), Horas |
| **Vacaciones** | Agente, Rango de fechas, Días tomados, Días disponibles/saldo |
| **Resumen global** | Agente (o agregado por equipo/supervisor), % Asistencia, % Tardanza, Total horas programadas vs trabajadas |

**Preguntas abiertas para ti:**
- ¿El sistema de horarios que manejan ustedes ya clasifica el tipo de ausencia (justificada/no justificada, tipo de permiso), o eso llega de otro sistema (RRHH)?
- ¿"Vacaciones" se gestiona en el mismo módulo de horarios o es un sistema externo (ej. un ERP de RRHH) del que solo consultamos saldo?
- Filtros que asumo necesarios: rango de fechas, supervisor, agente individual, sitio/campaña. ¿Falta alguno (ej. tipo de contrato, turno)?

---

## 2. Reporte de Actividades

**Objetivo**: ver qué actividades (no telefónicas) ejecutó el agente, programadas vs reales.

| Vista | Columnas propuestas |
|---|---|
| **Intradía** | Agente, Fecha, Línea de tiempo (bloques de 15/30 min), Actividad programada vs actividad real por bloque |
| **Por periodo** | Agente (o equipo), Rango de fechas, Tipo de actividad, Horas totales, % Cumplimiento vs programado |

**Preguntas abiertas:**
- ¿"Actividades" incluye break/lunch, o eso vive en asistencia? Necesito el catálogo exacto de tipos de actividad que manejan (coaching, capacitación, backoffice, reunión, etc.).
- ¿La actividad programada sale del mismo sistema de horarios, o hay un módulo de "intraday management" separado que reasigna actividades en el día?

---

## 3. Volumen de Llamadas (por cola)

**Objetivo**: medir el tráfico y resultado de llamadas por cola/skill.

| Columna | Notas |
|---|---|
| Cola/Skill | |
| Intervalo (15/30 min, o agregado diario/semanal) | |
| Ofrecidas | |
| Atendidas | |
| Abandonadas | con umbral de "abandono de cortesía" a definir (ej. <5s no cuenta) |
| % Abandono | |
| Tiempo espera promedio (atendidas) | ASA |
| Tiempo espera promedio (abandonadas) | separado del ASA |
| Máximo tiempo de espera | |
| Mínimo tiempo de espera | |
| AHT | confirmar si incluye ACW o se reporta aparte |
| NDS/SLA | confirmar umbral (ej. 80/20) y si se calcula sobre ofrecidas u ofrecidas-cortesía |

**Preguntas abiertas:**
- ¿Cuál es el umbral de SLA que manejan hoy (ej. "80% en 20 segundos")? ¿Es el mismo para todas las colas o varía?
- ¿Manejan el concepto de "abandono de cortesía" (umbral bajo tipo 3-5s que no cuenta como abandono real)?
- ¿AHT en su operación incluye ACW (after-call work) o lo reportan como métrica separada?

---

## 4. Desempeño de Agentes

**Objetivo**: cruzar tiempo/estado del agente con resultado de llamadas, por agente.

| Columna | Notas |
|---|---|
| Agente | |
| Tiempo en servicio | disponible + en llamada + ACW |
| Tiempo en actividades | no telefónico |
| Tiempo disponible | solo "Available" sin llamada |
| Ocupación | tiempo en llamada+ACW / tiempo logueado productivo |
| Productividad | tiempo productivo / tiempo programado |
| Llamadas atendidas | |
| AHT individual | |
| Cruce llamadas vs AHT | para identificar outliers (agentes muy rápidos con baja calidad, o muy lentos) |


---

Propuesta de Menù

```
Reportes
├── Asistencia
│   ├── Ausentismo
│   ├── Tardanzas
│   ├── Permisos
│   ├── Vacaciones
│   └── Global
├── Actividades
│   ├── Intradía
│   └── Por Periodo
├── Volumen
│   ├── Por Cola
│   ├── Por Intervalo
│   └── Consolidado
└── Desempeño
    ├── Por Agente
    ├── Por Equipo/Supervisor
    └── Ranking (Atendidas vs AHT)
```

Antes de pasarlo a tabla de rutas, un par de correcciones a la estructura que propones:

1. **"Volumen" necesita al menos 2 niveles, no 1.** Si dejas "Volumen" como hoja única, vas a terminar con un solo reporte gigante con 15 columnas y selector de granularidad adentro (intervalo/diario/semanal) mezclado con selector de vista (por cola/consolidado). Eso es exactamente el tipo de reporte ilegible que te advertí en el diseño funcional. Sepáralo en sub-vistas igual que Asistencia.

2. **"Desempeño" también necesita esa separación.** Un supervisor entra buscando "mi equipo completo rankeado", un agente (si tiene acceso) entra buscando "mi propio detalle". Si es un solo reporte con filtro de agente opcional, terminas con una UI que no comunica el caso de uso principal.

## Tabla de rutas + permisos (esto es lo que realmente define el árbol en el sistema)

No modeles el menú como un array hardcodeado en un Blade/Vue — modélalo como datos, porque vas a necesitar filtrarlo por permiso de rol (agente/supervisor/gerente/admin no ven lo mismo).

| Nodo | Slug/ruta | Permiso (Gate/Policy) | Nivel mínimo |
|---|---|---|---|
| Reportes | `reports` | `reports.view` | Supervisor+ |
| Asistencia | `reports.attendance` | `reports.attendance.view` | Supervisor+ |
| — Ausentismo | `reports.attendance.absences` | mismo padre | |
| — Tardanzas | `reports.attendance.tardiness` | mismo padre | |
| — Permisos | `reports.attendance.leaves` | mismo padre | |
| — Vacaciones | `reports.attendance.vacations` | mismo padre | |
| — Global | `reports.attendance.summary` | mismo padre | |
| Actividades | `reports.activities` | `reports.activities.view` | Supervisor+ |
| — Intradía | `reports.activities.intraday` | | |
| — Por Periodo | `reports.activities.period` | | |
| Volumen | `reports.volume` | `reports.volume.view` | Supervisor+ |
| — Por Cola | `reports.volume.queue` | | |
| — Por Intervalo | `reports.volume.interval` | | |
| — Consolidado | `reports.volume.summary` | | |
| Desempeño | `reports.performance` | `reports.performance.view` | Supervisor+ |
| — Por Agente | `reports.performance.agent` | `reports.performance.agent.view` | ver nota |
| — Por Equipo | `reports.performance.team` | `reports.performance.team.view` | Supervisor+ |
| — Ranking | `reports.performance.ranking` | `reports.performance.ranking.view` | Supervisor+ |

**Nota crítica de permisos**: "Por Agente" dentro de Desempeño es el único nodo que probablemente necesite lógica de scope, no solo permiso binario — un agente viendo "Por Agente" debe ver **solo su propio registro**, un supervisor debe ver **su equipo**, un gerente **todo**. Esto no lo resuelves con un permiso "puede ver/no puede ver", lo resuelves con una Policy que inyecta el scope según el rol autenticado (`AgentPerformancePolicy::view()` que retorna un query scope, no un booleano). Si intentas resolver esto con permisos planos vas a terminar con roles duplicados tipo "supervisor-solo-su-equipo" en vez de una regla de negocio clara.

