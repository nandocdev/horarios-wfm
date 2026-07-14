# Lineamientos de Seguridad — QualityModule

## 1. Stack de Seguridad del Proyecto

El QualityModule hereda toda la infraestructura de seguridad del monólito modular. No implementa autenticación ni autorización propia.

| Capa | Cómo lo resuelve el proyecto |
|---|---|
| Autenticación | Laravel Fortify (email verification + 2FA TOTP habilitados) |
| Autorización | Spatie Laravel Permission 7 + Policies |
| CSRF | `VerifyCsrfToken` middleware global + Livewire lo maneja automáticamente |
| SQL Injection | Eloquent ORM + Query Builder (prepared statements nativos) |
| Passwords | `Hash::make()` con bcrypt, columna `password` VARCHAR(255) |
| HTTPS | `TrustProxies` middleware + forced scheme en producción |
| Headers | `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY` |
| Session | Database driver, lifetime 120 min |
| Rate Limiting | Fortify throttle 5/min en login |

---

## 2. Rutas Seguras

```php
// Routes/web.php
Route::prefix('quality')
    ->middleware(['web', 'auth', 'verified'])
    ->group(function () {

    Route::get('/evaluaciones', EvaluationIndex::class)
        ->middleware('can:quality.evaluations.view')
        ->name('quality.evaluations.index');

    Route::get('/evaluaciones/crear', EvaluationForm::class)
        ->middleware('can:quality.evaluations.create')
        ->name('quality.evaluations.create');

    Route::get('/evaluaciones/{evaluation}', EvaluationDetail::class)
        ->middleware('can:quality.evaluations.view')
        ->name('quality.evaluations.show');

    Route::get('/evaluaciones/{evaluation}/feedback', FeedbackForm::class)
        ->middleware('can:quality.feedback.create')
        ->name('quality.feedback.create');

    Route::get('/evaluaciones/{evaluation}/calibrar', CalibrationForm::class)
        ->middleware('can:quality.calibrations.create')
        ->name('quality.calibrations.create');

    Route::get('/criterios', CriteriaList::class)
        ->middleware('can:quality.criteria.view')
        ->name('quality.criteria.index');

    Route::get('/criterios/crear', CriteriaForm::class)
        ->middleware('can:quality.criteria.create')
        ->name('quality.criteria.create');

    Route::get('/colas', QueueList::class)
        ->middleware('can:quality.queues.manage')
        ->name('quality.queues.index');
});
```

## 3. Políticas

```php
// Policies/EvaluationPolicy.php
class EvaluationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Evaluation $evaluation): bool
    {
        return $user->hasPermissionTo('quality.evaluations.view')
            || $evaluation->evaluator_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('quality.evaluations.create');
    }

    public function delete(User $user, Evaluation $evaluation): bool
    {
        if ($evaluation->feedback()->exists() || $evaluation->calibrations()->exists()) {
            return false; // RN-03
        }
        return $user->hasPermissionTo('quality.evaluations.delete');
    }

    public function calibrate(User $user, Evaluation $evaluation): bool
    {
        return $user->hasPermissionTo('quality.calibrations.create')
            && $evaluation->status === 'activa';
    }
}
```

## 4. Validación de Input

```php
// Http/Requests/StoreEvaluationRequest.php
class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('quality.evaluations.create');
    }

    public function rules(): array
    {
        return [
            'employee_id'  => ['required', 'string', 'exists:employees,id'],
            'evaluator_id' => ['required', 'string', 'exists:users,id'],
            'queue_id'     => ['required', 'string', 'exists:quality_queues,id'],
            'dtcall'       => ['nullable', 'date', 'before_or_equal:today'],
            'tmcall'       => ['nullable', 'date_format:H:i', 'after:06:00', 'before:19:00'],
            'scores'       => ['required', 'array', 'min:1'],
            'scores.*.criteria_version_id' => ['required', 'string', 'exists:quality_criteria_versions,id'],
            'scores.*.puntaje'             => ['required', 'integer', 'min:0'],
            'callobs'      => ['nullable', 'string', 'max:2500'],
        ];
    }
}
```

**Nota:** Los IDs son ULIDs (string 26 chars). Las reglas de validación usan `string` en lugar de `integer` para las FK.

## 5. Auditoría

Usar el **AuditModule** existente del proyecto (`App\Modules\AuditModule\Models\AuditLog`) en lugar de una librería externa:

```php
// En los Listeners:
use App\Modules\AuditModule\Models\AuditLog;

class LogEvaluationActivity
{
    public function handle(EvaluationCreated $event): void
    {
        AuditLog::log(
            action: 'evaluation_created',
            actorId: $event->createdBy->id,
            description: "Evaluación creada para empleado {$event->evaluation->employee_id}",
            properties: ['score' => $event->evaluation->score],
            module: 'quality'
        );
    }
}
```

### Eventos a auditar

- `evaluation_created` — quién evaluó a quién, cola, score
- `evaluation_deleted` — soft delete
- `feedback_created` — quién dio feedback, sobre qué evaluación
- `calibration_created` — score anterior y nuevo, quién calibró
- `criteria_version_created` — quién modificó el criterio

## 6. Checklist de Seguridad

### Autenticación y Sesión

- [x] Rutas del módulo envueltas en `middleware('auth')`
- [x] `middleware('verified')` — email verification (heredado de Fortify)
- [x] 2FA disponible (heredado de Fortify)
- [x] Timeout de sesión: 120 min (configuración global)
- [x] Throttle: 5 intentos/min en login (Fortify)

### Autorización

- [ ] Cada Livewire component verifica su permiso en `mount()` o `authorize()`
- [ ] Policies registradas en `ModuleServiceProvider`
- [ ] Seed de roles y permisos en `QualityModuleSeeder`
- [ ] Super-admin bypass automático (rol `admin` via `Gate::before()`)

### Validación de Input

- [ ] Todos los formularios tienen su `FormRequest`
- [ ] Livewire `rules()` definidas con type hints
- [ ] Prohibido `DB::raw()` con concatenación de strings
- [ ] Escaping automático en Blade con `{{ }}`

### CSRF

- [x] `@csrf` en todo `<form>` Blade (cuando no se usa Livewire)
- [x] Livewire maneja CSRF automáticamente
- [x] `VerifyCsrfToken` middleware activo

### Base de Datos

- [ ] Migraciones con `foreignUlid()->constrained()` para integridad referencial ULID
- [ ] Usuario BD de la app con solo `SELECT, INSERT, UPDATE, DELETE`
- [ ] Usuario BD de migraciones con `ALTER, CREATE, INDEX, DROP` (solo deploy)

## 7. Pruebas de Seguridad

```php
// Tests/Feature/QualityModule/SecurityTest.php
class SecurityTest extends TestCase
{
    /** @test */
    public function unauthenticated_user_is_redirected(): void
    {
        $response = $this->get('/quality/evaluaciones');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function evaluator_cannot_access_criteria_admin(): void
    {
        $evaluator = User::factory()->create();
        $evaluator->assignRole('quality-evaluator');

        $response = $this->actingAs($evaluator)
            ->get('/quality/criterios/crear');

        $response->assertForbidden();
    }

    /** @test */
    public function sql_injection_is_prevented(): void
    {
        $admin = User::factory()->create()->assignRole('quality-admin');
        $malicious = "' OR '1'='1";

        $response = $this->actingAs($admin)
            ->get('/quality/evaluaciones?search='.$malicious);

        $response->assertSuccessful();
    }
}
```

## 8. Vulnerabilidades Remanentes

| ID | Descripción | Prioridad |
|---|---|---|
| V-01 | Sin 2FA específico para quality (heredado: el proyecto tiene 2FA global) | Baja |
| V-02 | Sin rate limiting específico para el módulo (heredado del global) | Baja |
| V-03 | Sin validación de IP para admin | Baja |
