# Roadmap — QualityModule

> Fases, sprints y tareas para la construcción del módulo de calidad.

---

## Fase 1: Fundación
> Base de datos, modelos, migrations, service provider, seeders.

### Sprint 1.1 — Infraestructura

| Tarea                                                     | Descripción                                           | Dependencias                   |
| --------------------------------------------------------- | ----------------------------------------------------- | ------------------------------ |
| Crear estructura del módulo                               | Crear estructura del módulo                           | —                              |
| Registrar `ModuleServiceProvider` en `config/modules.php` | Habilitar carga del módulo                            | —                              |
| Migración `quality_queues`                                | Tabla de catálogo de colas (11 colas)                 | —                              |
| Migración `quality_criteria`                              | Tabla de identidad de criterios                       | —                              |
| Migración `quality_criteria_versions`                     | Versionado inmutable de criterios                     | criteria                       |
| Migración `quality_queue_criteria`                        | Asignación cola ↔ criterio versionado                 | queues, criteria_versions      |
| Migración `quality_red_flag_criteria`                     | Banderas rojas con penalización                       | —                              |
| Migración `quality_evaluations`                           | Cabecera de evaluación (ULIDs, FKs a employees/users) | queues                         |
| Migración `quality_evaluation_scores`                     | Puntajes individuales por criterio                    | evaluations, criteria_versions |
| Migración `quality_evaluation_red_flags`                  | Banderas rojas de la evaluación                       | evaluations, red_flag_criteria |
| Migración `quality_feedback`                              | Retroalimentación (FK → evaluations)                  | evaluations                    |
| Migración `quality_calibration_log`                       | Historial de calibraciones (FK → evaluations)         | evaluations                    |
| Migration: agregar `clip_id` a `quality_evaluations`      | FK → `processed_clips` (videoparser)                  | evaluations, videoparser       |

### Sprint 1.2 — Modelos + ServiceProvider

| Tarea                                 | Descripción                                            | Dependencias |
| ------------------------------------- | ------------------------------------------------------ | ------------ |
| `Models/Queue.php`                    | Modelo `Queue` extends `BaseModel`                     | migrations   |
| `Models/Criteria.php`                 | Modelo `Criteria`                                      | migrations   |
| `Models/CriteriaVersion.php`          | Modelo `CriteriaVersion` con scope `active()`          | migrations   |
| `Models/QueueCriteria.php`            | Modelo `QueueCriteria`                                 | migrations   |
| `Models/RedFlagCriteria.php`          | Modelo `RedFlagCriteria`                               | migrations   |
| `Models/Evaluation.php`               | Modelo `Evaluation` (SoftDeletes, FKs employees/users) | migrations   |
| `Models/EvaluationScore.php`          | Modelo `EvaluationScore`                               | migrations   |
| `Models/EvaluationRedFlag.php`        | Modelo `EvaluationRedFlag`                             | migrations   |
| `Models/Feedback.php`                 | Modelo `Feedback`                                      | migrations   |
| `Models/CalibrationLog.php`           | Modelo `CalibrationLog`                                | migrations   |
| `Providers/ModuleServiceProvider.php` | Bindings + carga de rutas/vistas/migrations + policies | models       |
| `Observers/EvaluationObserver.php`    | SoftDeletes + eventos de auditoría                     | evaluation   |

### Sprint 1.3 — Seeders + Enums

| Tarea                                      | Descripción                                            | Dependencias      |
| ------------------------------------------ | ------------------------------------------------------ | ----------------- |
| `Enums/QueueCode.php`                      | Enum con códigos de cola (CM-Tr, CM-Canc, etc.)        | —                 |
| `Enums/EvaluationStatus.php`               | Enum `activa`, `eliminada`                             | —                 |
| `Enums/QualityRole.php`                    | Enum `evaluador`, `coordinador`, `supervisor`, `admin` | —                 |
| `Database/Seeders/QualityModuleSeeder.php` | Seed de 11 colas + roles Spatie + criterios de ejemplo | migrations, enums |
| Comando `quality:seed`                     | Artisan command para ejecutar seeder manualmente       | seeders           |

---

## Fase 2: Núcleo de Evaluación
> Actions, DTOs, formulario de evaluación, lógica transaccional.

### Sprint 2.1 — DTOs + Actions

| Tarea                                     | Descripción                                             | Dependencias |
| ----------------------------------------- | ------------------------------------------------------- | ------------ |
| `DTOs/CreateEvaluationDTO.php`            | DTO inmutable con datos de evaluación (Spatie Data)     | —            |
| `DTOs/CreateFeedbackDTO.php`              | DTO para feedback                                       | —            |
| `DTOs/CreateCalibrationDTO.php`           | DTO para calibración                                    | —            |
| `DTOs/CriteriaAssignmentDTO.php`          | DTO para asignación cola-criterio                       | —            |
| `Actions/StoreEvaluationAction.php`       | Transacción: create Evaluation + bulk scores + redflags | DTOs, models |
| `Actions/StoreFeedbackAction.php`         | Transacción: create Feedback                            | DTOs, models |
| `Actions/StoreCalibrationAction.php`      | Transacción: create CalibrationLog + update score       | DTOs, models |
| `Actions/DeleteEvaluationAction.php`      | Soft delete con validación RN-03                        | models       |
| `Actions/CreateCriteriaAction.php`        | Crear criteria + criteria_version v1                    | DTOs, models |
| `Actions/CreateCriteriaVersionAction.php` | Cerrar versión anterior + crear nueva                   | DTOs, models |

### Sprint 2.2 — Events + Listeners

| Tarea                                      | Descripción                  | Dependencias |
| ------------------------------------------ | ---------------------------- | ------------ |
| `Events/EvaluationCreated.php`             | Evento de dominio            | actions      |
| `Events/FeedbackAdded.php`                 | Evento de dominio            | actions      |
| `Events/CalibrationCreated.php`            | Evento de dominio            | actions      |
| `Events/CriteriaVersionCreated.php`        | Evento de dominio            | actions      |
| `Listeners/SendEvaluationNotification.php` | Notificar coordinador        | events       |
| `Listeners/UpdateQueueScoreAverages.php`   | Recalcular promedios de cola | events       |

### Sprint 2.3 — Repositories

| Tarea                                           | Descripción                                                      | Dependencias |
| ----------------------------------------------- | ---------------------------------------------------------------- | ------------ |
| `Contracts/EvaluationRepositoryInterface.php`   | Contrato en Shared                                               | —            |
| `Repositories/EloquentEvaluationRepository.php` | Queries paginadas con filtros (fecha, cola, evaluador, empleado) | models       |
| `Contracts/CriteriaRepositoryInterface.php`     | Contrato en Shared                                               | —            |
| `Repositories/EloquentCriteriaRepository.php`   | Queries: activos por cola, versionado                            | models       |
| Bindings en `ModuleServiceProvider::register()` | Singleton bindings para ambos repos                              | repos        |

### Sprint 2.4 — Form Requests

| Tarea                                       | Descripción                                              | Dependencias |
| ------------------------------------------- | -------------------------------------------------------- | ------------ |
| `Http/Requests/StoreEvaluationRequest.php`  | Validación: employee_id, queue_id, scores array, callobs | —            |
| `Http/Requests/StoreFeedbackRequest.php`    | Validación: evaluation_id, obsfeed                       | —            |
| `Http/Requests/StoreCalibrationRequest.php` | Validación: evaluation_id, score_nuevo                   | —            |
| `Http/Requests/StoreCriteriaRequest.php`    | Validación: code, criterio_text, puntaje                 | —            |

### Sprint 2.5 — Policies

| Tarea                                                   | Descripción                     | Dependencias |
| ------------------------------------------------------- | ------------------------------- | ------------ |
| `Policies/EvaluationPolicy.php`                         | view, create, delete, calibrate | —            |
| `Policies/CriteriaPolicy.php`                           | view, create, update            | —            |
| `Policies/FeedbackPolicy.php`                           | create                          | —            |
| `Policies/CalibrationPolicy.php`                        | create                          | —            |
| Registro de Policies en `ModuleServiceProvider::boot()` | `Gate::policy()`                | policies     |

---

## Fase 3: UI (Livewire + FluxUI)
> Componentes de interfaz, formularios, listados.

### Sprint 3.1 — Listados y Tablas

| Tarea                                                 | Descripción                                                   | Dependencias    |
| ----------------------------------------------------- | ------------------------------------------------------------- | --------------- |
| `Livewire/EvaluationIndex.php`                        | DataTable con `<flux:table>`, paginación server-side, filtros | repositories    |
| `resources/views/livewire/evaluation-index.blade.php` | Template con flux:table, filtros reactivos                    | EvaluationIndex |
| `Livewire/CriteriaList.php`                           | Listado de criterios con indicador de versión activa          | repositories    |
| `resources/views/livewire/criteria-list.blade.php`    | Template con flux:table inline editable                       | CriteriaList    |
| `Livewire/QueueList.php`                              | Administración de colas                                       | repositories    |
| `resources/views/livewire/queue-list.blade.php`       | Template                                                      | QueueList       |

### Sprint 3.2 — Formularios

| Tarea                                                  | Descripción                                                                   | Dependencias     |
| ------------------------------------------------------ | ----------------------------------------------------------------------------- | ---------------- |
| `Livewire/EvaluationForm.php`                          | Formulario full-page: selección de cola → carga criterios → puntajes → submit | actions          |
| `resources/views/livewire/evaluation-form.blade.php`   | Template con flux:checkbox para criterios, textarea para callobs              | EvaluationForm   |
| `Livewire/EvaluationDetail.php`                        | Detalle de evaluación con scores, redflags, reproducción de clip              | models           |
| `resources/views/livewire/evaluation-detail.blade.php` | Template con scores readonly, botón de clip                                   | EvaluationDetail |
| `Livewire/FeedbackForm.php`                            | Formulario de feedback                                                        | actions          |
| `resources/views/livewire/feedback-form.blade.php`     | Template con textarea                                                         | FeedbackForm     |
| `Livewire/CalibrationForm.php`                         | Formulario de calibración                                                     | actions          |
| `resources/views/livewire/calibration-form.blade.php`  | Template con input de score                                                   | CalibrationForm  |
| `Livewire/CriteriaForm.php`                            | Crear/editar criterio (con versionado)                                        | actions          |
| `resources/views/livewire/criteria-form.blade.php`     | Template                                                                      | CriteriaForm     |

### Sprint 3.3 — Rutas

| Tarea            | Descripción                                                   | Dependencias        |
| ---------------- | ------------------------------------------------------------- | ------------------- |
| `Routes/web.php` | Todas las rutas con middleware `auth` + `verified` + permisos | livewire components |

---

## Fase 4: Integraciones
> Videoparser, auditoría, reportes, exportación.

### Sprint 4.1 — Videoparser

| Tarea                                                                        | Descripción                                    | Dependencias          |
| ---------------------------------------------------------------------------- | ---------------------------------------------- | --------------------- |
| Verificar que `scripts/videoparser/` escribe `processed_clips` correctamente | Validar flujo de corte de videos               | videoparser existente |
| Agregar FK `clip_id` a `quality_evaluations`                                 | Migration                                      | Fase 1 migrations     |
| Integrar selector de clips en `EvaluationForm`                               | Load `processed_clips` por employee_id + fecha | EvaluationForm        |
| Mostrar reproductor de clip en `EvaluationDetail`                            | `<video>` tag o enlace al archivo              | processed_clips       |

### Sprint 4.2 — Auditoría

| Tarea                                         | Descripción                            | Dependencias    |
| --------------------------------------------- | -------------------------------------- | --------------- |
| Crear listeners de auditoría para cada evento | Usar `AuditModule::log()` del proyecto | events (Fase 2) |

### Sprint 4.3 — Reportes

| Tarea                        | Descripción                                                        | Dependencias  |
| ---------------------------- | ------------------------------------------------------------------ | ------------- |
| `Services/ReportService.php` | Estadísticas de evaluaciones (score promedio por cola, tendencias) | models        |
| Exportación Excel            | Laravel Excel, generación de reporte con filtros                   | ReportService |

### Sprint 4.4 — Jobs

| Tarea                             | Descripción                                      | Dependencias |
| --------------------------------- | ------------------------------------------------ | ------------ |
| `Jobs/RecalculateQueueStats.php`  | Job programado para recalcular promedios de cola | models       |
| Registrar en `routes/console.php` | Programar `->daily()`                            | job          |

---

## Fase 5: Calidad y Pruebas
> Tests, validación, hardening.

### Sprint 5.1 — Tests

| Tarea                                                        | Descripción                                               | Dependencias |
| ------------------------------------------------------------ | --------------------------------------------------------- | ------------ |
| `tests/Feature/QualityModule/EvaluationTest.php`             | Feature tests: crear evaluación, calcular score, redflags | Fase 2       |
| `tests/Feature/QualityModule/CriteriaVersioningTest.php`     | Versionado: crear, editar, mantener histórico             | Fase 2       |
| `tests/Feature/QualityModule/FeedbackTest.php`               | Agregar feedback a evaluación existente                   | Fase 2       |
| `tests/Feature/QualityModule/CalibrationTest.php`            | Calibrar score, verificar CalibrationLog                  | Fase 2       |
| `tests/Feature/QualityModule/SecurityTest.php`               | Rutas protegidas, permisos por rol, SQL injection         | Fase 3       |
| `tests/Feature/QualityModule/VideoparserIntegrationTest.php` | FK a processed_clips, consulta de clips                   | Fase 4       |

### Sprint 5.2 — Hardening

| Tarea                               | Descripción                                   | Dependencias    |
| ----------------------------------- | --------------------------------------------- | --------------- |
| Auditoría de seguridad              | Verificar checklist de SECURITY.md            | todas las fases |
| Validación de carga                 | 30K+ evaluaciones, medir tiempos de respuesta | Fase 3          |
| Cache de criterios activos por cola | Redis para reducir queries repetitivas        | Fase 2          |

---

## Resumen de Dependencias

```
Fase 1 (Fundación)
  └── Fase 2 (Núcleo Evaluación)
        └── Fase 3 (UI)
              └── Fase 4 (Integraciones)
                    └── Fase 5 (Pruebas)
```

**Sprints paralelizables:**
- Sprint 1.1 + 1.2 + 1.3 (todos infra)
- Sprint 2.1 (Actions) + 2.4 (Form Requests) + 2.5 (Policies)
- Sprint 2.2 (Events) + 2.3 (Repositories)
- Sprint 3.1 (Listados) + 3.2 (Formularios) + 3.3 (Rutas)
- Sprint 4.1 (Videoparser) + 4.2 (Auditoría) + 4.3 (Reportes) + 4.4 (Jobs)
- Sprint 5.1 (Tests) + 5.2 (Hardening)

**Estimación:** 5 fases × ~2 sprints por fase = ~10 sprints. Ajustar según velocidad del equipo.
