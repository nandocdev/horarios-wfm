# Análisis técnico del `WfmModule`

## 1) Resumen ejecutivo

`WfmModule` es el núcleo operativo de planificación semanal y ejecución diaria. Su diseño combina:

- **modelo base de turnos** (`schedules`),
- **planificación semanal** (`weekly_schedules`, `weekly_schedule_assignments`, `weekly_team_assignments`),
- **mutaciones operativas** por prioridad (`schedule_overrides`),
- **vista efectiva consolidada** (`employee_effective_schedule`),
- **capas de UI Livewire** para gestión macro (equipos) y micro (agente),
- **acciones y jobs** para publicación, asignación masiva y snapshots.

La base conceptual es sólida y orientada a dominio, pero hay desalineamientos internos (nomenclatura, validaciones incompletas, pruebas legacy) que hoy elevan el riesgo de regresiones.

---

## 2) Arquitectura del módulo (estado actual)

### 2.1 Registro e integración

- El módulo registra rutas, vistas, policies y observer desde su `ModuleServiceProvider`.
- Se observa además un registro dual de componentes Livewire (`schedule.alias` y `wfm::alias`) para compatibilidad.

**Valor:** facilita desacople por dominio y encapsula dependencias del módulo.

### 2.2 Modelo de datos

El módulo define un esquema robusto en PostgreSQL:

- constraints de semana (`week_end_date = start + 6d`),
- `check` de estados,
- unicidad de asignación diaria por empleado,
- rangos temporales `tstzrange` para overrides e incidencias,
- índices GIST para consultas por intersección de rangos,
- vista `employee_effective_schedule` para resolver la “fuente de verdad” final.

**Valor:** buena base para reglas operativas complejas y consultas temporales.

### 2.3 Capa de aplicación

- Actions transaccionales para creación/publicación/asignación.
- Jobs para trabajo asíncrono (`ProcessMassAssignmentJob`, `CompileDailyScheduleSnapshotsJob`).
- Livewire segmentado por capacidades: catálogo, planning, overrides, requests, operaciones y analytics.

**Valor:** separación razonable entre orquestación (Livewire), lógica (Action) y persistencia (Model).

---

## 3) Hallazgos clave (priorizados)

## 3.1 Hallazgos críticos (alta prioridad)

1. **Desalineación entre diseño de grilla y estructura de datos por día**
   - `WeeklyPlanningGrid` carga `grid[team_id] = schedule_id`, pero el dominio de `WeeklyTeamAssignment` es por `team_id + day_of_week`.
   - Esto simplifica visualmente la asignación semanal, pero pierde granularidad (potencial sobrescritura/ambigüedad por día).

2. **Validación incompleta en publicación semanal**
   - `ValidateWeeklyScheduleAction` calcula `agentsWithNoTime` pero no lo agrega al arreglo de errores.
   - Resultado: una condición de negocio aparentemente crítica no bloquea publicación.

3. **Regla imposible / inconsistente en validador**
   - La validación habla de `schedule_id NULL` en asignaciones, pero la migración define `schedule_id` obligatorio (`foreignId` no nullable).
   - Esa validación no puede dispararse en estado normal de DB.

4. **Cobertura de tests posiblemente desactualizada por namespace legado**
   - Existen tests que apuntan a `App\Modules\SchedulingModule\...`, mientras el código real vive en `WfmModule`.
   - Riesgo: falsa sensación de cobertura o tests no ejecutables si no existe alias/compatibilidad adicional.

## 3.2 Hallazgos importantes (media prioridad)

5. **Políticas parcialmente registradas**
   - Existe `AttendanceIncidentPolicy`, pero en el provider no aparece mapeada en `$policies`.
   - Esto puede dejar rutas/componentes dependiendo solo de autorizaciones manuales.

6. **Observador de auditoría dependiente de `auth()` en contexto no interactivo**
   - `WeeklyScheduleAssignmentObserver` registra `changed_by => auth()->id()`.
   - En jobs asíncronos puede quedar `null`, afectando trazabilidad de actor real.

7. **Carga potencialmente costosa en snapshots diarios**
   - `CompileDailyScheduleSnapshotsAction` recorre empleados activos y parsea ranges por regex en PHP.
   - Para alto volumen, convendría empujar más cálculo al motor SQL (agregaciones por rango y duración).

---

## 4) Fortalezas concretas del módulo

1. **Uso correcto de `DB::transaction()`** en acciones críticas.
2. **Diseño temporal avanzado con `tstzrange` + GIST**, adecuado para overlap checks.
3. **Vista efectiva centralizada** que evita dispersar lógica de prioridad en múltiples consultas.
4. **Separación macro/micro en la UX de planificación** (equipo vs agente) con controles jerárquicos.

---

## 5) Recomendaciones accionables

## Semana 1 (impacto rápido)

1. Corregir `ValidateWeeklyScheduleAction` para que `agentsWithNoTime` sí bloquee publicación.
2. Eliminar/ajustar validación imposible de `schedule_id NULL` o volver nullable con regla explícita (elegir una sola estrategia).
3. Revisar `WeeklyPlanningGrid` para persistir estado por día (`grid[team][day]`) o documentar claramente que aplica un mismo turno a todos los días hábiles.
4. Agregar prueba de regresión de publicación con semana inválida.

## Semana 2 (estabilización)

5. Normalizar namespaces de tests (`WfmModule`) y depurar archivos legacy (`SchedulingModule`).
6. Registrar todas las policies usadas por modelos del módulo en el provider.
7. Enriquecer auditoría para jobs: propagar `actor_id` explícito cuando aplica.

## Semana 3+ (performance / escalabilidad)

8. Optimizar snapshots diarios con consultas agregadas en SQL (duraciones por `tstzrange`) y límites por lotes.
9. Definir SLOs del módulo (ej. tiempo máximo para publicar semana, tiempo de asignación masiva por 1k empleados).

---

## 6) Conclusión

`WfmModule` tiene una base arquitectónica competente para un WFM real y aprovecha bien capacidades de PostgreSQL para lógica temporal. El mayor riesgo actual no es conceptual sino de **consistencia interna y hardening**: validaciones incompletas, tests desalineados y pequeñas fisuras de gobernanza técnica.

Con correcciones acotadas y enfocadas (2–3 semanas), el módulo puede pasar de “funcional y prometedor” a “operativamente robusto y predecible” para producción crítica.
