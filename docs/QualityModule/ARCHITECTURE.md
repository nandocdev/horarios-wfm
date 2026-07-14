# Arquitectura — QualityModule

## 1. Stack Tecnológico

| Capa          | Tecnología                            | Componente                                         |
| ------------- | ------------------------------------- | -------------------------------------------------- |
| Frontend      | TailwindCSS 4 + FluxUI 2 + Livewire 4 | Componentes full-page con parámetros de URL        |
| Backend       | Laravel 13                            | Eloquent, Actions, Events, Policies, Form Requests |
| UI Reactiva   | Livewire 4                            | Componentes full-page con `#[Url]` para filtros    |
| Base de datos | PostgreSQL 16                         | Migraciones con ULIDs y FK                         |
| Exportación   | Laravel Excel (Spartner)              | Reportes a XLSX/CSV                                |
| Autorización  | Spatie Laravel Permission 7           | Roles y permisos por recurso                       |

## 2. Estructura del Módulo

```
app/Modules/QualityModule/
├── Actions/
│   ├── CreateCriteriaAction.php
│   ├── CreateCriteriaVersionAction.php
│   ├── DeleteEvaluationAction.php
│   ├── StoreCalibrationAction.php
│   ├── StoreEvaluationAction.php
│   └── StoreFeedbackAction.php
├── Console/Commands/
│   └── SeedQualityData.php                # php artisan quality:seed
├── Database/
│   └── Migrations/
│       ├── 2026_07_01_000001_create_quality_queues_table.php
│       ├── 2026_07_01_000002_create_quality_criteria_table.php
│       ├── 2026_07_01_000003_create_quality_criteria_versions_table.php
│       ├── 2026_07_01_000004_create_quality_queue_criteria_table.php
│       ├── 2026_07_01_000005_create_quality_red_flag_criteria_table.php
│       ├── 2026_07_01_000006_create_quality_evaluations_table.php
│       ├── 2026_07_01_000007_create_quality_evaluation_scores_table.php
│       ├── 2026_07_01_000008_create_quality_evaluation_red_flags_table.php
│       ├── 2026_07_01_000009_create_quality_feedback_table.php
│       └── 2026_07_01_000010_create_quality_calibration_log_table.php
├── DTOs/
│   ├── CreateEvaluationDTO.php
│   ├── CreateFeedbackDTO.php
│   ├── CreateCalibrationDTO.php
│   └── CriteriaAssignmentDTO.php
├── Enums/
│   ├── QueueCode.php                      # CM-Tr, CM-Canc, AU, etc.
│   ├── EvaluationStatus.php              # activa, eliminada
│   └── QualityRole.php                    # evaluador, coordinador, supervisor, admin
├── Events/
│   ├── EvaluationCreated.php
│   ├── FeedbackAdded.php
│   ├── CalibrationCreated.php
│   └── CriteriaVersionCreated.php
├── Http/
│   └── Requests/
│       ├── StoreEvaluationRequest.php
│       ├── StoreFeedbackRequest.php
│       ├── StoreCalibrationRequest.php
│       └── StoreCriteriaRequest.php
├── Jobs/
│   └── RecalculateQueueStats.php
├── Listeners/
│   ├── SendEvaluationNotification.php
│   └── UpdateQueueScoreAverages.php
├── Livewire/
│   ├── EvaluationForm.php
│   ├── EvaluationIndex.php
│   ├── EvaluationDetail.php
│   ├── CriteriaList.php
│   ├── CriteriaForm.php
│   ├── QueueList.php
│   ├── FeedbackForm.php
│   └── CalibrationForm.php
├── Models/
│   ├── Queue.php
│   ├── Criteria.php
│   ├── CriteriaVersion.php
│   ├── QueueCriteria.php
│   ├── RedFlagCriteria.php
│   ├── Evaluation.php
│   ├── EvaluationScore.php
│   ├── EvaluationRedFlag.php
│   ├── Feedback.php
│   └── CalibrationLog.php
├── Observers/
│   └── EvaluationObserver.php
├── Policies/
│   ├── EvaluationPolicy.php
│   ├── CriteriaPolicy.php
│   ├── FeedbackPolicy.php
│   └── CalibrationPolicy.php
├── Providers/
│   └── ModuleServiceProvider.php
├── Repositories/
│   ├── EvaluationRepository.php
│   └── CriteriaRepository.php
├── Resources/Views/
│   └── livewire/
│       ├── evaluation-form.blade.php
│       ├── evaluation-index.blade.php
│       ├── evaluation-detail.blade.php
│       ├── criteria-list.blade.php
│       ├── criteria-form.blade.php
│       ├── queue-list.blade.php
│       ├── feedback-form.blade.php
│       └── calibration-form.blade.php
├── Routes/
│   └── web.php
└── Services/
    ├── EvaluationService.php
    ├── CriteriaVersioningService.php
    └── ReportService.php
```

Los modelos NO usan prefijo `Quality`. El namespace `App\Modules\QualityModule\Models\Evaluation` es suficiente para evitar colisiones.

## 3. Convenciones del Módulo

| Concepto          | Convención                                                                                                                                                                  |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Prefijo de tablas | `quality_` (ej: `quality_evaluations`) — única excepción a la regla de no prefijo, para evitar colisión semántica con otros módulos que pudieran tener tablas "evaluations" |
| Naming de modelos | Sin prefijo: `Evaluation`, no `QualityEvaluation`                                                                                                                           |
| Naming de rutas   | `quality.evaluations.index`, `quality.criteria.store`                                                                                                                       |
| Naming de vistas  | `quality::livewire.evaluation-form` (namespace `quality::`)                                                                                                                 |
| Primary Keys      | ULIDs (vía `BaseModel` de `App\Shared\Models\BaseModel`)                                                                                                                    |
| Permisos          | Formato `quality.evaluations.create`, `quality.criteria.update`                                                                                                             |
| Eventos           | Listeners sincrónicos salvo Jobs pesados que van a cola                                                                                                                     |
| Layout            | `layouts.app` — el layout compartido del proyecto, no uno propio                                                                                                            |

## 4. Flujo de Datos

### 4.1 Evaluación (Livewire full-page)

```
GET /quality/evaluaciones/crear?queue=CM-Tr
  │
  ├── Route::get('/evaluaciones/crear', EvaluationForm::class)
  │     ->middleware('can:quality.evaluations.create')
  │
  ├── EvaluationForm (mount)
  │     ├── Carga colas desde Queue::active()->get()
  │     ├── Carga criterios vía CriteriaRepository::getActiveByQueue($queueId)
  │     └── Renderiza quality::livewire.evaluation-form
  │
  └── Submit (Livewire::save)
        ├── FormRequest valida
        ├── StoreEvaluationAction::execute(CreateEvaluationDTO $dto)
        │     ├── DB::beginTransaction()
        │     ├── Evaluation::create([...])
        │     ├── EvaluationScore::insert([...])  // bulk
        │     ├── EvaluationRedFlag::insert([...]) // bulk
        │     ├── DB::commit()
        │     └── EvaluationCreated::dispatch($evaluation)
        └── Redirección → quality.evaluations.index con flash
```

### 4.2 Versionado de Criterios

```
Admin modifica criterio "Saludo inicial"
  │
  ├── PUT /quality/criterios/{criteria}
  ├── StoreCriteriaRequest
  ├── CriteriaPolicy::update($user, $criteria)
  │
  ├── CreateCriteriaVersionAction::execute(Criteria $criteria, array $newData)
  │     ├── $currentVersion = $criteria->currentVersion()
  │     ├── $currentVersion->update(['valid_to' => now()])
  │     ├── $newVersion = CriteriaVersion::create([...])
  │     └── CriteriaVersionCreated::dispatch($newVersion)
  │
  └── Opcional: actualizar queue_criteria para apuntar a la nueva versión
```

### 4.3 Tabla de Evaluaciones (FluxUI)

```
GET /quality/evaluaciones (EvaluationIndex)
  │
  ├── Filtros reactivos: queue, daterange, evaluador, operador
  ├── EvaluationRepository::paginated(filters, sort) — eager loading
  │     └── Evaluation::with(['scores', 'queue', 'feedback'])
  │            ->whereBetween('dteval', $dates)
  │            ->paginate(25)
  └── Renderiza quality::livewire.evaluation-index con <flux:table>
```

## 5. Diagrama ER (Modelos)

```
┌──────────────┐     ┌──────────────────┐     ┌────────────────────┐
│    Queue     │     │    Criteria       │     │   RedFlagCriteria  │
│──────────────│     │──────────────────│     │────────────────────│
│ id (ULID PK) │──┐  │ id (ULID PK)     │  ┌──│ id (ULID PK)       │
│ code (unique)│  │  │ code (unique)    │  │  │ criterio_text      │
│ name         │  │  │ created_at       │  │  │ perdida            │
└──────────────┘  │  └────────┬─────────┘  │  └────────────────────┘
                  │           │             │
┌─────────────────┘  ┌────────┴─────────┐  │
│  ┌────────────────────┐               │  │
│  │   QueueCriteria    │               │  │
│  │────────────────────│               │  │
│  │ queue_id (FK)      │               │  │
│  │ criteria_vers_id(FK)               │  │
│  │ orden               │             │  │
│  │ is_active           │             │  │
│  └────────────────────┘             │  │
│                                      │  │
│  ┌────────────────────┐              │  │
│  │ CriteriaVersion    │  ────────────┘  │
│  │────────────────────│                 │
│  │ criteria_id (FK)   │                 │
│  │ version            │                 │
│  │ criterio_text      │                 │
│  │ puntaje            │                 │
│  │ valid_from / _to   │                 │
│  └────────────────────┘                 │
│                                         │
│  ┌─────────────────────┐   ┌─────────────────────────┐
│  │    Evaluation       │   │  EvaluationRedFlag      │
│  │─────────────────────│   │─────────────────────────│
│  │ id (ULID PK)        │── │ evaluation_id (FK)      │
│  │ queue_id (FK)       │   │ red_flag_criteria_id(FK)│
│  │ employee_id (FK)    │   └─────────────────────────┘
│  │ evaluator_id (FK)   │
│  │ score               │
│  │ deleted_at (soft)   │
│  └────────┬────────────┘
│           │ 1:N
│  ┌────────┴────────────────┐
│  │    EvaluationScore       │
│  │─────────────────────────│
│  │ evaluation_id (FK)      │
│  │ criteria_version_id(FK) │
│  │ puntaje_obtenido        │
│  └─────────────────────────┘
│
│  ┌──────────────────┐   ┌──────────────────┐
│  │    Feedback       │   │  CalibrationLog  │
│  │──────────────────│   │──────────────────│
│  │ evaluation_id(FK)│   │ evaluation_id(FK)│
│  │ obsfeed          │   │ score_anterior   │
│  │ created_by (FK)  │   │ score_nuevo      │
│  └──────────────────┘   │ created_by (FK)  │
│                         └──────────────────┘
```

**Nota sobre ULIDs:** Todas las PKs y FKs usan ULIDs (string 26 chars). Las migraciones usan `foreignUlid('evaluator_id')->constrained('users')` y `foreignUlid('employee_id')->constrained('employees')`.

**Relación con processed_clips (videoparser):** La tabla `processed_clips` usa `BigInteger` auto-increment (sistema Python). La FK `evaluation.clip_id` debe ser `BigInteger` nullable, no ULID, por ser tabla externa al monolito. Para la integración basta con:

```php
// Migración: FK a processed_clips (tabla del videoparser)
Schema::table('quality_evaluations', function (Blueprint $table) {
    $table->unsignedBigInteger('clip_id')->nullable();
    $table->foreign('clip_id')->references('id')->on('processed_clips');
});
```

## 6. Service Provider

```php
class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            QualityEvaluationRepositoryInterface::class,
            EloquentQualityEvaluationRepository::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'quality');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->registerPolicies();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Evaluation::class, EvaluationPolicy::class);
        Gate::policy(Criteria::class, CriteriaPolicy::class);
        Gate::policy(Feedback::class, FeedbackPolicy::class);
        Gate::policy(CalibrationLog::class, CalibrationPolicy::class);
    }
}
```

## 7. Integración con el Proyecto Existente

| Concepto           | Cómo se integra                                                                                                                                                              |
| ------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Employee**       | `Evaluation.employee_id` → FK a `employees` (PersonnelModule). Tabla pública del núcleo (ADR-007).                                                                           |
| **User**           | `Evaluation.evaluator_id` → FK a `users` (CoreModule). Tabla pública del núcleo (ADR-007).                                                                                   |
| **Roles**          | Spatie Permission. Seeds crean `quality-evaluator`, `quality-coordinator`, `quality-supervisor`, `quality-admin`. El super-admin bypass del proyecto aplica automáticamente. |
| **Auditoría**      | Usar `AuditModule` del proyecto (`App\Modules\AuditModule\Models\AuditLog`) en lugar de activitylog externo.                                                                 |
| **Notificaciones** | Usar sistema de notificaciones existente del proyecto.                                                                                                                       |
| **Layout**         | `layouts.app` — mismo layout que el resto del proyecto. Sin layout propio.                                                                                                   |

### 7.1 Integración con el Sistema de Corte de Videos (videoparser)

El proyecto `scripts/videoparser/` es un servicio Python que procesa grabaciones de audio/video del contact center, detecta llamadas individuales mediante análisis de audio (silence detection con librosa) y genera clips individuales de cada llamada. Opera sobre la misma base de datos PostgreSQL compartida.

```
┌─────────────────────────────────────────────────────────────────┐
│               Base de Datos Compartida (PostgreSQL)             │
│                                                                 │
│  ┌────────────────────┐    ┌──────────────────────┐             │
│  │  ConnectModule      │    │  videoparser (Python)│             │
│  │  call_records       │◄───│  (read-only)         │             │
│  │  employees          │◄───│                      │             │
│  └────────────────────┘    │                      │             │
│                            │  call_segments       │ (escribe)   │
│  ┌────────────────────┐    │  processed_clips     │ (escribe)   │
│  │  QualityModule      │    └──────────────────────┘             │
│  │  evaluations        │◄─── clip_id → processed_clips.id       │
│  └────────────────────┘                                         │
└─────────────────────────────────────────────────────────────────┘
```

**Flujo de integración:**

1. El videoparser lee `call_records` y `employees` (ConnectModule y PersonnelModule) para correlacionar segmentos de audio con llamadas reales
2. El videoparser escribe `call_segments` y `processed_clips` con metadatos de cada llamada individual extraída
3. El QualityModule referencia `processed_clips.id` vía FK `evaluation.clip_id` para que el evaluador pueda ver/escuchar el clip original mientras evalúa
4. La tabla `processed_clips` incluye `call_record_id` que permite al evaluador consultar metadatos de la llamada original

**Modelos compartidos en la BD:**

| Tabla | Escritura | Lectura | Propietario |
|---|---|---|---|
| `employees` | PersonnelModule (Laravel) | videoparser | PersonnelModule |
| `call_records` | ConnectModule (Laravel) | videoparser | ConnectModule |
| `call_segments` | videoparser (Python) | QualityModule | videoparser |
| `processed_clips` | videoparser (Python) | QualityModule | videoparser |

**Modelo Evaluation actualizado:**

```php
// Migración: agregar clip_id a evaluations
// NOTA: processed_clips usa BigInteger auto-increment (sistema Python externo)
Schema::table('quality_evaluations', function (Blueprint $table) {
    $table->unsignedBigInteger('clip_id')->nullable();
    $table->foreign('clip_id')->references('id')->on('processed_clips');
});
```

## 8. Decisiones Técnicas

| #   | Decisión                                  | Alternativa                  | Razón                                                                                                              |
| --- | ----------------------------------------- | ---------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| 1   | **Livewire 4** para formularios reactivos | Vue/React + API              | Consistente con el resto del proyecto; formularios sin escribir JS.                                                |
| 2   | **Actions** con único `execute()`         | Métodos en controllers       | Separación de responsabilidades; reutilizable desde commands, jobs, tests.                                         |
| 3   | **DTOs immutables** (Spatie Data)         | Arrays planos                | Type hints, validación en construcción, serialización a JSON para jobs.                                            |
| 4   | **Repositories para queries complejas**   | Eloquent directo en Livewire | Aísla lógica de consultas del componente; facilita testing.                                                        |
| 5   | **FluxUI Table** para listados            | DataTables externo           | Consistente con el ecosistema FluxUI; evita dependencia adicional. Hasta 30K registros con paginación server-side. |
| 6   | **Spatie Permission** para roles          | Policy manual                | Roles reutilizables entre módulos; permisos granulares por cola.                                                   |
| 7   | **Events para efectos secundarios**       | Lógica acoplada en Actions   | Desacopla notificaciones y auditoría. Listeners sincrónicos por defecto.                                           |
| 8   | **SoftDeletes en evaluaciones**           | Eliminación física           | Auditoría: una evaluación eliminada persiste con `deleted_at`.                                                     |
| 9   | **ULIDs como PK**                         | Auto-increment               | Consistente con BaseModel del proyecto; no expone volumen de datos.                                                |
