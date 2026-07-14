# PRD — QualityModule (Sistema de Evaluación de Llamadas)

**Módulo:** `app/Modules/QualityModule`
**Proyecto:** Horarios WFM (Monolito Modular Laravel)
**Versión del documento:** 1.0
**Fecha:** Julio 2026

---

## 1. Resumen Ejecutivo

Módulo Laravel para que evaluadores del área de calidad registren y administren evaluaciones de llamadas de agentes del contact center. Cada llamada se evalúa contra un conjunto de criterios que varían según la cola de atención (trámite, cancelación, farmacia, etc.). Los criterios cambian periódicamente y debe preservarse qué versión se aplicó en cada evaluación. Se implementa como un módulo autónomo dentro de `app/Modules/QualityModule/`, integrado al monólito modular existente.

---

## 2. Objetivos del Negocio

1. **Estandarizar** la medición de calidad en las 11 colas de atención.
2. **Trazabilidad histórica**: saber exactamente qué criterios y puntajes se usaron en cada evaluación, aunque los criterios hayan cambiado después.
3. **Reducir errores** eliminando datos hardcodeados (personal, criterios) y migrándolos a la base de datos.
4. **Auditabilidad**: registrar quién evaluó, quién calibró, quién dio feedback, y cuándo.

---

## 3. Usuarios y Roles

| Rol | Descripción |
|---|---|
| **Evaluador** | Califica llamadas de operadores usando los formularios por cola. |
| **Coordinador** | Supervisa evaluaciones, puede calibrar (re-asignar puntaje) y agregar feedback. |
| **Supervisor** | Visualiza evaluaciones de su equipo. |
| **Operador** | Consulta sus evaluaciones y feedback (futuro). |
| **Administrador** | Gestiona usuarios, roles, y mantiene los criterios de evaluación. |

Los roles se implementan con **Spatie Laravel Permission**. El super-admin del proyecto (rol `admin`) tiene acceso total vía `Gate::before()`.

---

## 4. Requerimientos Funcionales

### Módulo 1 — Autenticación

| ID | Descripción | Prioridad |
|---|---|---|
| RF-01 | El sistema autentica usuarios usando el guard `web` de Laravel Fortify (ya implementado en el proyecto). | Alta |
| RF-02 | La verificación de email y 2FA son requisitos del proyecto base, no del módulo. | Alta |

### Módulo 2 — Mantenimiento de Colas y Criterios

| ID | Descripción | Prioridad |
|---|---|---|
| RF-06 | CRUD de colas de atención vía panel Livewire con `<flux:table>`. | Alta |
| RF-07 | CRUD de criterios de evaluación con versionado automático. | Alta |
| RF-08 | Al modificar un criterio existente, `CreateCriteriaVersionAction` crea una nueva versión sin alterar las anteriores. | Alta |
| RF-09 | Asignación de criterios versionados a colas mediante tabla `queue_criteria`. | Alta |
| RF-10 | Activación/desactivación de criterios dentro de una cola sin eliminar histórico. | Alta |
| RF-11 | Un criterio puede compartirse entre varias colas. | Media |
| RF-12 | CRUD de banderas rojas con penalización. | Alta |

### Módulo 3 — Evaluación de Llamadas

| ID | Descripción | Prioridad |
|---|---|---|
| RF-13 | El evaluador selecciona cola, empleado evaluado y fecha/hora de llamada. Los clips individuales provienen del sistema de corte de videos (`scripts/videoparser/`) que segmenta grabaciones de 30 min en llamadas individuales mediante análisis de audio. | Alta |
| RF-13.1 | La evaluación puede opcionalmente asociarse a un `processed_clip` existente vía FK `clip_id`, permitiendo al evaluador reproducir el clip mientras evalúa. | Alta |
| RF-14 | El formulario Livewire carga dinámicamente los criterios activos de la cola. | Alta |
| RF-15 | Cada criterio se marca como "Cumple" (puntaje completo) o "No cumple" (0) mediante toggle. | Alta |
| RF-16 | Selección de banderas rojas con motivos predefinidos. | Alta |
| RF-17 | El score total se calcula automáticamente en el frontend (Livewire) y se valida en el Action. | Alta |
| RF-18 | Observaciones con contador de caracteres (máx 2500). | Alta |
| RF-19 | La evaluación congela la versión de cada criterio al guardarse (FK a `criteria_versions`). | Alta |
| RF-20 | Una vez enviada, la evaluación solo puede modificarse vía calibración. | Media |

### Módulo 4 — Feedback

| ID | Descripción | Prioridad |
|---|---|---|
| RF-21 | El coordinador/supervisor agrega feedback a una evaluación existente. | Alta |
| RF-22 | El feedback incluye observaciones, fecha y hora automática. | Alta |
| RF-23 | Una evaluación puede tener múltiples entradas de feedback (1:N). | Media |

### Módulo 5 — Calibración

| ID | Descripción | Prioridad |
|---|---|---|
| RF-24 | El coordinador calibra una evaluación: modifica el score total y agrega observación. | Alta |
| RF-25 | Se registra en `CalibrationLog`: score anterior, nuevo, quién calibró y cuándo. | Alta |
| RF-26 | La calibración no altera los puntajes individuales por criterio, solo el score total. | Media |

### Módulo 6 — Consultas y Reportes

| ID | Descripción | Prioridad |
|---|---|---|
| RF-27 | Vista general con `<flux:table>` server-side, búsqueda y exportación a Excel. | Alta |
| RF-28 | Filtros por rango de fechas, cola, evaluador, operador. | Alta |
| RF-29 | Indicador visual si una evaluación tiene feedback y/o calibración. | Media |
| RF-30 | Historial de versiones de un criterio (vista de solo lectura). | Baja |
| RF-31 | Exportación a Excel vía Laravel Excel (Spartner). | Media |

---

## 5. Requerimientos No Funcionales

| ID | Descripción |
|---|---|
| RNF-01 | **Seguridad**: autenticación con Fortify; todas las queries vía Eloquent con prepared statements. |
| RNF-02 | **Integridad referencial**: migraciones con FK a nivel de BD (ULIDs). |
| RNF-03 | **Aislamiento**: nombres de tablas con prefijo `quality_` para evitar colisiones con otros módulos del proyecto. |
| RNF-04 | **Mantenibilidad**: cero datos hardcodeados; todo seed desde migraciones. |
| RNF-05 | **Auditabilidad**: cada modificación registrada vía AuditModule del proyecto o eventos con timestamps. |
| RNF-06 | **Rendimiento**: histórico (30K+ registros) en < 3s con eager loading + paginación server-side. |
| RNF-07 | **Aislamiento**: el módulo se habilita/deshabilita desde `config/modules.php` sin afectar el resto del proyecto. |
| RNF-08 | **Consistencia**: todas las PKs y FKs usan ULIDs, consistentes con el BaseModel del proyecto. |

---

## 6. Casos de Uso

### CU-01: Evaluar llamada

```
Actor:      Evaluador
Precondición: Sesión iniciada, rol 'quality-evaluator', cola con criterios activos.
             El sistema de corte de videos (scripts/videoparser/) ya procesó
             la grabación y generó clips individuales en processed_clips.
Flujo:
  1. GET /quality/evaluaciones/crear?queue=CM-Tr → EvaluationForm Livewire.
  2. El componente carga criterios desde CriteriaRepository::getActiveByQueue().
  3. Evaluador selecciona empleado (Employee desde PersonnelModule). El formulario
     muestra los processed_clips disponibles para ese empleado en la fecha elegida.
  4. Evaluador selecciona un clip (opcional) y completa los puntajes.
  5. Submit → StoreEvaluationAction::execute(CreateEvaluationDTO $dto).
  6. Action: calcula score, crea Evaluation con clip_id opcional, bulk insert
     EvaluationScores y RedFlags en transacción.
  7. Event EvaluationCreated es disparado.
  8. Redirige a /quality/evaluaciones con flash message.
Postcondición: Evaluación persistida con versiones de criterios congeladas.
               Si se asoció un clip, el evaluador puede reproducirlo desde el detalle.
```

### CU-02: Agregar feedback

```
Actor:      Coordinador, Supervisor
Precondición: Evaluación existe y está activa.
Flujo:
  1. GET /quality/evaluaciones/{id}/feedback → FeedbackForm Livewire.
  2. Usuario escribe observaciones y submit.
  3. StoreFeedbackAction::execute() guarda en Feedback.
  4. Event FeedbackAdded.
Postcondición: Feedback registrado.
```

### CU-03: Calibrar evaluación

```
Actor:      Coordinador
Precondición: Evaluación existe.
Flujo:
  1. GET /quality/evaluaciones/{id}/calibrar → CalibrationForm Livewire.
  2. Coordinador ingresa nuevo score y observación.
  3. StoreCalibrationAction: registra en CalibrationLog, actualiza Evaluation.score.
  4. Event CalibrationCreated.
Postcondición: Calibración registrada.
```

### CU-04: Crear/editar criterio

```
Actor:      Administrador
Precondición: Rol quality-admin.
Flujo:
  1. GET /quality/criterios → CriteriaList Livewire.
  2. Nuevo: CreateCriteriaAction::execute() → crea Criteria + CriteriaVersion v1.
  3. Editar: CreateCriteriaVersionAction::execute() → cierra versión anterior, crea nueva.
  4. AssignToQueueAction asigna el criteria_version a una Queue.
Postcondición: Nueva versión del criterio. Evaluaciones anteriores intactas.
```

### CU-05: Consultar histórico

```
Actor:      Evaluador, Coordinador, Supervisor, Administrador
Precondición: Sesión iniciada.
Flujo:
  1. GET /quality/evaluaciones → EvaluationIndex Livewire con <flux:table>.
  2. Server-side paginate con filtros (fechas, cola, evaluador, empleado).
  3. Export a Excel vía Laravel Excel.
  4. Click en fila → GET /quality/evaluaciones/{id} → EvaluationDetail Livewire.
Postcondición: Datos visibles.
```

---

## 7. Reglas de Negocio

| ID | Regla |
|---|---|
| RN-01 | Una evaluación con `deleted_at` no null no aparece en el histórico pero persiste en BD. |
| RN-02 | El score total es siempre la suma de `evaluation_scores.puntaje_obtenido`, salvo que haya calibración. |
| RN-03 | No se puede eliminar una evaluación si tiene feedback o calibración asociados. |
| RN-04 | Un criterio no puede desasignarse de una cola si existen evaluation_scores que lo referencian. |
| RN-05 | El rango de hora de llamada debe estar entre 06:00 y 19:00 (validación en Form Request). |
| RN-06 | `employee_id` referencia a `employees` (PersonnelModule, tabla pública — ADR-007). |
| RN-07 | `evaluator_id` referencia a `users` (CoreModule, tabla pública — ADR-007). |

---

## 8. Glosario

| Término | Definición |
|---|---|
| **Cola** (Queue) | Tipo de atención (Citas Médicas, Farmacia, SIPE, etc.). |
| **Criterio** (Criteria) | Aspecto evaluable de la llamada, con versiones inmutables. |
| **Versión de criterio** (CriteriaVersion) | Snapshot inmutable del texto y puntaje en un momento dado. |
| **Bandera Roja** (RedFlagCriteria) | Falta grave con penalización. |
| **Calibración** (CalibrationLog) | Ajuste del score total por un coordinador. |
| **Feedback** (Feedback) | Retroalimentación del coordinador/supervisor. |
| **Employee** | Agente del contact center, modelo del PersonnelModule. |
| **User** | Usuario del sistema (evaluador, coordinador), modelo del CoreModule. |
| **ProcessedClip** | Clip de video/audio individual generado por `scripts/videoparser/` a partir de una grabación de 30 min. Cada clip corresponde a una llamada detectada mediante análisis de audio y correlacionada con un `CallRecord`. |
